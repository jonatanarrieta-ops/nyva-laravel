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
     * @return BelongsTo<Establishment, FrequentlyAskedQuestion>
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
