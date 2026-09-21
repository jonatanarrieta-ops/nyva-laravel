<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequentlyAskedQuestion extends Model
{
    protected $fillable = [
        'establishment_id',
        'question',
        'answer',
        'status',
    ];


    /**
     * Relationship:
     * A frequently asked question belongs to an establishment.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }


    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}