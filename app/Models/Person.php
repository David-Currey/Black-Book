<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $fillable = [
        'user_list_id',
        'name',
        'game',
        'notes',
    ];

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

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(EntryCustomFieldValue::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EntryReminder::class);
    }
}
