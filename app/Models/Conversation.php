<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'establishment_id',
        'customer_name',
        'message',
        'response',
        'status',
    ];

    /**
     * Relationship:
     * A conversation belongs to an establishment.
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
