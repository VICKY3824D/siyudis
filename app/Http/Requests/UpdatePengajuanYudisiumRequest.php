<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengajuanYudisiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasi struktural saja — validasi bisnis per field (wajib/tipe data)
     * tetap dilakukan di controller karena field-nya dinamis.
     */
    public function rules(): array
    {
        return [
            'field_values' => ['required', 'array', 'min:1'],
            'field_values.*.form_field_id' => ['required', 'integer', 'exists:form_fields,id'],
            'field_values.*.value' => ['nullable', 'string'],
        ];
    }
}
