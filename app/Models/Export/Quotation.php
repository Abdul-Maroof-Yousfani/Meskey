<?php

namespace App\Models\Export;

use App\Models\Export\Currency;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Export\ModeOfTransport;
use App\Models\Master\Country;
use App\Models\Master\HsCode;
use App\Models\Master\Port;
use App\Models\Product;
use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
        'arrival_sub_location_ids' => 'array',
    ];

    public function buyer()
    {
        return $this->belongsTo(Customer::class, 'buyer_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function exportSoda()
    {
        return $this->belongsTo(\App\Models\Export\ExportSodaField::class, 'export_soda_id');
    }

    public function packingItems()
    {
        return $this->hasMany(QuotationPackingItem::class);
    }

    public function specifications()
    {
        return $this->hasMany(QuotationSpecification::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function incoterm()
    {
        return $this->belongsTo(IncoTerm::class, 'incoterm_id');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(ModeOfTerm::class, 'mode_of_term_id');
    }

    public function modeOfTransport()
    {
        return $this->belongsTo(ModeOfTransport::class, 'mode_of_transport_id');
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
}
