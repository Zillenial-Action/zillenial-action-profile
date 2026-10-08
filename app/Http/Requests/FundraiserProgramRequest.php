<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi tambah dan edit program fundraiser dari admin.
 */
class FundraiserProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $program = $this->route('fundraiser');

        return [
            'id_event' => [
                'required',
                'exists:events,id',
                Rule::unique('fundraiser_programs', 'id_event')->ignore($program?->id),
            ],
            'nilai_diskon' => ['required', 'integer', 'min:0'],
            'nilai_komisi' => ['required', 'integer', 'min:0'],
            'kuota_per_kode' => ['required', 'integer', 'min:1'],
            'tanggal_berakhir' => $program ? ['required', 'date'] : ['required', 'date', 'after_or_equal:today'],
            'status' => [$program ? 'required' : 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $event = Event::find($this->input('id_event'));
            if ($event && (int) $this->input('nilai_diskon') > (int) $event->harga) {
                $validator->errors()->add('nilai_diskon', 'Diskon tidak boleh melebihi harga tiket event (Rp '
                    .number_format($event->harga, 0, ',', '.').').');
            }
        });
    }

    public function messages(): array
    {
        return [
            'id_event.required' => 'Event wajib dipilih.',
            'id_event.unique' => 'Event ini sudah punya program fundraiser. Edit program yang ada.',
            'nilai_diskon.required' => 'Diskon per tiket wajib diisi.',
            'nilai_komisi.required' => 'Komisi per tiket wajib diisi.',
            'kuota_per_kode.required' => 'Kuota per kode wajib diisi.',
            'kuota_per_kode.min' => 'Kuota per kode minimal 1.',
            'tanggal_berakhir.required' => 'Tanggal berakhir wajib diisi.',
            'tanggal_berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum hari ini.',
        ];
    }
}
