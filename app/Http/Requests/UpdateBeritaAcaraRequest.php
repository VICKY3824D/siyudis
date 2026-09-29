<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeritaAcaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'tanggal_surat' => ['nullable', 'date'],
            'periode' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_surat.max' => 'Nomor surat tidak boleh lebih dari :max karakter.',
            'tanggal_surat.date' => 'Tanggal surat harus berupa tanggal yang valid.',
            'periode.max' => 'Periode tidak boleh lebih dari :max karakter.',
        ];
    }
}
