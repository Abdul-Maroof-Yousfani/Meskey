<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Master\Port;

class DocumentList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'feature',
        'port_id',
        'is_required',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}
