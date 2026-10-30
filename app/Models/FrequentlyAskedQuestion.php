<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequentlyAskedQuestion extends Model
{
    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'establishment_id',
        'question',
        'answer',
        'status',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    protected function casts(): array
    {
        return [
        ];
    }
}

