<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'mitra' => 'required|max:100',
            'website' => 'nullable|url|max:100',
            'status' => 'nullable',
            'waktu_mulai' => 'required',
            'waktu_berakhir' => 'required',
            'nama_tempat' => 'required|max:100',
            'alamat' => 'required|max:200',
            'direction' => 'nullable|url|max:500',
            'kota' => 'required|max:100',
            'jumlah_tiket' => 'required|max:100',
            'harga' => 'required|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048|dimensions:min_width=100,min_height=100',
            'deskripsi' => 'nullable',
            'syarat_tiket' => 'nullable|string|max:20000',
            'pesan_email_tiket' => 'nullable|string|max:20000',
            'kontak_whatsapp_tiket' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s-]{9,20}$/'],
            'kontak_email_tiket' => 'nullable|email|max:255',
            'kontak_website_tiket' => 'nullable|url|max:255',
            'benefits' => 'nullable|array',
            'benefits.*' => 'nullable|string|max:150',
            'agenda' => 'nullable|array',
            'agenda.*.time_label' => 'nullable|string|max:100',
            'agenda.*.title' => 'nullable|string|max:150',
            'agenda.*.description' => 'nullable|string|max:500',
            'ada_baju' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kontak_whatsapp_tiket.regex' => 'Nomor WhatsApp hanya boleh berisi angka (boleh diawali +).',
        ];
    }
}
