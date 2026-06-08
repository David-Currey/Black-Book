<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListShare extends Model
{
    public const ROLES = [
        'viewer' => 'Viewer',
        'editor' => 'Editor',
    ];

    protected $fillable = [
        'user_list_id',
        'user_id',
        'role',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(UserList::class, 'user_list_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
