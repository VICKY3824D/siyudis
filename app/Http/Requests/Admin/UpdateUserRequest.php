<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['mahasiswa', 'staf_akademik', 'kaprodi', 'manit', 'kadep', 'super_admin'])],
            'program_studi_id' => ['nullable', 'exists:program_studi,id'],
        ];
    }
}