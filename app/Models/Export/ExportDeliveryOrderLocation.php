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

    // Since arrival and sub-arrival locations are stored as comma-separated strings
    // we can add accessors if needed, but for now we'll handle them in the controller/view.
}
