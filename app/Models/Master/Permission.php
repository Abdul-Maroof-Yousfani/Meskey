<?php

namespace App\Models\Master;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'parent_id',
        'description',
    ];

    /**
     * Parent permission relationship.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child permissions relationship.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope for searching permissions by name or description.
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $term = '%' . trim($search) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('description', 'like', $term);
        });
    }

    /**
     * Recursively fetch all descendant IDs for a given permission.
     */
    public static function getDescendantIds($id, $all = null)
    {
        if ($all === null) {
            $all = self::all();
        }

        $descendants = [];
        $children = $all->where('parent_id', (int) $id);

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, self::getDescendantIds($child->id, $all));
        }

        return $descendants;
    }

    /**
     * Get hierarchical tree options for parent selection dropdown.
     */
    public static function getHierarchicalOptions($excludeId = null)
    {
        $all = self::orderBy('name')->get();

        $excludedIds = [];
        if ($excludeId) {
            $excludedIds = self::getDescendantIds($excludeId, $all);
            $excludedIds[] = (int) $excludeId;
        }

        $filtered = $all->reject(function ($item) use ($excludedIds) {
            return in_array($item->id, $excludedIds);
        });

        $groupedByParent = $filtered->groupBy(function ($item) {
            return (string) ($item->parent_id ?? 'root');
        });

        $options = [];
        $buildTree = function ($parentId, $prefix = '') use (&$buildTree, &$options, $groupedByParent) {
            $children = $groupedByParent->get((string) $parentId, collect());
            foreach ($children as $child) {
                $displayName = $prefix ? ($prefix . ' > ' . $child->name) : $child->name;
                $options[$child->id] = $displayName;
                $buildTree($child->id, $displayName);
            }
        };

        $buildTree('root', '');

        return $options;
    }
}
