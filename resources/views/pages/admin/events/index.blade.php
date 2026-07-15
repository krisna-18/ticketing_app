@extends('layouts.admin_layouts')

@section('title', 'Manajemen Event')

@section('content')

    <div class="container mx-auto p-10">
        <div class="flex items-center mb-4">
            <h1 class="text-3xl font-semibold">Manajemen Event</h1>
            <a href="{{ route('admin.events.create') }}" class="btn btn-primary ml-auto">
                Tambah Event
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success mb-4">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error mb-4">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning mb-4">
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        <!-- Filter Form -->
        <div class="bg-white rounded-box shadow-xs p-5 mb-6">
            <form method="GET" action="{{ route('admin.events.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="form-control w-full">
                    <label class="label mb-1">
                        <span class="label-text">Cari</span>
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari judul atau lokasi..."
                           class="input input-bordered w-full">
                </div>

                <div class="form-control w-full">
                    <label class="label mb-1">
                        <span class="label-text">Kategori</span>
                    </label>
                    <select name="kategori_id" class="select select-bordered w-full">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) request('kategori_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-full">
                    <label class="label mb-1">
                        <span class="label-text">Urutkan Tanggal</span>
                    </label>
                    <select name="sort" class="select select-bordered w-full">
                        <option value="asc" {{ request('sort', 'asc') === 'asc' ? 'selected' : '' }}>Terlama - Terbaru</option>
                        <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>Terbaru - Terlama</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <!-- Events Table -->
        <div class="overflow-x-auto rounded-box bg-white p-5 shadow-xs">
            <form id="bulkDeleteForm" method="POST" action="{{ route('admin.events.bulkDestroy') }}">
                @csrf
                <div class="mb-4">
                    <button type="submit" class="btn btn-error btn-sm text-white" id="btnHapusTerpilih" disabled>
                        Hapus Terpilih
                    </button>
                </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" id="selectAllCheckbox" class="checkbox checkbox-sm" />
                            </label>
                        </th>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="ids[]" value="{{ $event->id }}" class="checkbox checkbox-sm event-checkbox" />
                                </label>
                            </td>
                            <td>
                                <img src="{{ $event->image_url }}" alt="{{ $event->judul }}"
                                     class="w-16 h-16 object-cover rounded-lg">
                            </td>
                            <td class="font-medium">{{ $event->judul }}</td>
                            <td>
                                @if ($event->kategori)
                                    <span class="badge badge-ghost">{{ $event->kategori->nama }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($event->tanggal_waktu)->translatedFormat('d M Y, H:i') }}</td>
                            <td>{{ $event->lokasi }}</td>
                            <td>
                                @php
                                    $statusColor = match ($event->status) {
                                        'Upcoming' => 'badge-info',
                                        'Ongoing' => 'badge-success',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusColor }}">{{ $event->status }}</span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline" target="_blank">
                                        Lihat
                                    </a>
                                    <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-primary">
                                        Edit
                                    </a>
                                    <button type="button" class="btn btn-sm bg-red-500 text-white" onclick="openDeleteModal(this)"
                                            data-id="{{ $event->id }}" data-judul="{{ $event->judul }}">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                Tidak ada event yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </form>
        </div>

        <div class="mt-6">
            {{ $events->appends(request()->except('page'))->links() }}
        </div>
    </div>

    <!-- Delete Modal -->
    <dialog id="delete_modal" class="modal">
        <form method="POST" class="modal-box">
            @csrf
            @method('DELETE')

            <h3 class="text-lg font-bold mb-4">Hapus Event</h3>
            <p>Apakah Anda yakin ingin menghapus event "<span id="delete_event_judul" class="font-semibold"></span>"?</p>
            <p class="text-sm text-gray-500 mt-2">Event yang sudah memiliki penjualan tiket tidak dapat dihapus.</p>

            <div class="modal-action">
                <button class="btn btn-primary" type="submit">Hapus</button>
                <button class="btn" type="button" onclick="delete_modal.close()">Batal</button>
            </div>
        </form>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const eventCheckboxes = document.querySelectorAll('.event-checkbox');
            const btnHapusTerpilih = document.getElementById('btnHapusTerpilih');
            const bulkDeleteForm = document.getElementById('bulkDeleteForm');

            function updateDeleteButtonState() {
                const checkedCount = document.querySelectorAll('.event-checkbox:checked').length;
                btnHapusTerpilih.disabled = checkedCount === 0;
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    eventCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    updateDeleteButtonState();
                });
            }

            eventCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const allChecked = document.querySelectorAll('.event-checkbox:checked').length === eventCheckboxes.length;
                    selectAllCheckbox.checked = allChecked && eventCheckboxes.length > 0;
                    updateDeleteButtonState();
                });
            });

            if (bulkDeleteForm) {
                bulkDeleteForm.addEventListener('submit', function (e) {
                    const checkedCount = document.querySelectorAll('.event-checkbox:checked').length;
                    if (!confirm(`Apakah Anda yakin ingin menghapus ${checkedCount} event yang dipilih?`)) {
                        e.preventDefault();
                    }
                });
            }
        });

        function openDeleteModal(button) {
            const id = button.dataset.id;
            const judul = button.dataset.judul;
            const form = document.querySelector('#delete_modal form');

            document.getElementById('delete_event_judul').textContent = judul;
            form.action = `{{ url('/admin/events') }}/${id}`;

            delete_modal.showModal();
        }
    </script>

@endsection
