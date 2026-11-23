<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
/**
 * Class ProductionRecipesItem
 * Alias for ProductionRecipeItem
 */
class ProductionRecipesItem extends ProductionRecipeItem
{
    use Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    // Inherits everything from ProductionRecipeItem
}
