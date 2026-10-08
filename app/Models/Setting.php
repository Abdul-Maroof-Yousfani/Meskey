<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'settable_type',
        'settable_id',
        'group',
        'key',
        'value',
        'type',
    ];

    /**
     * Polymorphic parent relation.
     * Note: Named 'entity' / 'owner' because 'settable()' conflicts with Model::setTable() in PHP.
     */
    public function entity()
    {
        return $this->morphTo('settable');
    }

    public function owner()
    {
        return $this->morphTo('settable');
    }

    /**
     * Get casted value according to its type.
     */
    public function getCastedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
