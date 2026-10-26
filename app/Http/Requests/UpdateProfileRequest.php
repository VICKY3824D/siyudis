<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        $avatarRules = $this->hasFile('avatar')
            ? ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048']
            : ['nullable', 'string', 'max:2048'];

        return [
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'nomor_induk' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users', 'nomor_induk')->ignore($userId)],
            'program_studi_id' => ['sometimes', 'nullable', 'integer', Rule::exists('program_studi', 'id')],
            'avatar' => $avatarRules,
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama lengkap',
            'nomor_induk' => 'nomor induk mahasiswa / pegawai',
            'program_studi_id' => 'program studi',
            'avatar' => 'foto profil',
        ];
    }
}
