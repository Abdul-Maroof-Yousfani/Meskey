<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Master\ProductionMachine;

class ProductionMachineAnalysis extends Model
{
    use HasFactory;

    protected $table = 'production_machine_analysis';

    protected $fillable = [
        'analysis_date',
        'company_location_id',
        'arrival_location_id',
        'plant_id',
        'production_machine_id',
        'remarks',
        'created_by'
    ];

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class, 'production_machine_id');
    }

    public function items()
    {
        return $this->hasMany(ProductionMachineAnalysisItem::class, 'machine_analysis_id');
    }
}
