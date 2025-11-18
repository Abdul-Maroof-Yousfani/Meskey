<?php

namespace App\Models\Procurement\Store;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QC extends Model
{
    use HasFactory, HasApproval;
    protected $table = "qc";
    protected $guarded = ["id", "created", "updated_at"];

    public function bags() {
        return $this->hasMany(QCBags::class, "qc_id");
    }

    public function scopeFilter($query)
    {
        if (!$this->canApprove()) {
            return $query->where('is_qc_approved', 'pending');
        }

        return $query;
    }

}

