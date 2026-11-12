<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreLedgerService;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PurchaseReturn extends Model
{
    use HasFactory;
    protected $table = "purchase_returns";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];
    use HasApproval {
        onApprovalComplete as protected traitOnApprovalComplete;
    }

    /** Stock-out and ledger are posted only when the return is fully approved. */
    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(StoreLedgerService::class)->postPurchaseReturn($this);
    }

    public function validateBeforeApproval(): ?string
    {
        return app(StoreLedgerService::class)->validate(fn () => app(StoreLedgerService::class)->buildPurchaseReturnEntries($this));
    }


    public function supplier() 
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchase_return_data() {
        return $this->hasMany(PurchaseReturnData::class, 'purchase_return_id');
    }

    public function purchaseBills() {
        return $this->belongsToMany(PurchaseBill::class, 'purchase_bill_purchase_return', 'purchase_return_id', 'purchase_bill_id');
    }
    public function purchase_bill() {
        return $this->belongsToMany(PurchaseBill::class, 'purchase_bill_purchase_return', 'purchase_return_id', 'purchase_bill_id');
    }
    

    public function company_location() {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function created_by_user() {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function item() {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
