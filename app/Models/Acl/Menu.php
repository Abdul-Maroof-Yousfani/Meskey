<?php

namespace App\Models\Acl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'icon',
        'name',
        'route',
        'permission_id',
        'creator_id',
        'status',
    ];

    /**
     * Get the parent menu.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child menus.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the permission associated with the menu.
     */
public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the creator of the menu.
     */
    public function creator()
    {
        return $this->belongsTo(User::class);
    }
}
