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

    /**
     * Get list of fumigation types configured for production.
     *
     * @return array
     */
    public static function getFumigationTypes(): array
    {
        $setting = static::where('key', 'fumigation_types')
            ->where('group', 'production')
            ->first();

        if (!$setting) {
            return ['phosphorate', 'methale'];
        }

        $val = $setting->casted_value;
        if (is_array($val)) {
            $types = array_values(array_filter(array_map('trim', $val)));
            return !empty($types) ? $types : ['phosphorate', 'methale'];
        }

        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                $types = array_values(array_filter(array_map('trim', $decoded)));
                return !empty($types) ? $types : ['phosphorate', 'methale'];
            }
        }

        return ['phosphorate', 'methale'];
    }

    /**
     * Save/update list of fumigation types.
     *
     * @param array $types
     * @return void
     */
    public static function setFumigationTypes(array $types): void
    {
        $cleaned = array_values(array_unique(array_filter(array_map(function ($t) {
            return is_string($t) ? trim($t) : '';
        }, $types))));

        static::updateOrCreate(
            [
                'group' => 'production',
                'key'   => 'fumigation_types',
            ],
            [
                'type'  => 'json',
                'value' => json_encode($cleaned),
            ]
        );
    }
}
