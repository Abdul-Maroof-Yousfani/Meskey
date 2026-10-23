<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\CustomerCompanyBankDetail;
use App\Models\CustomerConsignee;
use App\Models\CustomerOwnerBankDetail;
use App\Models\Master\Account\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'unique_no',
        'name',
        'account_id',
        'company_name',
        'owner_name',
        'owner_mobile_no',
        'owner_cnic_no',
        'next_to_kin',
        'next_to_kin_mobile_no',
        'owner_bank_detail',
        'company_bank_detail',
        'prefix',
        'email',
        'phone',
        'address',
        'ntn',
        'stn',
        'attachment',
        'status',
        'company_location_ids',
        'nature',
    ];

    protected $casts = [
        'company_location_ids' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyBankDetails()
    {
        return $this->hasMany(CustomerCompanyBankDetail::class);
    }

    public function ownerBankDetails()
    {
        return $this->hasMany(CustomerOwnerBankDetail::class);
    }

    public function consignees()
    {
        return $this->hasMany(CustomerConsignee::class);
    }

    // public function arrivalPurchaseOrders()
    // {
    //     return $this->hasMany(ArrivalPurchaseOrder::class, 'supplier_id');
    // }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function broker()
    {
        return $this->hasOne(Broker::class, 'name', 'company_name');
    }

    public function scopeForUserLocation($query, $user)
    {
        $companyLocation = $user->companyLocation;
        $locationId = $companyLocation ? $companyLocation->id : 1;

        return $query->whereJsonContains('company_location_ids', $locationId);
    }

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
    }

    protected ?array $oldReferencesSnapshot = null;

    public function captureInitialAuditSnapshot(): void
    {
        $this->oldReferencesSnapshot = $this->buildDbReferencesSnapshot();
    }

    public function getOldReferencesSnapshot(): ?array
    {
        return $this->oldReferencesSnapshot;
    }

    public function buildDbReferencesSnapshot(): array
    {
        $this->loadMissing(['companyBankDetails', 'ownerBankDetails', 'consignees']);

        $compBanks = [];
        foreach ($this->companyBankDetails as $b) {
            $parts = array_filter([
                $b->bank_name,
                $b->account_title ? "Title: {$b->account_title}" : null,
                $b->account_number ? "A/C: {$b->account_number}" : null,
                $b->branch_name ? "Branch: {$b->branch_name}" : null,
                $b->branch_code ? "Code: {$b->branch_code}" : null,
            ]);
            $compBanks[] = implode(' | ', $parts);
        }

        $ownerBanks = [];
        foreach ($this->ownerBankDetails as $b) {
            $parts = array_filter([
                $b->bank_name,
                $b->account_title ? "Title: {$b->account_title}" : null,
                $b->account_number ? "A/C: {$b->account_number}" : null,
                $b->branch_name ? "Branch: {$b->branch_name}" : null,
                $b->branch_code ? "Code: {$b->branch_code}" : null,
            ]);
            $ownerBanks[] = implode(' | ', $parts);
        }

        $consignees = [];
        foreach ($this->consignees as $c) {
            $parts = array_filter([
                $c->name,
                $c->contact ? "Contact: {$c->contact}" : null,
                $c->contact_person ? "Person: {$c->contact_person}" : null,
                $c->email ? "Email: {$c->email}" : null,
                $c->address ? "Address: {$c->address}" : null,
            ]);
            $consignees[] = implode(' | ', $parts);
        }

        $locNames = [];
        if (!empty($this->company_location_ids) && is_array($this->company_location_ids)) {
            $locNames = \App\Models\Master\CompanyLocation::whereIn('id', $this->company_location_ids)->pluck('name')->toArray();
        }

        return [
            'company_locations'    => !empty($locNames) ? implode(', ', $locNames) : null,
            'company_bank_details' => !empty($compBanks) ? implode("\n", $compBanks) : null,
            'owner_bank_details'   => !empty($ownerBanks) ? implode("\n", $ownerBanks) : null,
            'consignees'           => !empty($consignees) ? implode("\n", $consignees) : null,
        ];
    }

    public function buildNewReferencesSnapshot(): array
    {
        $req = request();

        // Company Bank Details from request if present, else fallback to DB
        $compBanks = [];
        if ($req && $req->has('company_bank_name') && is_array($req->company_bank_name)) {
            foreach ($req->company_bank_name as $k => $bName) {
                if (empty($bName)) continue;
                $parts = array_filter([
                    $bName,
                    !empty($req->company_account_title[$k]) ? "Title: {$req->company_account_title[$k]}" : null,
                    !empty($req->company_account_number[$k]) ? "A/C: {$req->company_account_number[$k]}" : null,
                    !empty($req->company_branch_name[$k]) ? "Branch: {$req->company_branch_name[$k]}" : null,
                    !empty($req->company_branch_code[$k]) ? "Code: {$req->company_branch_code[$k]}" : null,
                ]);
                $compBanks[] = implode(' | ', $parts);
            }
        } else {
            $this->loadMissing('companyBankDetails');
            foreach ($this->companyBankDetails as $b) {
                $parts = array_filter([
                    $b->bank_name,
                    $b->account_title ? "Title: {$b->account_title}" : null,
                    $b->account_number ? "A/C: {$b->account_number}" : null,
                    $b->branch_name ? "Branch: {$b->branch_name}" : null,
                    $b->branch_code ? "Code: {$b->branch_code}" : null,
                ]);
                $compBanks[] = implode(' | ', $parts);
            }
        }

        // Owner Bank Details from request if present, else fallback to DB
        $ownerBanks = [];
        if ($req && $req->has('owner_bank_name') && is_array($req->owner_bank_name)) {
            foreach ($req->owner_bank_name as $k => $bName) {
                if (empty($bName)) continue;
                $parts = array_filter([
                    $bName,
                    !empty($req->owner_account_title[$k]) ? "Title: {$req->owner_account_title[$k]}" : null,
                    !empty($req->owner_account_number[$k]) ? "A/C: {$req->owner_account_number[$k]}" : null,
                    !empty($req->owner_branch_name[$k]) ? "Branch: {$req->owner_branch_name[$k]}" : null,
                    !empty($req->owner_branch_code[$k]) ? "Code: {$req->owner_branch_code[$k]}" : null,
                ]);
                $ownerBanks[] = implode(' | ', $parts);
            }
        } else {
            $this->loadMissing('ownerBankDetails');
            foreach ($this->ownerBankDetails as $b) {
                $parts = array_filter([
                    $b->bank_name,
                    $b->account_title ? "Title: {$b->account_title}" : null,
                    $b->account_number ? "A/C: {$b->account_number}" : null,
                    $b->branch_name ? "Branch: {$b->branch_name}" : null,
                    $b->branch_code ? "Code: {$b->branch_code}" : null,
                ]);
                $ownerBanks[] = implode(' | ', $parts);
            }
        }

        // Consignees from request if present, else fallback to DB
        $consignees = [];
        if ($req && $req->has('has_consignee') && $req->has_consignee && $req->has('consignee_name') && is_array($req->consignee_name)) {
            foreach ($req->consignee_name as $k => $cName) {
                if (empty($cName)) continue;
                $parts = array_filter([
                    $cName,
                    !empty($req->consignee_contact[$k]) ? "Contact: {$req->consignee_contact[$k]}" : null,
                    !empty($req->consignee_contact_person[$k]) ? "Person: {$req->consignee_contact_person[$k]}" : null,
                    !empty($req->consignee_email[$k]) ? "Email: {$req->consignee_email[$k]}" : null,
                    !empty($req->consignee_address[$k]) ? "Address: {$req->consignee_address[$k]}" : null,
                ]);
                $consignees[] = implode(' | ', $parts);
            }
        } elseif ($req && $req->has('has_consignee') && !$req->has_consignee) {
            $consignees = [];
        } else {
            $this->loadMissing('consignees');
            foreach ($this->consignees as $c) {
                $parts = array_filter([
                    $c->name,
                    $c->contact ? "Contact: {$c->contact}" : null,
                    $c->contact_person ? "Person: {$c->contact_person}" : null,
                    $c->email ? "Email: {$c->email}" : null,
                    $c->address ? "Address: {$c->address}" : null,
                ]);
                $consignees[] = implode(' | ', $parts);
            }
        }

        // Locations from request if present, else fallback to model attribute
        $locNames = [];
        if ($req && $req->has('company_location_ids')) {
            $locIds = (array) $req->company_location_ids;
            $locNames = \App\Models\Master\CompanyLocation::whereIn('id', $locIds)->pluck('name')->toArray();
        } elseif (!empty($this->company_location_ids) && is_array($this->company_location_ids)) {
            $locNames = \App\Models\Master\CompanyLocation::whereIn('id', $this->company_location_ids)->pluck('name')->toArray();
        }

        return [
            'company_locations'    => !empty($locNames) ? implode(', ', $locNames) : null,
            'company_bank_details' => !empty($compBanks) ? implode("\n", $compBanks) : null,
            'owner_bank_details'   => !empty($ownerBanks) ? implode("\n", $ownerBanks) : null,
            'consignees'           => !empty($consignees) ? implode("\n", $consignees) : null,
        ];
    }

    public function hasReferencesChanged(): bool
    {
        $old = $this->getOldReferencesSnapshot() ?? [];
        $new = $this->buildNewReferencesSnapshot();

        return ($old['company_bank_details'] ?? null) !== ($new['company_bank_details'] ?? null)
            || ($old['owner_bank_details'] ?? null) !== ($new['owner_bank_details'] ?? null)
            || ($old['consignees'] ?? null) !== ($new['consignees'] ?? null)
            || ($old['company_locations'] ?? null) !== ($new['company_locations'] ?? null);
    }

    public function auditIdentifier(): string
    {
        return (string) ($this->company_name ?: ($this->owner_name ?: ($this->unique_no ?: ($this->name ?: "#{$this->id}"))));
    }

    public function auditModelName(): string
    {
        return 'Customer';
    }

    public static function auditLogConfig(): array
    {
        return [
            'title' => 'Customer Audit Logs',
            'subtitle' => function ($model) {
                $parts = ["Customer: <strong>" . e($model->company_name ?: $model->name) . "</strong>"];
                if ($model->owner_name) $parts[] = 'Owner: ' . e($model->owner_name);
                if ($model->unique_no) $parts[] = 'Code: ' . e($model->unique_no);
                return implode(' | ', $parts);
            },
            'empty_message' => 'No changes or updates have been recorded for this customer yet.',
            'field_labels' => [
                'unique_no'             => 'Customer Code #',
                'type'                  => 'Customer Type',
                'company_name'          => 'Company Name',
                'owner_name'            => 'Owner Name',
                'owner_cnic_no'         => 'Owner CNIC',
                'owner_mobile_no'       => 'Owner Mobile No',
                'next_to_kin'           => 'Next of Kin',
                'next_to_kin_mobile_no' => 'Next of Kin Mobile',
                'email'                 => 'Email',
                'phone'                 => 'Phone',
                'address'               => 'Address',
                'ntn'                   => 'NTN #',
                'stn'                   => 'STN #',
                'status'                => 'Status',
                'account_id'            => 'COA Account',
                'company_locations'     => 'Assigned Locations',
                'company_bank_details'  => 'Company Bank Details',
                'owner_bank_details'    => 'Owner Bank Details',
                'consignees'            => 'Consignees',
            ],
            'skip_keys' => [
                'name', 'prefix', 'company_location_ids', 'nature', 'attachment',
            ],
            'relation_resolvers' => [
                'account_id' => function ($val) {
                    $acc = \App\Models\Master\Account\Account::find($val);
                    return $acc ? "{$acc->name} [{$acc->unique_no}]" : ($val ? "Account #{$val}" : '-');
                },
            ],
        ];
    }
}

