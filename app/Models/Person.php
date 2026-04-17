<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
