<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KonfirmasiMahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'konfirmasi' => ['required', 'string', 'in:benar,salah'],
            'catatan' => [
                Rule::requiredIf(fn () => $this->input('konfirmasi') === 'salah'),
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'konfirmasi.required' => 'Konfirmasi wajib diisi.',
            'konfirmasi.in' => 'Konfirmasi harus berupa benar atau salah.',
            'catatan.required' => 'Catatan wajib diisi jika data salah.',
        ];
    }
}
