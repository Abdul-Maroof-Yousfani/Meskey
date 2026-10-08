<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Acl\{Company};
use Illuminate\Http\{Request, JsonResponse};
use App\Http\Requests\Company\{StoreCompanyRequest, UpdateCompanyRequest};

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.acl.company.index');
    }
    public function getList(Request $request)
    {
        $companies = Company::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));
        return view('management.acl.company.getList', compact('companies'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.acl.company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
       // $data = $request->validated();
        $company = Company::create($request->all());

        // Attach default stock_check boolean setting
        $company->settings()->create([
            'key' => 'stock_check',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'inventory',
        ]);

        return response()->json(['success' => 'Company created successfully.', 'data' => $company], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $company = Company::with('settings')->findOrFail($id);
        return view('management.acl.company.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->all());

        if ($request->has('settings')) {
            $this->syncSettings($company, $request->input('settings', []));
        }

        if ($request->filled('deleted_settings')) {
            $deletedIds = array_filter(explode(',', (string) $request->input('deleted_settings')));
            if (!empty($deletedIds)) {
                $company->settings()->whereIn('id', $deletedIds)->delete();
            }
        }

        return response()->json(['success' => 'Company updated successfully.', 'data' => $company->load('settings')], 200);
    }

    /**
     * Sync settings associated with company.
     */
    protected function syncSettings(Company $company, array $settingsData): void
    {
        foreach ($settingsData as $item) {
            if (!isset($item['key']) || trim($item['key']) === '') {
                continue;
            }
            $key = trim($item['key']);
            $type = $item['type'] ?? 'string';
            $val = $item['value'] ?? null;
            if ($type === 'boolean') {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            if (!empty($item['id'])) {
                $setting = $company->settings()->find($item['id']);
                if ($setting) {
                    $setting->update([
                        'key' => $key,
                        'type' => $type,
                        'value' => $val,
                        'group' => $item['group'] ?? null,
                    ]);
                    continue;
                }
            }

            $company->settings()->updateOrCreate(
                ['key' => $key],
                [
                    'type' => $type,
                    'value' => $val,
                    'group' => $item['group'] ?? null,
                ]
            );
        }
    }

    /**
     * Store a single setting via AJAX.
     */
    public function storeSetting(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:150',
            'type' => 'required|string|in:string,integer,float,boolean,json,datetime',
            'value' => 'nullable',
            'group' => 'nullable|string|max:100',
        ]);

        $val = $request->value;
        if ($request->type === 'boolean') {
            $val = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        $setting = $company->settings()->updateOrCreate(
            ['key' => trim($request->key)],
            [
                'type' => $request->type,
                'value' => $val,
                'group' => $request->group,
            ]
        );

        return response()->json([
            'success' => 'Setting saved successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * Update a single setting via AJAX.
     */
    public function updateSetting(Request $request, Company $company, Setting $setting): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:150',
            'type' => 'required|string|in:string,integer,float,boolean,json,datetime',
            'value' => 'nullable',
            'group' => 'nullable|string|max:100',
        ]);

        $val = $request->value;
        if ($request->type === 'boolean') {
            $val = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        $setting->update([
            'key' => trim($request->key),
            'type' => $request->type,
            'value' => $val,
            'group' => $request->group,
        ]);

        return response()->json([
            'success' => 'Setting updated successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * Delete a single setting via AJAX.
     */
    public function destroySetting(Company $company, Setting $setting): JsonResponse
    {
        $setting->delete();
        return response()->json(['success' => 'Setting deleted successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();
        return response()->json(['success' => 'Company deleted successfully.'], 200);
    }
    public function selectCompany(Request $request, $key = null)
    {
        if ($key) {
            $company = Company::where('app_key', $key)->firstOrFail();
            auth()
                ->user()
                ->update(['current_company_id' => $company->id]);
            return redirect('/');
        }
        $companies = Company::get();
        return view('management.acl.company.selectCompany', compact('companies'));
    }
}
