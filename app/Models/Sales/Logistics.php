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

    /**
     * Cached old audit snapshot of items.
     */
    protected ?array $auditOldSnapshot = null;

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);

        static::retrieved(function ($model) {
            if (!request() || !request()->isMethodSafe() || app()->runningInConsole()) {
                $model->captureInitialAuditSnapshot();
            }
        });

        static::saving(function ($model) {
            if ($model->exists && method_exists($model, 'hasReferencesChanged') && $model->hasReferencesChanged()) {
                $model->updated_at = now();
            }
        });

        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Logistics record is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && !in_array($newStatus, ['pending', 'reverted'])) {
                    throw new \Exception("Logistics record is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->getOriginal('am_approval_status') ?? $model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Logistics record is already {$status} and cannot be deleted.");
            }
        });
    }

    public function items()
    {
        return $this->hasMany(LogisticsItem::class, 'logistics_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(\App\Models\Sales\SalesOrder::class, 'sale_order_id');
    }

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
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


    /**
     * Identifier for audit logs.
     */
    public function auditIdentifier(): string
    {
        return (string) ($this->so_no ?: "#{$this->id}");
    }

    /**
     * Human-readable model display name for audit logs.
     */
    public function auditModelName(): string
    {
        return 'Logistics';
    }

    /**
     * Action prefix for audit logs.
     */
    public function auditActionPrefix(): string
    {
        return 'logistics';
    }

    /**
     * Configuration for audit logs modal rendering.
     */
    public static function auditLogConfig(): array
    {
        return [
            'field_labels' => [
                'type'               => 'Logistics Type',
                'date'               => 'Date',
                'so_no'              => 'SO / Contract No',
                'loading_request'    => 'Loading Request',
                'so_qty'             => 'SO Quantity',
                'commodity'          => 'Commodity',
                'sauda_type'         => 'Sauda Type',
                'customer'           => 'Customer',
                'location'           => 'From Location / Mill',
                'to_location'        => 'To Location / Port',
                'delivery_address'   => 'Delivery Address',
                'factory'            => 'Factory',
                'job_order'          => 'Job Order',
                'return_port'        => 'Return Port',
                'booking_no'         => 'Booking No',
                'shipping_line'      => 'Shipping Line',
                'am_approval_status' => 'Approval Status',
                'items_transporter'  => 'Transporter(s)',
                'items_rate'         => 'Rate(s)',
                'items_rate_type'    => 'Rate Type(s)',
                'items_qty'          => 'Quantity(ies)',
                'items_brand'        => 'Brand(s)',
                'items_packing'      => 'Packing Size(s)',
                'total_item_qty'     => 'Total Item Quantity',
                'items_count'        => 'Items Count',
            ],
            'skip_keys' => [
                'company_id',
                'created_by',
                'am_change_made',
                'sale_order_id',
                'export_order_id',
                'deleted_at',
            ],
            'relation_resolvers' => [
                'location' => function ($val, $key) {
                    if (is_numeric($val)) {
                        $loc = \App\Models\Master\CompanyLocation::find($val);
                        return $loc ? $loc->name : "Location #{$val}";
                    }
                    return $val;
                },
                'to_location' => function ($val, $key) {
                    if (is_numeric($val)) {
                        $port = \App\Models\Master\Port::find($val);
                        if ($port) return $port->name;
                        $loc = \App\Models\Master\CompanyLocation::find($val);
                        return $loc ? $loc->name : "Location #{$val}";
                    }
                    return $val;
                },
                'am_approval_status' => function ($val) {
                    return ucwords(str_replace('_', ' ', (string) $val));
                },
                'type' => function ($val) {
                    return ucwords(str_replace('_', ' ', (string) $val));
                },
                'so_qty' => function ($val) {
                    return is_numeric($val) ? number_format((float) $val, 2) : $val;
                },
                'total_item_qty' => function ($val) {
                    return is_numeric($val) ? number_format((float) $val, 2) : $val;
                },
            ],
            'subtitle' => function ($model) {
                $parts = ['SO #' . ($model->so_no ?? $model->id)];
                if (!empty($model->customer)) {
                    $parts[] = 'Customer: ' . $model->customer;
                }
                if (!empty($model->commodity)) {
                    $parts[] = 'Commodity: ' . $model->commodity;
                }
                if (!empty($model->so_qty)) {
                    $parts[] = 'Qty: ' . number_format((float) $model->so_qty, 2);
                }
                if (!empty($model->date)) {
                    $parts[] = 'Date: ' . (\Carbon\Carbon::parse($model->date)->format('d M Y'));
                }
                if (!empty($model->am_approval_status)) {
                    $parts[] = 'Status: ' . ucfirst($model->am_approval_status);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No edits or updates have been recorded for this logistics record yet.',
        ];
    }

    /**
     * Capture initial snapshot of items for audit logging before any deletes/updates.
     */
    public function captureInitialAuditSnapshot(): void
    {
        if ($this->auditOldSnapshot !== null || !$this->exists) {
            return;
        }
        $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
    }

    /**
     * Get the cached old snapshot of items.
     */
    public function getOldReferencesSnapshot(): ?array
    {
        if ($this->auditOldSnapshot === null && $this->exists) {
            $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
        }
        return $this->auditOldSnapshot;
    }

    /**
     * Build items snapshot from current database state.
     */
    public function buildCurrentReferencesSnapshot(): array
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('transporter')->get();

        $transporters = [];
        $rates = [];
        $rateTypes = [];
        $qtys = [];
        $brands = [];
        $packings = [];
        $totalQty = 0;

        foreach ($items as $item) {
            $tName = $item->transporter_name ?: ($item->transporter?->company_name ?? $item->transporter?->name);
            if ($tName) {
                $transporters[] = $tName;
            }
            if ($item->rate !== null && $item->rate !== '') {
                $rates[] = number_format((float) $item->rate, 2);
            }
            if ($item->rate_type) {
                $rateTypes[] = $item->rate_type;
            }
            if ($item->qty !== null && $item->qty !== '') {
                $q = (float) $item->qty;
                $qtys[] = number_format($q, 2);
                $totalQty += $q;
            }
            if ($item->brand) {
                $brands[] = trim($item->brand);
            }
            if ($item->packing_size) {
                $packings[] = trim($item->packing_size);
            }
        }

        return [
            'items_transporter' => !empty($transporters) ? implode(', ', $transporters) : null,
            'items_rate'        => !empty($rates) ? implode(', ', $rates) : null,
            'items_rate_type'   => !empty($rateTypes) ? implode(', ', $rateTypes) : null,
            'items_qty'         => !empty($qtys) ? implode(', ', $qtys) : null,
            'items_brand'       => !empty($brands) ? implode(', ', $brands) : null,
            'items_packing'     => !empty($packings) ? implode(', ', $packings) : null,
            'total_item_qty'    => $totalQty > 0 ? (float) $totalQty : null,
            'items_count'       => count($items),
        ];
    }

    /**
     * Build items snapshot from incoming request payload.
     */
    public function buildNewReferencesSnapshot(): array
    {
        $req = request();
        if ($req && $req->has('items') && is_array($req->items)) {
            $transporters = [];
            $rates = [];
            $rateTypes = [];
            $qtys = [];
            $brands = [];
            $packings = [];
            $totalQty = 0;

            foreach ($req->items as $item) {
                if (empty($item['transporter']) && empty($item['qty']) && empty($item['rate'])) {
                    continue;
                }
                $tRaw = $item['transporter'] ?? '';
                $tName = $tRaw;
                if (is_numeric($tRaw)) {
                    $trans = \App\Models\Master\Transporter::find($tRaw);
                    if ($trans) {
                        $tName = $trans->company_name ?: $trans->name;
                    }
                }
                if ($tName) {
                    $transporters[] = $tName;
                }
                if (isset($item['rate']) && $item['rate'] !== '') {
                    $rates[] = number_format((float) $item['rate'], 2);
                }
                if (!empty($item['rate_type'])) {
                    $rateTypes[] = $item['rate_type'];
                }
                if (isset($item['qty']) && $item['qty'] !== '') {
                    $q = (float) $item['qty'];
                    $qtys[] = number_format($q, 2);
                    $totalQty += $q;
                }
                if (!empty($item['brand'])) {
                    $brands[] = trim($item['brand']);
                }
                if (!empty($item['packing_size'])) {
                    $packings[] = trim($item['packing_size']);
                }
            }

            return [
                'items_transporter' => !empty($transporters) ? implode(', ', $transporters) : null,
                'items_rate'        => !empty($rates) ? implode(', ', $rates) : null,
                'items_rate_type'   => !empty($rateTypes) ? implode(', ', $rateTypes) : null,
                'items_qty'         => !empty($qtys) ? implode(', ', $qtys) : null,
                'items_brand'       => !empty($brands) ? implode(', ', $brands) : null,
                'items_packing'     => !empty($packings) ? implode(', ', $packings) : null,
                'total_item_qty'    => $totalQty > 0 ? (float) $totalQty : null,
                'items_count'       => count($transporters),
            ];
        }

        return $this->buildCurrentReferencesSnapshot();
    }

    /**
     * Check if child items snapshot changed between old and new state.
     */
    public function hasReferencesChanged(): bool
    {
        $old = $this->getOldReferencesSnapshot() ?? [];
        $new = $this->buildNewReferencesSnapshot();

        if (empty($old) && empty($new)) {
            return false;
        }

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($allKeys as $k) {
            $o = $old[$k] ?? null;
            $n = $new[$k] ?? null;
            if ($o != $n) {
                return true;
            }
        }
        return false;
    }
}
