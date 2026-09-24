<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'role' => [
                'id' => $this->role_id,
                'name' => $this->whenLoaded('role', fn () => $this->role->name),
            ],
            'program_studi_id' => $this->program_studi_id,
            'created_at' => $this->created_at,
        ];
    }
}