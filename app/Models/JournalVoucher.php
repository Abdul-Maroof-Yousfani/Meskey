<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    protected $fillable = [
        'jv_date',
        'jv_no',
        'description',
        'username',
        'status',
        'jv_status',
        'am_approval_status',
        'am_change_made',
        'created_by',
        'approve_user_id',
        'delete_user_id',
        'company_id'
    ];

    protected $casts = [
        'jv_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->am_approval_status)) {
                $model->am_approval_status = 'pending';
            }
            if (!isset($model->am_change_made)) {
                $model->am_change_made = 1;
            }
        });

        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Journal Voucher is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && !in_array($newStatus, ['pending', 'reverted'])) {
                    throw new \Exception("Journal Voucher is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->getOriginal('am_approval_status') ?? $model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Journal Voucher is already {$status} and cannot be deleted.");
            }
        });
    }

    public function journalVoucherDetails()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'journal_voucher_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approveUser()
    {
        return $this->belongsTo(User::class, 'approve_user_id');
    }

    public function deleteUser()
    {
        return $this->belongsTo(User::class, 'delete_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();

        $updateData = [
            'am_change_made' => 1,
            'jv_status' => 'approved',
            'approve_user_id' => optional(auth()->user())->id,
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'approved';
        }
        $this->update($updateData);

        // Post ledger transactions if they do not already exist
        $voucherType = \App\Models\Master\Account\TransactionVoucherType::where('code', 'JV')->first();
        if ($voucherType) {
            $existingTx = \App\Models\Master\Account\Transaction::where('purpose', 'like', "journal-voucher-{$this->id}-%")
                ->exists();

            if (!$existingTx) {
                foreach ($this->journalVoucherDetails as $detail) {
                    if ($detail->debit_amount > 0) {
                        createTransaction(
                            (float) $detail->debit_amount,
                            $detail->acc_id,
                            $voucherType->id,
                            $this->jv_no,
                            'debit',
                            'no',
                            [
                                'reference_no' => $detail->voucher_no ?? '',
                                'purpose' => "journal-voucher-{$this->id}-{$this->jv_no}",
                                'remarks' => $detail->description ?? ($this->description ?? "Journal entry for {$this->jv_no}"),
                                'voucher_date' => $this->jv_date ? $this->jv_date->format('Y-m-d') : now()->format('Y-m-d')
                            ]
                        );
                    }

                    if ($detail->credit_amount > 0) {
                        createTransaction(
                            (float) $detail->credit_amount,
                            $detail->acc_id,
                            $voucherType->id,
                            $this->jv_no,
                            'credit',
                            'no',
                            [
                                'purpose' => "journal-voucher-{$this->id}-{$this->jv_no}",
                                'remarks' => $detail->description ?? ($this->description ?? "Journal entry for {$this->jv_no}"),
                                'voucher_date' => $this->jv_date ? $this->jv_date->format('Y-m-d') : now()->format('Y-m-d')
                            ]
                        );
                    }
                }
            }
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
            'jv_status' => 'rejected'
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'rejected';
        }
        $this->update($updateData);
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
            'jv_status' => 'reverted'
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'reverted';
        }
        $this->update($updateData);
    }
}
