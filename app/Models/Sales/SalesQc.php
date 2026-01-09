<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQc extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'sales_qc';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function loadingProgramItem()
    {
        return $this->belongsTo(LoadingProgramItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }


    public function attachments()
    {
        return $this->hasMany(SalesQcAttachment::class);
    }
}
