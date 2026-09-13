<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_form' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ];
    }

    /**
     * Custom pesan validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            // Pesan untuk field nama_form
            'nama_form.required' => 'Nama form wajib diisi.',
            'nama_form.string'   => 'Nama form harus berupa teks.',
            'nama_form.max'      => 'Nama form tidak boleh lebih dari :max karakter.',

            // Pesan untuk field deskripsi
            'deskripsi.string'   => 'Deskripsi harus berupa teks.',
        ];
    }

    /**
     * Custom nama atribut (opsional, agar pesan lebih rapi).
     */
    public function attributes(): array
    {
        return [
            'nama_form' => 'nama form',
            'deskripsi' => 'deskripsi',
        ];
    }
}
