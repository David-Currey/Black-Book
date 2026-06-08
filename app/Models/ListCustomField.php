<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListCustomField extends Model
{
    protected $fillable = [
        'user_list_id',
        'name',
        'sort_order',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(UserList::class, 'user_list_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(EntryCustomFieldValue::class);
    }
}
