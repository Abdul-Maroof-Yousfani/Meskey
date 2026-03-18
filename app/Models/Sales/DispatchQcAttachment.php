<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchQcAttachment extends Model
{
    use HasFactory;

    protected $table = 'dispatch_qc_attachment';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function dispatchQc()
    {
        return $this->belongsTo(DispatchQc::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
