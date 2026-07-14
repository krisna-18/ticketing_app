<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventFormRequest;
use App\Models\Event;
use App\Models\Kategori;
use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of events (admin).
     */
    public function index(Request $request)
    {
        $query = Event::with(['kategori', 'tikets']);

        // Filter by kategori
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Search by judul or lokasi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        // Sort by tanggal_waktu
        $sort = $request->get('sort', 'asc');
        $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'asc';
        $query->orderBy('tanggal_waktu', $sort);

        $events = $query->paginate(10)->withQueryString();

        $categories = Kategori::all();

        return view('pages.admin.events.index', [
            'events' => $events,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
    $categories = Kategori::all();

    return view('pages.admin.events.create', [
        'categories' => $categories,
    ]);
    }

    public function store(EventFormRequest $request)
    {
    $validated = $request->validated();

    DB::transaction(function () use ($request, $validated) {
        // Handle image upload
        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('events', 'public');
        } else {
            $path = 'konser.jpg';
        }

        $event = Event::create([
            'user_id' => auth()->id(),
            'kategori_id' => $validated['kategori_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'lokasi' => $validated['lokasi'],
            'gambar' => $path,
            'tanggal_waktu' => $validated['tanggal_waktu'],
        ]);

        foreach ($validated['tikets'] as $tiket) {
            $event->tikets()->create([
                'tipe' => $tiket['tipe'],
                'harga' => $tiket['harga'],
                'stok' => $tiket['stok'],
            ]);
        }
    });

    return redirect()
        ->route('admin.events.index')
        ->with('success', 'Event berhasil ditambahkan!');
    }

    public function edit(Event $event)
    {
    $event->load('tikets');
    $categories = Kategori::all();
    $hasSales = $event->hasSales();

    return view('pages.admin.events.edit', [
        'event' => $event,
        'categories' => $categories,
        'hasSales' => $hasSales,
    ]);
    }


    /**
 * Update the specified event.
 */
    public function update(EventFormRequest $request, Event $event)
    {
    $validated = $request->validated();
    $hasSales = $event->hasSales();

    // Jika event sudah terjual, tanggal_waktu tidak boleh diubah
    if ($hasSales) {
        $originalDate = $event->tanggal_waktu?->format('Y-m-d H:i:s');
        $newDate = date('Y-m-d H:i:s', strtotime($validated['tanggal_waktu']));

        if ($originalDate !== $newDate) {
            return back()
                ->withInput()
                ->with('error', 'Tanggal & waktu tidak dapat diubah karena event sudah memiliki penjualan tiket.');
        }
    }

    DB::transaction(function () use ($request, $validated, $event, $hasSales) {
        // Handle image update
        if ($request->hasFile('gambar')) {
            if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
                Storage::disk('public')->delete($event->gambar);
            }
            $path = $request->file('gambar')->store('events', 'public');
        } else {
            $path = $event->gambar;
        }

        $event->update([
            'kategori_id' => $validated['kategori_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'lokasi' => $validated['lokasi'],
            'gambar' => $path,
            'tanggal_waktu' => $hasSales ? $event->tanggal_waktu : $validated['tanggal_waktu'],
        ]);

        $incomingIds = collect($validated['tikets'])->pluck('id')->filter()->all();

        // Delete removed tickets — hanya boleh jika tiket tsb belum pernah terjual
        $event->tikets()
            ->whereNotIn('id', $incomingIds)
            ->get()
            ->each(function (Tiket $tiket) {
                if (! $tiket->detailOrders()->exists()) {
                    $tiket->delete();
                }
            });

        foreach ($validated['tikets'] as $tiketData) {
            if (! empty($tiketData['id'])) {
                $tiket = $event->tikets()->find($tiketData['id']);
                if ($tiket) {
                    $tiket->update([
                        'tipe' => $tiketData['tipe'],
                        'harga' => $tiketData['harga'],
                        'stok' => $tiketData['stok'],
                    ]);
                }
            } else {
                $event->tikets()->create([
                    'tipe' => $tiketData['tipe'],
                    'harga' => $tiketData['harga'],
                    'stok' => $tiketData['stok'],
                ]);
            }
        }
    });

    return redirect()
        ->route('admin.events.index')
        ->with('success', 'Event berhasil diperbarui!');
    }

    /**
 * Remove the specified event.
 */
    public function destroy(Event $event)
    {
        if ($event->hasSales()) {
            return back()->with('error', 'Event tidak dapat dihapus karena sudah memiliki penjualan tiket.');
        }

        if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
            Storage::disk('public')->delete($event->gambar);
        }

        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus!');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        $event->load(['kategori', 'tikets']);

        $relatedEvents = Event::with(['tikets'])
            ->where('id', '!=', $event->id)
            ->where('kategori_id', $event->kategori_id)
            ->upcoming()
            ->orderBy('tanggal_waktu', 'asc')
            ->limit(4)
            ->get()
            ->map(function ($e) {
                $e->tikets_min_harga = $e->tikets->min('harga') ?? 0;
                return $e;
            });

        return view('events.show', [
            'event' => $event,
            'relatedEvents' => $relatedEvents,
        ]);
    }
}
