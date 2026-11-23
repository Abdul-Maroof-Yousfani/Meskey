<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryHead extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = 'category_heads';

    protected $fillable = [
        'company_id',
        'category_id',
        'head_type_slug',
        'name',
        'sort_order',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function headType()
    {
        return $this->belongsTo(ProductHeadType::class, 'head_type_slug', 'slug');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
