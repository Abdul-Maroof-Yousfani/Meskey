<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ExportOrderAddendum extends Model
{
    use HasFactory;

    protected $table = 'export_order_addendums';

    protected $guarded = [];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
