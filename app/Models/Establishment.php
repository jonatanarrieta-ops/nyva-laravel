<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establishment extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'description',
        'address',
        'phone',
        'email',
        'status',
        'registered_at',
    ];


    /**
     * Relationship:
     * An establishment belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Relationship:
     * An establishment can have many subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }


    /**
     * Relationship:
     * An establishment can have many categories.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }


    /**
     * Relationship:
     * An establishment can have many schedules.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }


    /**
     * Relationship:
     * An establishment can have many orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }


    /**
     * Relationship:
     * An establishment can have many frequently asked questions.
     */
    public function frequentlyAskedQuestions(): HasMany
    {
        return $this->hasMany(FrequentlyAskedQuestion::class);
    }


    /**
     * Relationship:
     * An establishment can have many conversations.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }


    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }
}