<?php

namespace App\Models\Master;

use App\Models\Master\Account\Account;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ArrivalLocation extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'company_id',
        'company_location_id',
        'name',
        'description',
        'status',
    ];

    // Relationship with the Company model (if you have one)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function assetAccount()
    {
        return $this->hasOne(Account::class, 'model_id')
            ->where('table_name', 'arrival_locations')
            ->where('hierarchy_path', 'like', '1-7-%');
    }

    public function revenueAccount()
    {
        return $this->hasOne(Account::class, 'model_id')
            ->where('table_name', 'arrival_locations')
            ->where('hierarchy_path', 'like', '4-4-%');
    }

    public function getOrCreateAssetAccount($companyId = null)
    {
        $expectedName = trim($this->name) . ' Weighbridge';

        $account = $this->assetAccount;
        if ($account) {
            if ($account->name !== $expectedName) {
                $account->update(['name' => $expectedName]);
            }
            return $account;
        }

        $account = Account::where('hierarchy_path', 'like', '1-7-%')
            ->where(function ($q) use ($expectedName) {
                $q->where('name', $expectedName)
                  ->orWhere('name', trim($this->name));
            })->first();

        if ($account) {
            $account->update([
                'table_name' => 'arrival_locations',
                'model_id' => $this->id,
                'name' => $expectedName
            ]);
            return $account;
        }

        $parent = Account::where('hierarchy_path', '1-7')->first();
        if ($parent) {
            $params = getParamsForAccountCreationByPath(
                $companyId ?? $this->company_id ?? 1,
                $expectedName,
                '1-7',
                'arrival_locations'
            );
            $account = Account::create($params);
            $account->update(['model_id' => $this->id]);
            return $account;
        }

        return null;
    }

    public function getOrCreateRevenueAccount($companyId = null)
    {
        $expectedName = trim($this->name) . ' Weighbridge';

        $account = $this->revenueAccount;
        if ($account) {
            if ($account->name !== $expectedName) {
                $account->update(['name' => $expectedName]);
            }
            return $account;
        }

        $account = Account::where('hierarchy_path', 'like', '4-4-%')
            ->where(function ($q) use ($expectedName) {
                $q->where('name', $expectedName)
                  ->orWhere('name', trim($this->name));
            })->first();

        if ($account) {
            $account->update([
                'table_name' => 'arrival_locations',
                'model_id' => $this->id,
                'name' => $expectedName
            ]);
            return $account;
        }

        $parent = Account::where('hierarchy_path', '4-4')->first();
        if ($parent) {
            $params = getParamsForAccountCreationByPath(
                $companyId ?? $this->company_id ?? 1,
                $expectedName,
                '4-4',
                'arrival_locations'
            );
            $account = Account::create($params);
            $account->update(['model_id' => $this->id]);
            return $account;
        }

        return null;
    }
}
