<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;

class Logistics extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'logistics';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function items()
    {
        return $this->hasMany(LogisticsItem::class, 'logistics_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(\App\Models\Sales\SalesOrder::class, 'sale_order_id');
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'approved']);
        }

        if (isset($this->am_change_made)) {
            $this->update(['am_change_made' => 1]);
        }

        // Auto-create transporters for items that don't have a transporter_id
        foreach ($this->items as $item) {
            if (!$item->transporter_id && $item->transporter_name) {
                // Check if a transporter with this name already exists (in case it was created by another row or approval)
                $transporter = \App\Models\Master\Transporter::where('company_name', $item->transporter_name)->first();

                if (!$transporter) {
                    // Determine company_id first
                    $companyId = $this->company_id 
                        ?? $this->saleOrder?->company_id 
                        ?? (auth()->check() ? auth()->user()->current_company_id : 1);

                    $uniqueNo = generateUniqueNumber('transporters', null, $companyId, 'unique_no');
                    
                    // Create Account in Chart of Accounts
                    $parentAccountPath = '2-5'; // Transporters path
                    $parentAccount = \App\Models\Master\Account\Account::where('hierarchy_path', $parentAccountPath)->first();
                    
                    $accountUniqueNo = generateUniqueNumber('accounts', 'ACC-', $companyId, 'unique_no');
                    
                    $childCount = \App\Models\Master\Account\Account::where('parent_id', $parentAccount?->id)->count();
                    $newHierarchyPath = $parentAccount ? ($parentAccountPath . '-' . ($childCount + 1)) : null;

                    $account = \App\Models\Master\Account\Account::create([
                        'name' => $item->transporter_name,
                        'company_id' => $companyId,
                        'unique_no' => $accountUniqueNo,
                        'account_type' => $parentAccount->account_type ?? 'debit',
                        'table_name' => 'transporters',
                        'is_operational' => 'yes',
                        'parent_id' => $parentAccount->id ?? NULL,
                        'parent_unique_no' => $parentAccount->unique_no ?? NULL,
                        'hierarchy_path' => $newHierarchyPath,
                        'status' => 'active',
                        'request_account_id' => 0
                    ]);
                    
                    // Create Transporter
                    $transporter = \App\Models\Master\Transporter::create([
                        'company_id' => $companyId,
                        'name' => $item->transporter_name,
                        'company_name' => $item->transporter_name,
                        'owner_name' => $item->transporter_name, // Default to name
                        'owner_mobile_no' => '', // Default to empty
                        'unique_no' => $uniqueNo,
                        'account_id' => $account->id,
                        'status' => 'active',
                        'type' => 'raw_material',
                    ]);
                }

                $item->update(['transporter_id' => $transporter->id]);
            }
        }
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
