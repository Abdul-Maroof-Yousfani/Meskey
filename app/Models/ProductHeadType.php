<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Models\Master\ProductHeadType as BaseProductHeadType;

class ProductHeadType extends BaseProductHeadType
{
    use Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

}
