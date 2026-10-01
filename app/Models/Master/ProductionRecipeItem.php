<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductionRecipeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_recipes_items';

    protected $fillable = [
        'company_id',
        'production_recipe_id',
        'production_attribute_id',
        'value',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = auth()->user()->id ?? null;
                $model->created_by = $userId;
                $model->updated_by = $userId;
                if (empty($model->company_id)) {
                    $model->company_id = Auth::user()->current_company_id ?? Auth::user()->company_id ?? null;
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = auth()->user()->id ?? null;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = auth()->user()->id ?? null;
                $model->save();
            }
        });
    }

    /**
     * Relationship with parent Production Recipe
     */
    public function recipe()
    {
        return $this->belongsTo(ProductionRecipe::class, 'production_recipe_id');
    }

    /**
     * Relationship with Production Attribute
     */
    public function attribute()
    {
        return $this->belongsTo(\App\Models\ProdctionAttribute::class, 'production_attribute_id');
    }

    /**
     * Accessor for attribute key
     */
    public function getKeyAttribute()
    {
        return $this->attribute->key ?? null;
    }

    /**
     * Accessor for attribute type
     */
    public function getTypeAttribute()
    {
        return $this->attribute->type ?? 'text';
    }

    /**
     * Accessor for attribute slug
     */
    public function getSlugAttribute()
    {
        return $this->attribute->slug ?? null;
    }

    /**
     * Relationship with Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relationship with Creator User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with Updater User
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
