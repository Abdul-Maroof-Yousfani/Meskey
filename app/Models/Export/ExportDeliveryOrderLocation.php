<?php
namespace App\Models\Export;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryOrderLocation extends Model
{
    use HasFactory;

    protected $table = 'export_delivery_order_locations';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, 'delivery_order_id');
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function arrivalLocations()
    {
        // This is a placeholder to prevent eager loading errors if someone tries to use it.
        // In this project's architecture, we handle comma-separated IDs manually.
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, 'arrival_location_ids');
    }

    public function subArrivalLocations()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class, 'sub_arrival_location_ids');
    }
}
