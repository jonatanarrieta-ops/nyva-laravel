<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'establishment_id',
        'day',
        'opening_time',
        'closing_time',
        'status',
    ];


    /**
     * Relationship:
     * A schedule belongs to an establishment.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }


    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'status' => 'boolean',
        ];
    }
}