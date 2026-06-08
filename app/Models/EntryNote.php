<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryNote extends Model
{
    protected $fillable = [
        'person_id',
        'occurred_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
