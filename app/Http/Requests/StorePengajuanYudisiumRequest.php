<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanYudisiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasi struktural saja (bentuk payload). Validasi bisnis per field
     * (wajib/tipe data sesuai konfigurasi form) dilakukan di controller
     * karena field-nya dinamis, tergantung form yang aktif.
     */
    public function rules(): array
    {
        return [
            'yudisium_event_id' => ['required', 'integer', 'exists:yudisium_events,id'],
            'field_values' => ['required', 'array', 'min:1'],
            'field_values.*.form_field_id' => ['required', 'integer', 'exists:form_fields,id'],
            'field_values.*.value' => ['nullable', 'string'],
        ];
    }
}
