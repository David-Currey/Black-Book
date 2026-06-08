<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    public const STATUSES = [
        'neutral' => 'Neutral',
        'trusted' => 'Trusted',
        'watch' => 'Watch',
        'avoid' => 'Avoid',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'user_list_id',
        'name',
        'game',
        'status',
        'rating',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['neutral'];
    }

    /**
     * Get the list that this person belongs to
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(UserList::class, 'user_list_id');
    }

    /**
     * Get all tags attached to this person
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function timelineNotes(): HasMany
    {
        return $this->hasMany(EntryNote::class);
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(EntryCustomFieldValue::class);
    }
}
