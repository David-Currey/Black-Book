<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserList extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the list.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all people that belong to this list
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ListCustomField::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ListShare::class);
    }

    public function canBeViewedBy(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->shares()->where('user_id', $user->id)->exists();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->shares()
                ->where('user_id', $user->id)
                ->where('role', 'editor')
                ->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
