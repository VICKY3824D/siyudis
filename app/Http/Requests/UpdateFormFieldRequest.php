<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_name'     => 'sometimes|required|string|max:255',
            'field_desc'     => 'nullable|string',
            'data_type'      => 'sometimes|required|in:freetext,pdf,link,image,number,single_option',
            'options'        => 'nullable|array',
            'is_required'    => 'boolean',
            'is_visible'     => 'boolean',
            'order_position' => 'nullable|integer',
        ];
    }

    /**
     * Custom pesan validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            // field_name
            'field_name.required' => 'Nama field wajib diisi.',
            'field_name.string'   => 'Nama field harus berupa teks.',
            'field_name.max'      => 'Nama field tidak boleh lebih dari :max karakter.',

            // field_desc
            'field_desc.string'   => 'Deskripsi field harus berupa teks.',

            // data_type
            'data_type.required'  => 'Tipe data wajib dipilih.',
            'data_type.in'        => 'Tipe data yang dipilih tidak valid. Pilihan yang tersedia: freetext, pdf, link, image, number, single_option.',

            // options
            'options.array'       => 'Options harus berupa array.',

            // is_required
            'is_required.boolean' => 'Status wajib isi harus berupa true atau false.',

            // is_visible
            'is_visible.boolean'  => 'Status tampil harus berupa true atau false.',

            // order_position
            'order_position.integer' => 'Posisi urutan harus berupa angka bulat.',
        ];
    }

    /**
     * Custom nama atribut agar pesan lebih rapi.
     */
    public function attributes(): array
    {
        return [
            'field_name'     => 'nama field',
            'field_desc'     => 'deskripsi field',
            'data_type'      => 'tipe data',
            'options'        => 'options',
            'is_required'    => 'wajib isi',
            'is_visible'     => 'status tampil',
            'order_position' => 'posisi urutan',
        ];
    }
}
