<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProductionRecipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_recipes';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'crop_year_id',
        'commodity_id',
        'status',
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
            $model->items()->delete();
        });
    }

    /**
     * Relationship with Recipe Items (Parameters)
     */
    public function items()
    {
        return $this->hasMany(ProductionRecipeItem::class, 'production_recipe_id');
    }

    /**
     * Alias for items
     */
    public function recipeItems()
    {
        return $this->hasMany(ProductionRecipeItem::class, 'production_recipe_id');
    }

    /**
     * Relationship with Commodity (Product)
     */
    public function commodity()
    {
        return $this->belongsTo(Product::class, 'commodity_id');
    }

    /**
     * Relationship with Crop Year
     */
    public function cropYear()
    {
        return $this->belongsTo(CropYear::class, 'crop_year_id');
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
