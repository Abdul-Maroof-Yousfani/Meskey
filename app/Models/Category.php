<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
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
}