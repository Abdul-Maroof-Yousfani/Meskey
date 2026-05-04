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
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    protected $guarded = [];

    protected $casts = [
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
        'arrival_sub_location_ids' => 'array',
        'commission_percentage' => 'float',
        'commission_amount_per_ton' => 'float',
        'commission' => 'float',
        'total_amount' => 'float',
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

    /**
     * Override createApprovalRows for conditional approval
     */
    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return;
        }

        $currentCycle = $this->getCurrentApprovalCycle();
        $hasSauda = !empty($this->export_soda_id);

        foreach ($module->roles as $moduleRole) {
            $includeRole = false;

            if (empty($moduleRole->condition)) {
                $includeRole = true; // Always include if no condition
            } elseif ($moduleRole->condition === 'with_sauda' && $hasSauda) {
                $includeRole = true;
            } elseif ($moduleRole->condition === 'without_sauda' && !$hasSauda) {
                $includeRole = true;
            }

            if ($includeRole) {
                \App\Models\ApprovalsModule\ApprovalRow::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'record_id' => $this->id,
                        'role_id' => $moduleRole->role_id,
                        'approval_cycle' => $currentCycle,
                    ],
                    [
                        'required_count' => $moduleRole->approval_count,
                        'current_count' => 0,
                        'status' => 'pending'
                    ]
                );
            }
        }
    }
}
