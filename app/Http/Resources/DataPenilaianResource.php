<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataPenilaianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pengajuan_id' => $this->pengajuan_id,
            'ipk' => (float) $this->ipk,
            'persen_nilai_d' => (float) $this->persen_nilai_d,
            'sks_ditempuh' => $this->sks_ditempuh,
            'similarity_index' => (float) $this->similarity_index,
            'skor_bahasa_inggris' => $this->skor_bahasa_inggris,
            'sertifikat_level' => $this->sertifikat_level,
            'status_judul_pa' => $this->status_judul_pa,
            'sertifikat_kompetensi' => $this->sertifikat_kompetensi,
            'status_bebas_pelanggaran' => $this->status_bebas_pelanggaran,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
