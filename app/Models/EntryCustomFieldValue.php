<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryCustomFieldValue extends Model
{
    protected $fillable = [
        'person_id',
        'list_custom_field_id',
        'value',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ListCustomField::class, 'list_custom_field_id');
    }
}
