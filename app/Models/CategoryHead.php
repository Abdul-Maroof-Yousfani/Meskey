<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Models\Master\CategoryHead as BaseCategoryHead;

class CategoryHead extends BaseCategoryHead
{
    use Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

}
