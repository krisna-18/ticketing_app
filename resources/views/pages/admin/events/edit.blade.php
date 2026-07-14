@extends('layouts.admin_layouts')

@section('title', 'Edit Event')

@section('content')

    <div class="container mx-auto p-10 max-w-5xl">
        <div class="flex items-center mb-6">
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline btn-sm mr-4">
                &larr; Kembali
            </a>
            <h1 class="text-3xl font-semibold">Edit Event</h1>
        </div>

        @if ($errors->any())
            <div class="alert alert-error mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error mb-6">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($hasSales)
            <div class="alert alert-warning mb-6">
                <span>
                    Event ini sudah memiliki penjualan tiket. Beberapa field (tanggal &amp; waktu, dan tiket yang sudah terjual) tidak dapat diubah.
                </span>
            </div>
        @endif

        <div class="card bg-white shadow-xs">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data" id="eventForm">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Judul -->
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium">Judul Event</span>
                                <span class="text-error">*</span>
                            </label>
                            <input type="text" name="judul" value="{{ old('judul', $event->judul) }}"
                                   class="input input-bordered w-full" required>
                        </div>

                        <!-- Kategori -->
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium">Kategori</span>
                                <span class="text-error">*</span>
                            </label>
                            <select name="kategori_id" class="select select-bordered w-full" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('kategori_id', $event->kategori_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Lokasi -->
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium">Lokasi</span>
                                <span class="text-error">*</span>
                            </label>
                            <input type="text" name="lokasi" value="{{ old('lokasi', $event->lokasi) }}"
                                   class="input input-bordered w-full" required>
                        </div>

                        <!-- Tanggal & Waktu -->
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium">Tanggal &amp; Waktu</span>
                                <span class="text-error">*</span>
                                @if ($hasSales)
                                    <span class="text-xs text-warning ml-1">(terkunci - sudah ada penjualan)</span>
                                @endif
                            </label>
                            <input type="datetime-local" name="tanggal_waktu"
                                   value="{{ old('tanggal_waktu', $event->tanggal_waktu?->format('Y-m-d\TH:i')) }}"
                                   class="input input-bordered w-full"
                                   {{ $hasSales ? 'readonly' : '' }} required>
                        </div>

                        <!-- Gambar -->
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium">Gambar Event</span>
                                <span class="text-xs text-gray-400">(kosongkan jika tidak ingin mengubah gambar)</span>
                            </label>

                            <div class="mb-2">
                                <img src="{{ $event->image_url }}" alt="{{ $event->judul }}" class="w-32 h-32 object-cover rounded-lg border">
                            </div>

                            <input type="file" name="gambar" accept="image/png, image/jpeg"
                                   class="file-input file-input-bordered w-full" id="gambarInput">
                            <div id="imagePreviewContainer" class="hidden mt-2">
                                <img id="imagePreview" src="" alt="Preview baru" class="w-32 h-32 object-cover rounded-lg border">
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block">
                                <span class="text-sm font-medium">Deskripsi</span>
                                <span class="text-error">*</span>
                            </label>
                            <textarea name="deskripsi" rows="4" class="textarea textarea-bordered w-full" required>{{ old('deskripsi', $event->deskripsi) }}</textarea>
                        </div>
                    </div>

                    <div class="divider mt-8"></div>

                    <!-- Dynamic Ticket Form -->
                    <div class="flex items-center mb-4">
                        <h2 class="text-xl font-semibold">Tiket</h2>
                        <button type="button" class="btn btn-sm btn-primary ml-auto" id="addTicketBtn">
                            + Tambah Tiket
                        </button>
                    </div>

                    <div id="ticketsContainer" class="space-y-4"></div>

                    <div class="card-actions justify-end mt-8">
                        <a href="{{ route('admin.events.index') }}" class="btn btn-outline">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let ticketIndex = 0;
        const ticketsContainer = document.getElementById('ticketsContainer');

        @php
            $formattedTickets = $event->tikets->map(function ($tiket) {
                return [
                    'id' => $tiket->id,
                    'tipe' => $tiket->tipe,
                    'harga' => (float) $tiket->harga,
                    'stok' => $tiket->stok,
                    'sold' => $tiket->detailOrders()->exists(),
                ];
            });
        @endphp
        const existingTickets = @json($formattedTickets);

        function renderTicketCard(index, data = {}) {
            const wrapper = document.createElement('div');
            wrapper.className = 'card bg-base-200 ticket-card';
            wrapper.dataset.index = index;

            const isSold = data.sold === true;
            const idInput = data.id
                ? `<input type="hidden" name="tikets[${index}][id]" value="${data.id}">`
                : '';

            const soldBadge = isSold
                ? '<span class="badge badge-warning badge-sm ml-2">Sudah Terjual</span>'
                : '';

            const removeButton = isSold
                ? ''
                : `<button type="button" class="btn btn-xs bg-red-500 text-white ml-auto remove-ticket-btn">Hapus</button>`;

            wrapper.innerHTML = `
                ${idInput}
                <div class="card-body p-4">
                    <div class="flex items-center mb-3">
                        <h3 class="font-semibold ticket-title">Tiket #${index + 1}</h3>
                        ${soldBadge}
                        <div class="ml-auto">${removeButton}</div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="block">
                                <span class="text-sm font-medium">Tipe Tiket</span>
                                <span class="text-error">*</span>
                            </label>
                            <select name="tikets[${index}][tipe]" class="select select-bordered select-sm w-full" ${isSold ? 'disabled' : ''} required>
                                <option value="reguler" ${data.tipe === 'reguler' ? 'selected' : ''}>Reguler</option>
                                <option value="premium" ${data.tipe === 'premium' ? 'selected' : ''}>Premium</option>
                            </select>
                            ${isSold ? `<input type="hidden" name="tikets[${index}][tipe]" value="${data.tipe}">` : ''}
                        </div>
                        <div class="space-y-1">
                            <label class="block">
                                <span class="text-sm font-medium">Harga (Rp)</span>
                                <span class="text-error">*</span>
                            </label>
                            <input type="number" name="tikets[${index}][harga]" min="0" step="1000"
                                   value="${data.harga ?? ''}" class="input input-bordered input-sm w-full" required>
                        </div>
                        <div class="space-y-1">
                            <label class="block">
                                <span class="text-sm font-medium">Stok</span>
                                <span class="text-error">*</span>
                            </label>
                            <input type="number" name="tikets[${index}][stok]" min="0"
                                   value="${data.stok ?? ''}" class="input input-bordered input-sm w-full" required>
                        </div>
                    </div>
                </div>
            `;

            const removeBtn = wrapper.querySelector('.remove-ticket-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    if (ticketsContainer.querySelectorAll('.ticket-card').length <= 1) {
                        alert('Minimal harus ada 1 tiket.');
                        return;
                    }
                    wrapper.remove();
                    renumberTickets();
                });
            }

            return wrapper;
        }

        function addTicket(data = {}) {
            const card = renderTicketCard(ticketIndex, data);
            ticketsContainer.appendChild(card);
            ticketIndex++;
        }

        function renumberTickets() {
            ticketsContainer.querySelectorAll('.ticket-card').forEach((card, i) => {
                card.querySelector('.ticket-title').textContent = `Tiket #${i + 1}`;
            });
        }

        document.getElementById('addTicketBtn').addEventListener('click', () => addTicket());

        // Load existing tickets saat halaman dibuka
        if (existingTickets.length > 0) {
            existingTickets.forEach((t) => addTicket(t));
        } else {
            addTicket();
        }

        // Image preview
        document.getElementById('gambarInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('imagePreviewContainer');
            const preview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
                preview.src = '';
            }
        });
    </script>

@endsection