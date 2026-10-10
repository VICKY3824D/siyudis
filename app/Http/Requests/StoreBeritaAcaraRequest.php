<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeritaAcaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'yudisium_event_id' => ['required', 'exists:yudisium_events,id'],
            'program_studi_id' => ['required', 'exists:program_studi,id'],
            'pengajuan_ids' => ['required', 'array', 'min:1'],
            'pengajuan_ids.*' => ['required', 'exists:pengajuan_yudisium,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'yudisium_event_id.required' => 'Yudisium event harus dipilih.',
            'yudisium_event_id.exists' => 'Yudisium event tidak ditemukan.',
            'program_studi_id.required' => 'Program studi harus dipilih.',
            'program_studi_id.exists' => 'Program studi tidak ditemukan.',
            'pengajuan_ids.required' => 'Pengajuan harus dipilih.',
            'pengajuan_ids.array' => 'Format pengajuan tidak valid.',
            'pengajuan_ids.min' => 'Minimal 1 pengajuan harus dipilih.',
            'pengajuan_ids.*.exists' => 'Salah satu pengajuan tidak ditemukan.',
        ];
    }
}
