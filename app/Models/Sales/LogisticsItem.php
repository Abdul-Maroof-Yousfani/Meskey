<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsItem extends Model
{
    use HasFactory;

    protected $table = 'logistics_items';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function logistics()
    {
        return $this->belongsTo(Logistics::class, 'logistics_id');
    }
}
