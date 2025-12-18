<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserTestStoreRequest;
use App\Http\Requests\User\UserTestUpdateRequest;
use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:user-list', ['only' => ['index']]);
        $this->middleware('check.company:user-list', ['only' => ['getTable']]);
        $this->middleware('check.company:user-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:user-delete', ['only' => ['destroy']]);
        $this->middleware('check.company:assign-company', ['only' => ['assign']]);
        $this->middleware('check.company:edit-assign-company', ['only' => ['edit-assign']]);
        $this->middleware('check.company:delete-assign-company', ['only' => ['delete-assign']]);
    }

    public function index(Request $request): View
    {
        $data = User::latest()->paginate(5);

        return view('management.acl.users-test.index', compact('data'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        // $roles = Role::all();
        // $locations = CompanyLocation::all();
        $users = User::
            // where('id', '!=', auth()->id())
            //     // ->where('user_type', '!=', 'super-admin')
            //     ->
            get();

        return view('management.acl.users-test.create', compact('users'));
    }

    public function store(UserTestStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'success' => 'Basic details saved.',
            'user_id' => $user->username,
            'redirect' => route('users-test.edit', $user->id),
        ]);
    }

    public function getCompanyLocations($companyId)
    {
        $locations = CompanyLocation::where('company_id', $companyId)->with('arrivalLocations')->get();

        return response()->json($locations);
    }

    public function getArrivalLocations($companyLocationId)
    {
        $locations = ArrivalLocation::where('company_location_id', $companyLocationId)
            ->with('companyLocation')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'location_name' => $item->companyLocation->name ?? '',
                ];
            });

        // dd($locations);

        return response()->json($locations);
    }

    public function checkUsernameAvailability(Request $request)
    {
        $username = $request->input('username');
        $userId = $request->input('user_id');

        $query = User::where('username', $username);

        if ($userId) {
            $query->where('id', '!=', $userId);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => ! $exists,
            'username' => $username,
        ]);
    }

    public function show($id): View
    {
        $user = User::find($id);

        return view('management.acl.users-test.show', compact('user'));
    }

    public function edit($id): View
    {
        $user = User::with(['companies', 'roles', 'companyLocation.arrivalLocations'])->findOrFail($id);
        $users = User::where('id', '!=', $id)->get();
        $userRole = $user->roles->pluck('id', 'name')->all();

        return view('management.acl.users-test.edit', compact('user', 'userRole', 'users'));
    }

    public function update(UserTestUpdateRequest $request, $id): JsonResponse
    {
        try {
            $input = $request->all();

            if (! empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                $input = Arr::except($input, ['password']);
            }

            $user = User::findOrFail($id);

            if ($user->username !== $input['username']) {
                $usernameExists = User::where('username', $input['username'])
                    ->where('id', '!=', $id)
                    ->exists();

                if ($usernameExists) {
                    return response()->json([
                        'error' => 'Username is already taken',
                        'errors' => ['username' => ['This username is already in use']],
                    ], 422);
                }
            }
            $user->update($input);

            return response()->json([
                'success' => 'Successfully Saved.',
                'data' => $user,
                'username' => $user->username,
                // 'redirect' => route('users-test.assign', $user->id),
            ]);
        } catch (Exception $e) {
            Log::error('User update failed: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $id,
                'input' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to update user. Please try again.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function assignDetails($id)
    {
        $user = User::with('companies')->findOrFail($id);

        $permission = Permission::get();
        $assignedCompanyIds = $user->companies->pluck('id')->toArray();
        $companies = Company::whereNotIn('id', $assignedCompanyIds)->get();

        return view('management.acl.users-test.assign', compact(
            'user',
            'permission',
            'companies',
        ));
    }

    public function saveAssignDetails(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $userType = $user->user_type == 'user';

        $rules = [
            'company' => 'required|array',
            'company.*' => 'exists:companies,id',
            'permission' => 'required|array',
        ];

        if ($userType) {
            $rules['company_location_id'] = 'required|array';
            $rules['company_location_id.*'] = 'array';
            $rules['company_location_id.*.*'] = 'exists:company_locations,id';
        } else {
            $rules['company_location_id'] = 'nullable|array';
            $rules['company_location_id.*'] = 'nullable|array';
            $rules['company_location_id.*.*'] = 'nullable|exists:company_locations,id';
        }

        $rules['arrival_location_id'] = 'nullable|array';

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            // Check if permission array exists
            $permissions = $request->permission ?? [];

            // If any permission is selected, dashboard must be present
            if (! empty($permissions) && ! in_array('dashboard', $permissions)) {
                $validator->errors()->add('permission', 'Dashboard permission is required.');
            }
        });

        $validator->validate(); // Throws validation exception if fails

        // $request->validate($rules);

        $permissions = $request->permission ?? [];
        $existingCompanyLocationIds = $user->company_location_ids ?? [];
        $existingArrivalLocationIds = $user->arrival_location_ids ?? [];

        $newCompanyLocationIds = [];
        $newArrivalLocationMap = []; // map instead of flat array

        $roleNames = [];

        foreach ($request->company as $i => $companyId) {

            $company = Company::findOrFail($companyId);
            $prefix = substr(strtoupper($company->name), 0, 3);

            $autoRoleName = $user->username.'-company'.$prefix.$companyId;

            $role = Role::firstOrCreate(['name' => $autoRoleName]);

            // Sync permissions to role
            $role->syncPermissions($permissions);
            $roleNames[] = $autoRoleName;

            // $locations = $request->company_location_id[$i] ?? [];
            // $arrivalData = [];

            // $newCompanyLocationIds = array_merge($newCompanyLocationIds, $locations);

            // foreach ($locations as $locId) {

            //     $locArrivals = [];

            //     if (! empty($request->arrival_location_id[$locId])) {
            //         foreach ($request->arrival_location_id[$locId] as $arrId) {
            //             $arrivalData[] = $arrId;
            //             $locArrivals[] = (int) $arrId;
            //         }
            //     }

            //     if (! empty($locArrivals)) {
            //         $newArrivalLocationMap[$locId] = $locArrivals;
            //     }
            // }

            $locations = $request->company_location_id[$i] ?? [];
            $locations = is_array($locations) ? $locations : [];

            $arrivals = $request->arrival_location_id ?? [];
            $arrivals = is_array($arrivals) ? $arrivals : [];

            $newCompanyLocationIds = array_merge($newCompanyLocationIds, $locations);

            $arrivalData = [];

            foreach ($locations as $locId) {

                // Filter arrivals belonging to this location
                $locArrivals = ArrivalLocation::whereIn('id', $arrivals)
                    ->where('company_location_id', $locId)
                    ->pluck('id')
                    ->toArray();

                if (! empty($locArrivals)) {

                    // Add in final mapping output
                    $newArrivalLocationMap[$locId] = $locArrivals;

                    // Add to flat list for db pairing
                    foreach ($locArrivals as $arrId) {
                        $arrivalData[] = $arrId;
                    }
                }
            }

            $user->companies()->syncWithoutDetaching([
                $companyId => [
                    'role_id' => $role->id,
                    'locations' => json_encode($locations),
                    'arrival_locations' => json_encode($arrivalData),
                ],
            ]);
        }

        $finalCompanyLocationIds = array_values(array_unique(array_merge(
            $existingCompanyLocationIds,
            $newCompanyLocationIds
        )));

        $finalArrivalLocationMap = $existingArrivalLocationIds;

        foreach ($newArrivalLocationMap as $locId => $arrList) {
            $old = $finalArrivalLocationMap[$locId] ?? [];
            $finalArrivalLocationMap[$locId] = array_values(array_unique(array_merge($old, $arrList)));
        }

        $user->update([
            'company_location_ids' => $finalCompanyLocationIds,
            'arrival_location_ids' => $finalArrivalLocationMap,
        ]);

        $user->syncRoles(array_unique(array_merge(
            $user->getRoleNames()->toArray(),
            $roleNames
        )));

        return response()->json(['success' => 'User assignments saved successfully.']);
    }

    public function editAssignDetails($userId, $companyId, $roleId)
    {
        $user = User::with('companies')->findOrFail($userId);

        $permission = Permission::get();
        $locations = CompanyLocation::all();
        $companies = getAllCompanies();

        $selectedLocations = [];
        $selectedArrivals = [];

        $company = $user->companies->where('id', $companyId)->first();

        if ($company && ! empty($company->pivot->locations)) {
            $selectedLocations = json_decode($company->pivot->locations, true) ?? [];
        }

        if ($company && ! empty($company->pivot->arrival_locations)) {
            $selectedArrivals = json_decode($company->pivot->arrival_locations, true) ?? [];
        }

        $assignedPermissions = Role::find($roleId)?->permissions->pluck('name')->toArray() ?? [];

        return view('management.acl.users-test.edit-assign', compact(
            'user',
            'permission',
            'locations',
            'companies',
            'selectedLocations',
            'selectedArrivals',
            'assignedPermissions',
            'companyId',
            'roleId'
        ));
    }

    public function updateAssignDetails(Request $request, $userId, $companyId, $roleId)
    {
        $user = User::findOrFail($userId);

        $userType = $user->user_type == 'user';

        $rules = [
            'permission' => 'required|array',
        ];

        if ($userType) {
            $rules['company_location_id'] = 'required|array';
            $rules['company_location_id.*'] = 'exists:company_locations,id';
        } else {
            $rules['company_location_id'] = 'nullable|array';
            $rules['company_location_id.*'] = 'nullable|exists:company_locations,id';
        }

        $rules['arrival_location_id'] = 'nullable|array';

        // $request->validate($rules);

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            // Check if permission array exists
            $permissions = $request->permission ?? [];

            // If any permission is selected, dashboard must be present
            if (! empty($permissions) && ! in_array('dashboard', $permissions)) {
                $validator->errors()->add('permission', 'Dashboard permission is required.');
            }
        });

        $validator->validate(); // Throws validation exception if fails

        $permissions = $request->permission ?? [];
        $locations = $request->company_location_id ?? [];

        $arrivals = $request->arrival_location_id ?? [];
        $arrivals = is_array($arrivals) ? $arrivals : [];

        $flatArrivalData = [];
        $mapArrivalData = [];

        foreach ($locations as $locId) {
            $locArrivals = ArrivalLocation::whereIn('id', $arrivals)
                ->where('company_location_id', $locId)
                ->pluck('id')
                ->toArray();

            if (! empty($locArrivals)) {
                $flatArrivalData = array_merge($flatArrivalData, $locArrivals);
                $mapArrivalData[$locId] = $locArrivals;
            }
        }

        // foreach ($locations as $locId) {
        //     $mapArrivalData[$locId] = [];

        //     if (! empty($request->arrival_location_id[$locId])) {
        //         foreach ($request->arrival_location_id[$locId] as $arrId) {

        //             // For pivot (flat)
        //             $flatArrivalData[] = $arrId;

        //             // For user table (map)
        //             $mapArrivalData[$locId][] = $arrId;
        //         }
        //     }
        // }

        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissions);

        DB::table('company_user_role')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->update([
                'role_id' => $roleId,
                'locations' => json_encode($locations),
                'arrival_locations' => json_encode($flatArrivalData),
                'updated_at' => now(),
            ]);

        $finalCompanyLocationIds = [];
        $finalArrivalMap = [];

        $allCompanies = $user->companies()->get();

        foreach ($allCompanies as $comp) {

            if (! empty($comp->pivot->locations)) {
                $locs = json_decode($comp->pivot->locations, true) ?? [];

                foreach ($locs as $locId) {
                    $finalCompanyLocationIds[] = $locId;
                }
            }

            if (! empty($comp->pivot->arrival_locations)) {

                if ($comp->id == $companyId) {
                    foreach ($mapArrivalData as $locId => $arrs) {
                        $finalArrivalMap[$locId] = $arrs;
                    }
                }
            }
        }

        $existingArrivalMap = $user->arrival_location_ids ?? [];

        $finalArrivalMap = array_merge($existingArrivalMap, $finalArrivalMap);

        $user->update([
            'company_location_ids' => array_values(array_unique($finalCompanyLocationIds)),
            'arrival_location_ids' => $finalArrivalMap,
        ]);

        return response()->json(['success' => 'Company assignment updated successfully.']);
    }

    public function deleteAssignDetails($userId, $companyId, $roleId)
    {
        $user = User::findOrFail($userId);

        $pivot = DB::table('company_user_role')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('role_id', $roleId)
            ->first();

        $deleteLocations = [];
        $deleteArrivalsFlat = [];

        if ($pivot) {
            $deleteLocations = json_decode($pivot->locations, true) ?? [];
            $deleteArrivalsFlat = json_decode($pivot->arrival_locations, true) ?? [];
        }

        $user->companies()
            ->wherePivot('role_id', $roleId)
            ->detach($companyId);

        // Delete role completely
        $role = Role::find($roleId);
        if ($role) {
            $role->syncPermissions([]); // Optional: remove all permissions
            $role->delete();
        }

        $userLocations = $user->company_location_ids ?? [];
        $userArrivalMap = $user->arrival_location_ids ?? []; // NOW MAP

        // Remove deleted locations
        $updatedLocations = array_values(array_diff($userLocations, $deleteLocations));

        // Remove deleted arrivals by location
        foreach ($deleteLocations as $locId) {
            if (isset($userArrivalMap[$locId])) {
                unset($userArrivalMap[$locId]);
            }
        }

        foreach ($userArrivalMap as $locId => $arrs) {
            $userArrivalMap[$locId] = array_values(array_diff($arrs, $deleteArrivalsFlat));

            if (empty($userArrivalMap[$locId])) {
                unset($userArrivalMap[$locId]);
            }
        }

        $user->update([
            'company_location_ids' => $updatedLocations,
            'arrival_location_ids' => $userArrivalMap,
        ]);

        return response()->json([
            'success' => 'Company assignment deleted successfully.',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $contact = User::find($id)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['success' => 'Successfully Deleted.', 'data' => $contact]);
    }

    public function profileSetting()
    {
        return view('management.acl.users-test.profileSetting');
    }

    public function profileSettingUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($id);
        $user->update($request->all());

        return response()->json(['success' => 'User profile updated successfully', 'data' => $user]);
    }

    public function updatePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6|different:old_password',
            'confirm_password' => 'required|same:new_password',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            if (! Hash::check($request->old_password, $user->password)) {
                $customErrors['old_password'] = ['The provided old password does not match.'];
            }
            if (! empty($customErrors)) {
                return response()->json(['errors' => $customErrors], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();
            DB::commit();

            return response()->json(['success' => 'Successfully Saved.']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getTable(Request $request)
    {
        $users = User::with(['companies'])->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
                $sq->orWhere('email', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.acl.users-test.getList', compact('users'));
    }

    public function exportToExcel()
    {
        $discounts = User::all();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'name');
        $sheet->setCellValue('B1', 'email');
        $sheet->setCellValue('C1', 'created at');

        $row = 2;
        foreach ($discounts as $discount) {
            $sheet->setCellValue('A'.$row, $discount->name);
            $sheet->setCellValue('B'.$row, $discount->email);
            $sheet->setCellValue('C'.$row, date('D d M Y', strtotime($discount->created_at)));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'users.xlsx';
        $writer->save($fileName);

        return response()->download($fileName)->deleteFileAfterSend(true);
    }
}
