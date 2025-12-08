<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QCItems extends Model
{
    use HasFactory;
    protected $table = "qc_items";
    protected $guarded = ["id", "created_at", "updated_at"];
}
