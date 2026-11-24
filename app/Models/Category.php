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

    /**
     * Top-level (main) category of this category, e.g. Irri -> Rice, Sella -> Rice.
     * A category with parent_id null or 0 is its own main category.
     */
    public function rootCategory(): self
    {
        $category = $this;
        $seen = [$category->id];

        while ($category->parent_id && !in_array($category->parent_id, $seen)) {
            $parent = $category->parent;
            if (!$parent) {
                break;
            }
            $seen[] = $parent->id;
            $category = $parent;
        }

        return $category;
    }

    // Relationship: Category Heads (Blueprint templates)
    public function heads()
    {
        return $this->hasMany(CategoryHead::class, 'category_id')->orderBy('sort_order');
    }
}