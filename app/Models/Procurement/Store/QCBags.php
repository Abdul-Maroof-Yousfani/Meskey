<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QCBags extends Model
{
    use HasFactory;
    protected $table = "qc_bags";
    protected $guarded = ["id", "created_at", "updated_at"];
}
