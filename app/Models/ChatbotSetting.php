<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $establishment_id
 * @property bool $enabled
 * @property bool $orders_enabled
 * @property string|null $welcome_message
 * @property string|null $business_info
 */
class ChatbotSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'enabled',
        'orders_enabled',
        'welcome_message',
        'business_info',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'orders_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Establishment, $this>
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }
}
