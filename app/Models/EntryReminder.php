<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryReminder extends Model
{
    protected $fillable = [
        'person_id',
        'remind_on',
        'note',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'remind_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->completed_at === null
            && $this->remind_on->isPast()
            && ! $this->remind_on->isToday();
    }
}
