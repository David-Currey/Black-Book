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
}
