<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory;

    protected $table = 'form_fields';

    protected $fillable = [
        'form_id',
        'field_name',
        'field_desc',
        'data_type',
        'options',
        'is_required',
        'is_visible',
        'order_position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'order_position' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(PengajuanFieldValue::class, 'form_field_id');
    }
}
