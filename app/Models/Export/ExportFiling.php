<?php

namespace App\Models\Export;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportFiling extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'export_order_id',
        'remarks',
        'created_by',
    ];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }

    public function documents()
    {
        return $this->hasMany(ExportFilingDocument::class, 'export_filing_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
