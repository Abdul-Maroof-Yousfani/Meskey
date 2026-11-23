<?php
namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'company_id',
        'parent_id',
        'category_type',
        'name',
        'is_protected'
    ];

    // Relationship: Parent Category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relationship: Child Categories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Relationship: Category Heads (Blueprint templates)
    public function heads()
    {
        return $this->hasMany(CategoryHead::class, 'category_id')->orderBy('sort_order');
    }
}