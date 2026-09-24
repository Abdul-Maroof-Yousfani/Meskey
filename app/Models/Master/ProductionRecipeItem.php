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
        'key',
        'slug',
        'type',
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

            // Auto-generate unique slug on create
            if (empty($model->slug)) {
                $baseText = !empty($model->key) ? $model->key : 'param';
                $baseSlug = Str::slug($baseText, '_');
                if (empty($baseSlug)) {
                    $baseSlug = 'param';
                }

                $slug = $baseSlug;
                $count = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}_{$count}";
                    $count++;
                }
                $model->slug = $slug;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = auth()->user()->id ?? null;
            }

            // If slug became empty, re-generate uniquely
            if (empty($model->slug)) {
                $baseText = !empty($model->key) ? $model->key : 'param';
                $baseSlug = Str::slug($baseText, '_');
                if (empty($baseSlug)) {
                    $baseSlug = 'param';
                }

                $slug = $baseSlug;
                $count = 1;
                while (static::withTrashed()->where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                    $slug = "{$baseSlug}_{$count}";
                    $count++;
                }
                $model->slug = $slug;
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
