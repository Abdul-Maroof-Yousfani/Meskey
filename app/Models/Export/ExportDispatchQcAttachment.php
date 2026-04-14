<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDispatchQcAttachment extends Model
{
    use HasFactory;

    protected $table = 'dispatch_qc_attachment';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function dispatchQc()
    {
        return $this->belongsTo(ExportDispatchQc::class, 'dispatch_qc_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
