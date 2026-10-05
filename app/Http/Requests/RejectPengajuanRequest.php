<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_revisi_internal' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_revisi_internal.required' => 'Catatan revisi wajib diisi supaya staf akademik tahu apa yang perlu diperbaiki.',
        ];
    }
}
