<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    use HasFactory;

    protected $table = 'forms';

    protected $fillable = [
        'nama_form',
        'deskripsi',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')->orderBy('order_position');
    }

    public function yudisiumEvents(): HasMany
    {
        return $this->hasMany(YudisiumEvent::class, 'form_id');
    }
}
