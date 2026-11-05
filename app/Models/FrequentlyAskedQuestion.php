<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $establishment_id
 * @property string $question
 * @property string $answer
 * @property string $status
 */
class FrequentlyAskedQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'establishment_id',
        'question',
        'answer',
        'status',
    ];

    /**
     * @return BelongsTo<Establishment, $this>
     */
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

