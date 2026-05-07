<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportLoadingSlipStack extends Model
{
    use HasFactory;

    protected $table = 'loading_slip_stacks';

    protected $fillable = [
        'loading_slip_id',
        'bag_type',
        'packing_size',
        'input_size',
    ];

    public function loadingSlip()
    {
        return $this->belongsTo(ExportLoadingSlip::class, 'loading_slip_id');
    }
}
