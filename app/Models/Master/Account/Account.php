<?php

namespace App\Models\Master\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_no',
        'company_id',
        'name',
        'description',
        'account_type',
        'parent_id',
        'parent_unique_no',
        'hierarchy_path',
        'is_operational',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_operational' => 'string',
        'status' => 'string',
        'account_type' => 'string'
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }




    public static function getTree()
    {
        $accounts = Account::with('children')
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return self::buildTree($accounts);
    }

    protected static function buildTree($accounts, $prefix = '')
    {
        $tree = collect();

        foreach ($accounts as $account) {
            $account->name = $prefix . $account->name;
            $tree->push($account);

            if ($account->children->isNotEmpty()) {
                $tree = $tree->merge(
                    self::buildTree($account->children, $prefix . '--')
                );
            }
        }

        return $tree;
    }






    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
