<?php

namespace App\Observers;

use App\Models\Acl\Company;
use Illuminate\Support\Str;

class CompanyObserver
{
    /**
     * Handle the "creating" event.
     */

    public function creating(Company $company)
    {
        $companyName = $company->name;
        $prefix = strtoupper(
            implode(
                '',
                array_map(function ($word) {
                    return strtoupper(substr($word, 0, 1)); // First letter of each word
                }, explode(' ', $companyName)),
            ),
        );

        $company->prefix = $prefix;

        $company->app_key = Str::random(32);

        if (request()->hasFile('logo')) {
            $company->logo = 'storage/' . $this->storeProfileImage(request()->file('logo'), $company);
        }
    }
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        //
    }

    /**
     * Handle the "updating" event.
     */
    public function saving(Company $company)
    {
        $companyName = $company->name;
        $prefix = strtoupper(
            implode(
                '',
                array_map(function ($word) {
                    return strtoupper(substr($word, 0, 1)); // First letter of each word
                }, explode(' ', $companyName)),
            ),
        );

        $company->prefix = $prefix;
        if (request()->hasFile('logo')) {
            $company->logo = 'storage/' . $this->storeProfileImage(request()->file('logo'), $company);
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }

    protected function storeProfileImage($image, Company $company)
    {

        $logoOriginalName = pathinfo($company->logo->getClientOriginalName(), PATHINFO_FILENAME);

        $sluggedName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $logoOriginalName));
        $filename = Str::slug($logoOriginalName) . '-' . now()->format('YmdHis') . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('company_logos', $filename, 'public');
    }
}
