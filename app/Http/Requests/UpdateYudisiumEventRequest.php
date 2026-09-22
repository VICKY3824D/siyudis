<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateYudisiumEventRequest extends FormRequest
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
        $event = $this->route('event');
        $tglBuka = $this->input('tgl_buka', $event?->tgl_buka);

        return [
            'form_id' => 'sometimes|required|exists:forms,id',
            'program_studi_id' => 'sometimes|required|exists:program_studi,id',
            'nama_event' => 'sometimes|required|string|max:255',
            'periode' => 'sometimes|required|string|max:255',
            'tgl_buka' => 'sometimes|required|date',
            'tgl_tutup' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($tglBuka) {
                    if ($tglBuka && strtotime((string) $value) <= strtotime((string) $tglBuka)) {
                        $fail('Tanggal tutup harus setelah tanggal buka.');
                    }
                },
            ],
            'tgl_yudisium' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
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
            'tgl_yudisium.date' => 'Format tanggal yudisium tidak valid.',
            'is_active.boolean' => 'Status aktif harus bernilai boolean.',
        ];
    }
}
