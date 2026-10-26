<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

     protected $fillable = [
        'company_id',
        'account_id',
        'account_title',
        'bank_name',
        'branch',
        'iban',
        'account_no',
        'swift_code',
        'bank_address',
        'description',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class, 'company_id');
    }

    public function account()
    {
        return $this->belongsTo(\App\Models\Master\Account\Account::class, 'account_id');
    }

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);
    }

    public function auditIdentifier(): string
    {
        $name = $this->bank_name ?: ($this->account_title ?: "#{$this->id}");
        return $this->branch ? "{$name} ({$this->branch})" : (string) $name;
    }

    public function auditModelName(): string
    {
        return 'Bank';
    }

    public static function auditLogConfig(): array
    {
        return [
            'title' => 'Bank Account Audit Logs',
            'subtitle' => function ($model) {
                $parts = ["Bank: <strong>" . e($model->bank_name) . "</strong>" . ($model->branch ? " (" . e($model->branch) . ")" : "")];
                if ($model->account_title) $parts[] = 'Title: ' . e($model->account_title);
                if ($model->account_no) $parts[] = 'A/C: ' . e($model->account_no);
                return implode(' | ', $parts);
            },
            'empty_message' => 'No changes or updates have been recorded for this bank account yet.',
            'field_labels' => [
                'company_id'    => 'Company',
                'account_id'    => 'COA Account',
                'account_title' => 'Account Title',
                'bank_name'     => 'Bank Name',
                'branch'        => 'Branch',
                'iban'          => 'IBAN',
                'account_no'    => 'Account No',
                'swift_code'    => 'Swift Code',
                'bank_address'  => 'Bank Address',
                'description'   => 'Description',
                'status'        => 'Status',
            ],
            'relation_resolvers' => [
                'account_id' => function ($val) {
                    $acc = \App\Models\Master\Account\Account::find($val);
                    return $acc ? "{$acc->name} [{$acc->unique_no}]" : ($val ? "Account #{$val}" : '-');
                },
                'company_id' => function ($val) {
                    $comp = \App\Models\Acl\Company::find($val);
                    return $comp ? $comp->name : ($val ? "Company #{$val}" : '-');
                },
            ],
        ];
    }
}
