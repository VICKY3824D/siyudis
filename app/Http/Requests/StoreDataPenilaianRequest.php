<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataPenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ipk' => ['required', 'numeric', 'min:0', 'max:4'],
            'persen_nilai_d' => ['required', 'numeric', 'min:0', 'max:100'],
            'sks_ditempuh' => ['required', 'integer', 'min:0'],
            'similarity_index' => ['required', 'numeric', 'min:0', 'max:100'],
            'skor_bahasa_inggris' => ['required', 'integer', 'min:0'],
            'sertifikat_level' => ['nullable', 'string', 'max:255'],
            'status_judul_pa' => ['required', 'string', 'in:disetujui,tidak_disetujui'],
            'sertifikat_kompetensi' => ['nullable', 'string', 'max:255'],
            'status_bebas_pelanggaran' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ipk.required' => 'IPK wajib diisi.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.min' => 'IPK tidak boleh kurang dari :min.',
            'ipk.max' => 'IPK tidak boleh lebih dari :max.',

            'persen_nilai_d.required' => 'Persentase nilai D wajib diisi.',
            'persen_nilai_d.numeric' => 'Persentase nilai D harus berupa angka.',
            'persen_nilai_d.min' => 'Persentase nilai D tidak boleh kurang dari :min.',
            'persen_nilai_d.max' => 'Persentase nilai D tidak boleh lebih dari :max.',

            'sks_ditempuh.required' => 'SKS ditempuh wajib diisi.',
            'sks_ditempuh.integer' => 'SKS ditempuh harus berupa bilangan bulat.',
            'sks_ditempuh.min' => 'SKS ditempuh tidak boleh kurang dari :min.',

            'similarity_index.required' => 'Similarity index wajib diisi.',
            'similarity_index.numeric' => 'Similarity index harus berupa angka.',
            'similarity_index.min' => 'Similarity index tidak boleh kurang dari :min.',
            'similarity_index.max' => 'Similarity index tidak boleh lebih dari :max.',

            'skor_bahasa_inggris.required' => 'Skor bahasa Inggris wajib diisi.',
            'skor_bahasa_inggris.integer' => 'Skor bahasa Inggris harus berupa bilangan bulat.',
            'skor_bahasa_inggris.min' => 'Skor bahasa Inggris tidak boleh kurang dari :min.',

            'status_judul_pa.required' => 'Status judul PA wajib diisi.',
            'status_judul_pa.in' => 'Status judul PA harus berupa disetujui atau tidak_disetujui.',

            'status_bebas_pelanggaran.required' => 'Status bebas pelanggaran wajib diisi.',
            'status_bebas_pelanggaran.boolean' => 'Status bebas pelanggaran harus berupa true atau false.',
        ];
    }
}
