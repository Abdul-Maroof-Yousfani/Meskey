<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQcAttachment extends Model
{
    use HasFactory;

    protected $table = 'sales_qc_attachments';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function salesQc()
    {
        return $this->belongsTo(SalesQc::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
