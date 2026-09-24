<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_field_id' => ['required', 'integer', 'exists:form_fields,id'],
            'value' => ['required', 'string'],
        ];
    }
}