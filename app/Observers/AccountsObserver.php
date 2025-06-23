<?php

namespace App\Observers;

use App\Models\Master\Account\Account;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountsObserver
{
    /**
     * Handle the Account "creating" event.
     */
    public function creating(Account $account): void
    {
        $account->unique_no = generateUniqueNumber('accounts', 'ACC-', null, 'unique_no');

        $this->setHierarchyPath($account);

        if ($account->parent_id) {
            $parentAccount = Account::find($account->parent_id);
            if ($parentAccount) {
                $account->parent_unique_no = $parentAccount->unique_no;
            }
        }

        if (empty($account->company_id)) {
            $account->company_id = request()->company_id;
        }
    }

    /**
     * Set hierarchy path for the account
     */
    protected function setHierarchyPath(Account $account): void
    {
        if ($account->parent_id) {
            $parentAccount = Account::find($account->parent_id);

            if ($parentAccount) {
                $siblingsCount = Account::where('parent_id', $parentAccount->id)->count();
                $childNumber = $siblingsCount + 1;

                if (!empty($parentAccount->hierarchy_path)) {
                    $account->hierarchy_path = $parentAccount->hierarchy_path . '-' . $childNumber;
                } else {
                    $account->hierarchy_path = $parentAccount->id . '-' . $childNumber;
                }
            }
        } else {
            $latestRootAccount = Account::whereNull('parent_id')->orderByDesc('id')->first();
            $account->hierarchy_path = $latestRootAccount ? (string)($latestRootAccount->id + 1) : '1';
        }
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        // if (empty($account->parent_id)) {
        //     $account->update(['hierarchy_path' => (string)$account->id]);
        // }
    }

    /**
     * Handle the Account "updating" event.
     */
    public function updating(Account $account): void
    {
        if ($account->isDirty('parent_id')) {
            $this->setHierarchyPath($account);

            if ($account->parent_id) {
                $parentAccount = Account::find($account->parent_id);
                if ($parentAccount) {
                    $account->parent_unique_no = $parentAccount->unique_no;
                }
            } else {
                $account->parent_unique_no = null;
            }
        }
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void {}
}
