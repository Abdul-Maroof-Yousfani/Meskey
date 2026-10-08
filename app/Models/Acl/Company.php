<?php

namespace App\Models\Acl;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'prefix', 'email', 'phone', 'address', 'registration_no', 'logo', 'connection_database', 'app_key', 'status', 'ntn', 'stn'];


    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user_role')
            ->withPivot('role_id', 'locations', 'arrival_locations')
            ->withTimestamps();
    }

    public function settings()
    {
        return $this->morphMany(\App\Models\Setting::class, 'settable');
    }

    /**
     * Get typed setting value by key.
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->relationLoaded('settings')
            ? $this->settings->firstWhere('key', $key)
            : $this->settings()->where('key', $key)->first();

        return $setting ? $setting->casted_value : $default;
    }

    /**
     * Set setting value.
     */
    public function setSetting(string $key, $value, string $type = 'string', ?string $group = null)
    {
        $valString = $type === 'boolean'
            ? (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
            : (is_array($value) ? json_encode($value) : (string) $value);

        return $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $valString,
                'type' => $type,
                'group' => $group,
            ]
        );
    }
}
