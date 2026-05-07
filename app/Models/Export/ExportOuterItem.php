<?php

namespace App\Models\Export;

use App\Models\Sales\LoadingProgramItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportOuterItem extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function loadingProgramItem()
    {
        return $this->belongsTo(LoadingProgramItem::class, 'loading_program_item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
