<?php

namespace App\Models\Procurement\Store;

use App\Services\StoreLedgerService;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    use HasFactory, HasApproval {
        onApprovalComplete as protected traitOnApprovalComplete;
    }

    protected $fillable = [
        'grn_id',
        'bill_id',
        'reference_number',
        'transaction_date',
        'created_by',
        'am_approval_status',
        'am_change_made',
    ];

    protected $table = "debit_notes";


    /** Ledger is posted only when the debit note is fully approved (no stock movement). */
    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(StoreLedgerService::class)->postDebitNote($this);
    }

    public function validateBeforeApproval(): ?string
    {
        return app(StoreLedgerService::class)->validate(fn () => app(StoreLedgerService::class)->buildDebitNoteEntries($this));
    }

    public function debit_note_data() {
        return $this->hasMany(DebitNoteData::class, "debit_note_id");
    }
        
    public function grn() {
        return $this->belongsTo(PurchaseOrderReceiving::class, "grn_id");
    }

    public function bill() {
        return $this->belongsTo(PurchaseBill::class, "bill_id");
    }

    public function created_by_user() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }
}
