<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreYudisiumEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('tanggal_buka') && ! $this->filled('tgl_buka')) {
            $merge['tgl_buka'] = $this->input('tanggal_buka');
        }

        if ($this->filled('tanggal_tutup') && ! $this->filled('tgl_tutup')) {
            $merge['tgl_tutup'] = $this->input('tanggal_tutup');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'form_id' => 'required|exists:forms,id',
            'program_studi_id' => 'required|exists:program_studi,id',
            'nama_event' => 'required|string|max:255',
            'periode' => 'required|string|max:255',
            'tgl_buka' => 'required|date',
            'tgl_tutup' => 'required|date|after:tgl_buka',
            'tgl_yudisium' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'form_id.required' => 'Form yudisium wajib dipilih.',
            'form_id.exists' => 'Form yudisium yang dipilih tidak valid.',
            'program_studi_id.required' => 'Program studi wajib dipilih.',
            'program_studi_id.exists' => 'Program studi yang dipilih tidak valid.',
            'nama_event.required' => 'Nama event wajib diisi.',
            'nama_event.string' => 'Nama event harus berupa teks.',
            'nama_event.max' => 'Nama event maksimal :max karakter.',
            'periode.required' => 'Periode yudisium wajib diisi.',
            'periode.string' => 'Periode yudisium harus berupa teks.',
            'periode.max' => 'Periode yudisium maksimal :max karakter.',
            'tgl_buka.required' => 'Tanggal buka pendaftaran wajib diisi.',
            'tgl_buka.date' => 'Format tanggal buka tidak valid.',
            'tgl_tutup.required' => 'Tanggal tutup pendaftaran wajib diisi.',
            'tgl_tutup.date' => 'Format tanggal tutup tidak valid.',
            'tgl_tutup.after' => 'Tanggal tutup harus setelah tanggal buka.',
            'tgl_yudisium.date' => 'Format tanggal yudisium tidak valid.',
            'is_active.boolean' => 'Status aktif harus bernilai boolean.',
        ];
    }
}
