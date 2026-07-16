<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
{
    return $this->user() !== null && $this->user()->role === 'admin';
}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

    $tanggalRules = ['required', 'date'];
    if (! $isUpdate) {
        $tanggalRules[] = 'after:now';
    }

    return [
        'judul' => ['required', 'string', 'max:255'],
        'deskripsi' => ['required', 'string'],
        'lokasi' => ['required', 'string', 'max:255'],
        'kategori_id' => ['required', 'exists:kategoris,id'],
        'tanggal_waktu' => $tanggalRules,
        'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

        'tikets' => ['required', 'array', 'min:1'],
        'tikets.*.id' => ['nullable', 'exists:tikets,id'],
        'tikets.*.tipe' => ['required', 'in:reguler,premium'],
        'tikets.*.harga' => ['required', 'numeric', 'min:0'],
        'tikets.*.stok' => ['required', 'integer', 'min:0'],
    ];
}

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul event wajib diisi.',
            'judul.string' => 'Judul event harus berupa teks.',
            'judul.max' => 'Judul event tidak boleh lebih dari 255 karakter.',

            'deskripsi.required' => 'Deskripsi event wajib diisi.',
            'deskripsi.string' => 'Deskripsi event harus berupa teks.',

            'lokasi.required' => 'Lokasi event wajib diisi.',
            'lokasi.string' => 'Lokasi event harus berupa teks.',
            'lokasi.max' => 'Lokasi event tidak boleh lebih dari 255 karakter.',

            'kategori_id.required' => 'Kategori event wajib dipilih.',
            'kategori_id.exists' => 'Kategori event yang dipilih tidak valid.',

            'tanggal_waktu.required' => 'Tanggal dan waktu event wajib diisi.',
            'tanggal_waktu.date' => 'Tanggal dan waktu event harus berupa tanggal yang valid.',
            'tanggal_waktu.after' => 'Tanggal dan waktu event harus di masa mendatang.',

            'gambar.image' => 'Gambar harus berupa file gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG, JPEG, atau PNG.',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.',

            // Tiket validation messages
            'tikets.required' => 'Tiket wajib diisi.',
            'tikets.array' => 'Data tiket tidak valid.',
            'tikets.min' => 'Setidaknya satu tiket harus ditambahkan.',

            'tikets.*.tipe.required' => 'Tipe tiket wajib diisi.',
            'tikets.*.tipe.in' => 'Tipe tiket harus berupa "reguler" atau "premium".',
            'tikets.*.tipe.string' => 'Tipe tiket harus berupa teks.',
            
            'tikets.*.harga.required' => 'Harga tiket wajib diisi.',
            'tikets.*.harga.numeric' => 'Harga tiket harus berupa angka.',
            'tikets.*.harga.min' => 'Harga tiket tidak boleh kurang dari 0.',
            
            'tikets.*.stok.required' => 'Stok tiket wajib diisi.',
            'tikets.*.stok.integer' => 'Stok tiket harus berupa bilangan bulat.',
            'tikets.*.stok.min' => 'Stok tiket tidak boleh kurang dari 0.',
            'tikets.*.id.exists' => 'Tiket yang dipilih tidak valid.',
        ];
    }
}
