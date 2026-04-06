<?php

namespace App\Models\Export;

use App\Models\Acl\Company;
use App\Models\ExportOrderSpecification;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CustomerOwnerBankDetail;
use App\Models\CustomerCompanyBankDetail;
use App\Models\Country;
use App\Models\Master\Port;
use App\Models\Master\HsCode;

class ExportOrder extends Model
{
    use HasApproval, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
        'arrival_sub_location_ids' => 'array',
        'voucher_date' => 'date',
        'shipment_delivery_date_from' => 'date',
        'shipment_delivery_date_to' => 'date',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== 'am_change_made') {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (! empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function specifications()
    {
        return $this->hasMany(ExportOrderSpecification::class);
    }

    public function exportSoda()
    {
        return $this->belongsTo(ExportSodaField::class, 'export_soda_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function packingItems()
    {
        return $this->hasMany(ExportOrderPackingItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(ModeOfTerm::class, 'mode_of_term_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function proforma()
    {
        return $this->hasOne(Proforma::class);
    }

    // Auto calculate total fields
    // public function getTotalBagsAttribute()
    // {
    //     return $this->packingItems->sum('total_bags');
    // }

    // public function getTotalKgsAttribute()
    // {
    //     return $this->packingItems->sum('total_kgs');
    // }

    // public function getTotalMetricTonsAttribute()
    // {
    //     return $this->packingItems->sum('metric_tons');
    // }

    // public function getTotalContainersAttribute()
    // {
    //     return $this->packingItems->sum('no_of_containers');
    // }

    //   // Get all company locations from packing items (sorted and unique)
    //   public function getCompanyLocationsAttribute()
    //   {
    //       return CompanyLocation::whereIn('id', $this->packingItems->pluck('company_location_id')->unique()->toArray())
    //           ->orderBy('name')
    //           ->get();
    //   }

    //   // Get company locations as comma separated string
    //   public function getCompanyLocationsStringAttribute()
    //   {
    //     // company_locations_string
    //       $locations = $this->company_locations;
    //       return $locations->pluck('name')->implode(', ');
    //   }
    public function getCustomerBankAttribute()
    {
        if ($this->customer_bank_type === 'owner') {
            return CustomerOwnerBankDetail::find($this->customer_bank_id);
        } elseif ($this->customer_bank_type === 'company') {
            return CustomerCompanyBankDetail::find($this->customer_bank_id);
        }

        return null;
    }

    public function correspondentBank()
    {
        return $this->belongsTo(Bank::class, 'correspondent_bank_id');
    }

    public function originCountry()
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }

    public function portOfLoading()
    {
        return $this->belongsTo(Port::class, 'port_of_loading_id');
    }

    public function portOfDischarge()
    {
        return $this->belongsTo(Port::class, 'port_of_discharge_id');
    }

    public function modeOfTransport()
    {
        return $this->belongsTo(ModeOfTransport::class, 'mode_of_transport_id');
    }

    public function hsCode()
    {
        return $this->belongsTo(HsCode::class, 'hs_code_id');
    }

    public function incoterm()
    {
        return $this->belongsTo(IncoTerm::class, 'incoterm_id');
    }
}
