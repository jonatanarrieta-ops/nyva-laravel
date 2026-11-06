<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $establishment_id
 * @property string|null $token
 * @property string $customer_identifier
 * @property string $status
 * @property array<string, mixed>|null $draft
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $ended_at
 * @property CarbonInterface|null $last_activity_at
 */
class Conversation extends Model
{
    public const STATUS_LABELS = [
        'active' => 'Activa',
        'closed' => 'Cerrada',
    ];

    public $timestamps = false;

    protected $fillable = [
        'establishment_id',
        'token',
        'customer_identifier',
        'started_at',
        'ended_at',
        'status',
        'draft',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'draft' => 'array',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * @return BelongsTo<Establishment, $this>
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    /**
     * @return HasMany<ConversationMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }
}
