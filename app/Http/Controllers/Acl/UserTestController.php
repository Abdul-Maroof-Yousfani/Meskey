<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserTestStoreRequest;
use App\Http\Requests\User\UserTestUpdateRequest;
use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Account\Account;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:user-list', ['only' => ['index']]);
        $this->middleware('check.company:user-list', ['only' => ['getTable']]);
        $this->middleware('check.company:user-list', ['only' => ['exportToExcel']]);
        $this->middleware('check.company:user-list', ['only' => ['getAuditLogs', 'getCompanyAuditLogs']]);
        $this->middleware('check.company:user-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:user-delete', ['only' => ['destroy']]);
        $this->middleware('check.company:assign-company', ['only' => ['assign']]);
        $this->middleware('check.company:edit-assign-company', ['only' => ['edit-assign']]);
        $this->middleware('check.company:delete-assign-company', ['only' => ['delete-assign']]);
    }

    public function index(Request $request): View
    {
        $allowedLocationIds = $this->getAllowedLocationIds();

        $data = User::query()
            ->tap(function ($q) use ($allowedLocationIds) {
                $this->applyUserLocationScope($q, $allowedLocationIds);
            })
            ->latest()
            ->paginate(5);

        $locations = CompanyLocation::when($allowedLocationIds !== null, function ($q) use ($allowedLocationIds) {
                return $q->whereIn('id', $allowedLocationIds);
            })
            ->orderBy('name')
            ->get();

        return view('management.acl.users-test.index', compact('data', 'locations'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $allowedLocationIds = $this->getAllowedLocationIds();
        $users = User::query()
            ->tap(function ($q) use ($allowedLocationIds) {
                $this->applyUserLocationScope($q, $allowedLocationIds);
            })
            ->get();
        $parentAccount = Account::where('hierarchy_path', '2-9')->first();

        return view('management.acl.users-test.create', compact('users', 'parentAccount'));
    }

    public function store(UserTestStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $allowedLocationIds = $this->getAllowedLocationIds();
        if ($allowedLocationIds !== null) {
            $data['user_type'] = 'user';
        }

        $data['password'] = Hash::make($data['password']);

        if ($request->has('has_account') && $request->has_account) {
            $companyId = auth()->user()?->current_company_id ?: 1;
            $account = Account::create(getParamsForAccountCreationByPath($companyId, $request->name, '2-9', 'users'));
            $account->update(['status' => 'active']);
            $data['account_id'] = $account->id;
        } else {
            $data['account_id'] = null;
        }

        $user = User::create($data);

        $newValues = $this->buildUserAuditValues($user);
        AuditLogService::log(
            $user,
            'user_created',
            "User '{$user->name}' ({$user->username}) created.",
            null,
            $newValues
        );

        return response()->json([
            'success' => 'Basic details saved.',
            'user_id' => $user->username,
            'redirect' => route('users-test.edit', $user->id),
        ]);
    }

    public function getCompanyLocations($companyId)
    {
        $allowedLocationIds = $this->getAllowedLocationIds($companyId);

        $locations = CompanyLocation::where('company_id', $companyId)
            ->with('arrivalLocations')
            ->when($allowedLocationIds !== null, function ($q) use ($allowedLocationIds) {
                return $q->whereIn('id', $allowedLocationIds);
            })
            ->get();

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
            'available' => !$exists,
            'username' => $username,
        ]);
    }

    public function show($id): View
    {
        $user = User::findOrFail($id);
        $allowedLocationIds = $this->getAllowedLocationIds();
        if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
            abort(403, 'Unauthorized access to user from another location.');
        }

        return view('management.acl.users-test.show', compact('user'));
    }

    public function edit($id): View
    {
        $user = User::with(['companies', 'roles', 'companyLocation.arrivalLocations', 'account.parent'])->findOrFail($id);
        $this->syncUserCompanyLocations($user);
        $allowedLocationIds = $this->getAllowedLocationIds();
        if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
            abort(403, 'Unauthorized access to user from another location.');
        }
        $users = User::where('id', '!=', $id)
            ->tap(function ($q) use ($allowedLocationIds) {
                $this->applyUserLocationScope($q, $allowedLocationIds);
            })
            ->get();
        $userRole = $user->roles->pluck('id', 'name')->all();
        $parentAccount = Account::where('hierarchy_path', '2-9')->first();

        return view('management.acl.users-test.edit', compact('user', 'userRole', 'users', 'parentAccount'));
    }

    public function update(UserTestUpdateRequest $request, $id): JsonResponse
    {
        try {
            $input = $request->all();

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                $input = Arr::except($input, ['password']);
            }

            $user = User::findOrFail($id);
            $allowedLocationIds = $this->getAllowedLocationIds();
            if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
                return response()->json(['error' => 'Unauthorized action for this user.'], 403);
            }
            $oldValues = $this->buildUserAuditValues($user);
            $passwordChanged = !empty($input['password']);

            if ($request->has('has_account') && $request->has_account) {
                if ($user->account_id && $user->account) {
                    $user->account->update(['status' => 'active', 'name' => $request->name]);
                    $input['account_id'] = $user->account_id;
                } else {
                    $companyId = $user->current_company_id ?: (auth()->user()?->current_company_id ?: 1);
                    $account = Account::create(getParamsForAccountCreationByPath($companyId, $request->name, '2-9', 'users'));
                    $account->update(['status' => 'active']);
                    $input['account_id'] = $account->id;
                }
            } else {
                if ($user->account) {
                    $user->account->update(['status' => 'inactive']);
                }
                $input['account_id'] = $user->account_id;
            }

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
            $this->syncUserCompanyLocations($user);
            $user->refresh();

            $newValues = $this->buildUserAuditValues($user);

            if ($passwordChanged) {
                $oldValues['password'] = '******';
                $newValues['password'] = 'Updated (Changed)';
            }

            AuditLogService::log(
                $user,
                'user_updated',
                "User '{$user->name}' ({$user->username}) updated.",
                $oldValues,
                $newValues
            );

            return response()->json([
                'success' => 'Successfully Saved.',
                'data' => $user,
                'username' => $user->username,
                // 'redirect' => route('users-test.assign', $user->id),
            ]);
        } catch (Exception $e) {
            Log::error('User update failed: ' . $e->getMessage(), [
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
        $allowedLocationIds = $this->getAllowedLocationIds();
        if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
            abort(403, 'Unauthorized access to user from another location.');
        }

        $permission = Permission::get();
        $assignedCompanyIds = $user->companies->pluck('id')->toArray();
        $authUser = auth()->user();

        if ($allowedLocationIds !== null && $authUser) {
            $userCompanyIds = $authUser->companies->pluck('id')->toArray();
            $companies = Company::whereIn('id', $userCompanyIds)->whereNotIn('id', $assignedCompanyIds)->get();
        } else {
            $companies = Company::whereNotIn('id', $assignedCompanyIds)->get();
        }

        return view('management.acl.users-test.assign', compact(
            'user',
            'permission',
            'companies',
        ));
    }

    public function saveAssignDetails(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldAssignments = $this->buildUserAssignmentAuditValues($user);

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
            if (!empty($permissions) && !in_array('dashboard', $permissions)) {
                $validator->errors()->add('permission', 'Dashboard permission is required.');
            }
        });

        $validator->validate(); // Throws validation exception if fails

        // $request->validate($rules);

        $permissions = $request->permission ?? [];
        $allowedLocationIds = $this->getAllowedLocationIds();
        $existingCompanyLocationIds = $user->company_location_ids ?? [];
        $existingArrivalLocationIds = $user->arrival_location_ids ?? [];

        $newCompanyLocationIds = [];
        $newArrivalLocationMap = []; // map instead of flat array

        $roleNames = [];

        foreach ($request->company as $i => $companyId) {

            $company = Company::findOrFail($companyId);
            $prefix = substr(strtoupper($company->name), 0, 3);

            $autoRoleName = $user->username . '-company' . $prefix . $companyId;

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
            if ($allowedLocationIds !== null) {
                $locations = array_values(array_intersect($locations, $allowedLocationIds));
            }

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

                if (!empty($locArrivals)) {

                    // Add in final mapping output
                    $newArrivalLocationMap[$locId] = $locArrivals;

                    // Add to flat list for db pairing
                    foreach ($locArrivals as $arrId) {
                        $arrivalData[] = $arrId;
                    }
                }
            }

            // Restored role_id and sync logic
            $user->companies()->syncWithoutDetaching([
                $companyId => [
                    'role_id' => $role->id,
                    'locations' => json_encode($locations),
                    'arrival_locations' => json_encode($arrivalData),
                ],
            ]);

            Log::info('SaveAssignDetails PO Approval loop', [
                'index' => $i,
                'isset' => isset($request->purchase_order_approval[$i]),
                'raw' => $request->purchase_order_approval
            ]);

            // Check if checked for this specific index (array case) OR if it's a single value (string case)
            $isChecked = false;
            if (is_array($request->purchase_order_approval)) {
                $isChecked = isset($request->purchase_order_approval[$i]);
            } elseif ($request->has('purchase_order_approval')) {
                $isChecked = true;
            }

            if ($isChecked) {
                $user->update(['purchase_order_approval' => true]);
            } else {
                // If we are in the last loop and nothing was checked, or if it's a single field unchecked
                // Actually, if it's a global flag, we should only set it to false if NONE are checked.
            }
        }

        // Global update for purchase_order_approval if missing from request entirely
        if (!$request->has('purchase_order_approval')) {
            $user->update(['purchase_order_approval' => false]);
        }

        $this->syncUserCompanyLocations($user);

        $user->syncRoles(array_unique(array_merge(
            $user->getRoleNames()->toArray(),
            $roleNames
        )));

        $user->refresh();
        $newAssignments = $this->buildUserAssignmentAuditValues($user);

        AuditLogService::log(
            $user,
            'user_assignment_saved',
            "Company & Location assignments updated for user '{$user->name}'.",
            $oldAssignments,
            $newAssignments
        );

        return response()->json(['success' => 'User assignments saved successfully.']);
    }

    public function editAssignDetails($userId, $companyId, $roleId)
    {
        $user = User::with('companies')->findOrFail($userId);
        $allowedLocationIds = $this->getAllowedLocationIds($companyId);
        if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
            abort(403, 'Unauthorized access to user from another location.');
        }

        $permission = Permission::get();
        $locations = CompanyLocation::where('company_id', $companyId)
            ->when($allowedLocationIds !== null, function ($q) use ($allowedLocationIds) {
                return $q->whereIn('id', $allowedLocationIds);
            })
            ->get();
        $companies = getAllCompanies();

        $selectedLocations = [];
        $selectedArrivals = [];

        $company = $user->companies->where('id', $companyId)->first();

        if ($company && !empty($company->pivot->locations)) {
            $selectedLocations = json_decode($company->pivot->locations, true) ?? [];
        }

        if ($company && !empty($company->pivot->arrival_locations)) {
            $selectedArrivals = json_decode($company->pivot->arrival_locations, true) ?? [];
        }

        $assignedPermissions = Role::find($roleId)?->permissions->pluck('name')->toArray() ?? [];

        $purchaseOrderApproval = $user->purchase_order_approval;

        return view('management.acl.users-test.edit-assign', compact(
            'user',
            'permission',
            'locations',
            'companies',
            'selectedLocations',
            'selectedArrivals',
            'assignedPermissions',
            'companyId',
            'roleId',
            'purchaseOrderApproval'
        ));
    }

    public function updateAssignDetails(Request $request, $userId, $companyId, $roleId)
    {
        $user = User::findOrFail($userId);
        $oldAssignments = $this->buildUserAssignmentAuditValues($user);

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
            if (!empty($permissions) && !in_array('dashboard', $permissions)) {
                $validator->errors()->add('permission', 'Dashboard permission is required.');
            }
        });

        $validator->validate(); // Throws validation exception if fails

        $permissions = $request->permission ?? [];
        $locations = $request->company_location_id ?? [];
        $locations = is_array($locations) ? $locations : [];

        $allowedLocationIds = $this->getAllowedLocationIds($companyId);
        if ($allowedLocationIds !== null) {
            $existingCompany = DB::table('company_user_role')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();
            $currentLocs = $existingCompany && !empty($existingCompany->locations)
                ? (json_decode($existingCompany->locations, true) ?: [])
                : [];
            $outsideScopeLocs = array_values(array_diff($currentLocs, $allowedLocationIds));

            $submittedAllowedLocs = array_values(array_intersect($locations, $allowedLocationIds));
            $locations = array_values(array_unique(array_merge($outsideScopeLocs, $submittedAllowedLocs)));
        }

        $arrivals = $request->arrival_location_id ?? [];
        $arrivals = is_array($arrivals) ? $arrivals : [];

        $flatArrivalData = [];
        $mapArrivalData = [];

        foreach ($locations as $locId) {
            $locArrivals = ArrivalLocation::whereIn('id', $arrivals)
                ->where('company_location_id', $locId)
                ->pluck('id')
                ->toArray();

            if (!empty($locArrivals)) {
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

        Log::info('Updating PO Approval status', [
            'userId' => $user->id,
            'has_field' => $request->has('purchase_order_approval'),
            'value' => $request->purchase_order_approval
        ]);

        $user->update(['purchase_order_approval' => $request->has('purchase_order_approval')]);

        $this->syncUserCompanyLocations($user);

        $user->refresh();
        $newAssignments = $this->buildUserAssignmentAuditValues($user);

        AuditLogService::log(
            $user,
            'user_assignment_updated',
            "Company assignment updated for user '{$user->name}'.",
            $oldAssignments,
            $newAssignments
        );

        return response()->json(['success' => 'Company assignment updated successfully.']);
    }

    public function deleteAssignDetails($userId, $companyId, $roleId)
    {
        $user = User::findOrFail($userId);
        $oldAssignments = $this->buildUserAssignmentAuditValues($user);

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

        $this->syncUserCompanyLocations($user);

        $user->refresh();
        $newAssignments = $this->buildUserAssignmentAuditValues($user);

        AuditLogService::log(
            $user,
            'user_assignment_deleted',
            "Company assignment deleted for user '{$user->name}'.",
            $oldAssignments,
            $newAssignments
        );

        return response()->json([
            'success' => 'Company assignment deleted successfully.',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $allowedLocationIds = $this->getAllowedLocationIds();
            if (!$this->userBelongsToAllowedLocations($user, $allowedLocationIds)) {
                return response()->json(['error' => 'Unauthorized action for this user.'], 403);
            }
            $oldValues = $this->buildUserAuditValues($user);
            $oldAssignments = $this->buildUserAssignmentAuditValues($user);

            AuditLogService::log(
                $user,
                'user_deleted',
                "User '{$user->name}' ({$user->username}) deleted.",
                array_merge($oldValues, $oldAssignments),
                null
            );

            $contact = $user->delete();
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
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($id);
        $oldValues = $this->buildUserAuditValues($user);
        $user->update($request->all());
        $user->refresh();
        $newValues = $this->buildUserAuditValues($user);

        AuditLogService::log(
            $user,
            'profile_updated',
            "Profile updated for user '{$user->name}'.",
            $oldValues,
            $newValues
        );

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

            if (!Hash::check($request->old_password, $user->password)) {
                $customErrors['old_password'] = ['The provided old password does not match.'];
            }
            if (!empty($customErrors)) {
                return response()->json(['errors' => $customErrors], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            AuditLogService::log(
                $user,
                'password_updated',
                "Password changed for user '{$user->name}'.",
                ['password' => '******'],
                ['password' => 'Updated (Changed)']
            );

            DB::commit();

            return response()->json(['success' => 'Successfully Saved.']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getTable(Request $request)
    {
        $locationIds = is_array($request->company_location_id)
            ? $request->company_location_id
            : ($request->filled('company_location_id') ? [$request->company_location_id] : []);
        $locationIds = array_values(array_filter($locationIds, fn($id) => !empty($id) && $id !== 'all'));

        $allowedLocationIds = $this->getAllowedLocationIds();

        $users = User::with(['companies', 'account.parent'])
            ->tap(function ($q) use ($allowedLocationIds, $locationIds) {
                $this->applyUserLocationScope($q, $allowedLocationIds, $locationIds);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('username', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhereHas('account', function ($aq) use ($searchTerm) {
                            $aq->where('hierarchy_path', 'like', $searchTerm)
                                ->orWhere('unique_no', 'like', $searchTerm)
                                ->orWhere('name', 'like', $searchTerm);
                        });
                });
            })
            ->latest()
            // ->paginate(10);
            ->paginate($request->get('per_page', 25));

        return view('management.acl.users-test.getList', compact('users'));
    }

    public function exportToExcel(Request $request)
    {
        $locationIds = is_array($request->company_location_id)
            ? $request->company_location_id
            : ($request->filled('company_location_id') ? [$request->company_location_id] : []);
        $locationIds = array_values(array_filter($locationIds, fn($id) => !empty($id) && $id !== 'all'));

        $allowedLocationIds = $this->getAllowedLocationIds();

        $users = User::with(['companies', 'account.parent', 'parent'])
            ->tap(function ($q) use ($allowedLocationIds, $locationIds) {
                $this->applyUserLocationScope($q, $allowedLocationIds, $locationIds);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('username', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhereHas('account', function ($aq) use ($searchTerm) {
                            $aq->where('hierarchy_path', 'like', $searchTerm)
                                ->orWhere('unique_no', 'like', $searchTerm)
                                ->orWhere('name', 'like', $searchTerm);
                        });
                });
            })
            ->latest()
            ->get();

        $companyLocations = CompanyLocation::pluck('name', 'id')->toArray();
        $arrivalLocations = ArrivalLocation::all()->keyBy('id');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        $headers = ['Name', 'Parent', 'PO Approval', 'Location/Sublocation', 'COA Hierarchy'];
        $columns = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . '1', $header);
        }

        // Header styling
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F497D'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 2;
        foreach ($users as $user) {
            $parent = $user->parent?->name ?? '--';
            $poApproval = $user->purchase_order_approval ? 'Enabled' : 'Disabled';

            // Location/Sublocation
            $locEntries = [];
            foreach ($user->companies as $comp) {
                $savedLocations = json_decode($comp->pivot->locations, true) ?? [];
                $flatArrivals = json_decode($comp->pivot->arrival_locations, true) ?? [];

                $savedArrivals = [];
                foreach ($flatArrivals as $arrId) {
                    $arr = $arrivalLocations->get($arrId);
                    if ($arr && $arr->company_location_id) {
                        $savedArrivals[$arr->company_location_id][] = $arr->name;
                    }
                }

                foreach ($savedLocations as $locId) {
                    $locName = $companyLocations[$locId] ?? 'Location N/A';
                    $arrNames = $savedArrivals[$locId] ?? [];

                    if (!empty($arrNames)) {
                        $locEntries[] = $locName . ' (' . implode(', ', $arrNames) . ')';
                    } else {
                        $locEntries[] = $locName;
                    }
                }
            }

            if (empty($locEntries)) {
                if (!empty($user->company_location_ids)) {
                    $directLocIds = is_array($user->company_location_ids)
                        ? $user->company_location_ids
                        : (json_decode($user->company_location_ids, true) ?? []);
                    foreach ($directLocIds as $locId) {
                        if (isset($companyLocations[$locId])) {
                            $locEntries[] = $companyLocations[$locId];
                        }
                    }
                } elseif ($user->company_location_id && isset($companyLocations[$user->company_location_id])) {
                    $locEntries[] = $companyLocations[$user->company_location_id];
                }
            }

            $locationSublocation = !empty($locEntries) ? implode("\n", array_unique($locEntries)) : '--';

            // COA Hierarchy
            $coaHierarchy = '--';
            if ($user->account) {
                $parts = array_filter([
                    $user->account->hierarchy_path,
                    $user->account->unique_no,
                    $user->account->name,
                ]);
                $coaHierarchy = !empty($parts) ? implode(' - ', $parts) : '--';
            }

            $sheet->setCellValue('A' . $row, $user->name);
            $sheet->setCellValue('B' . $row, $parent);
            $sheet->setCellValue('C' . $row, $poApproval);
            $sheet->setCellValue('D' . $row, $locationSublocation);
            $sheet->setCellValue('E' . $row, $coaHierarchy);

            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getColumnDimension('E')->setWidth(30);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'users.xlsx';
        $writer->save($fileName);

        return response()->download($fileName)->deleteFileAfterSend(true);
    }

    /**
     * Build audit values array for user basic details.
     */
    protected function buildUserAuditValues(User $user): array
    {
        $user->loadMissing(['parent', 'account']);

        return [
            'name'                    => $user->name,
            'username'                => $user->username,
            'email'                   => $user->email,
            'user_type'               => $user->user_type ? ucwords(str_replace('-', ' ', $user->user_type)) : null,
            'parent_user'             => $user->parent ? "{$user->parent->name} ({$user->parent->username})" : 'None',
            'purchase_order_approval' => $user->purchase_order_approval ? 'Yes' : 'No',
            'chart_of_account'        => $user->account ? "{$user->account->name} [{$user->account->unique_no}] (" . ucfirst($user->account->status) . ')' : 'None',
            'status'                  => $user->status ? ucfirst($user->status) : 'Active',
        ];
    }

    /**
     * Build audit values array for user company & location assignments.
     */
    protected function buildUserAssignmentAuditValues(User $user): array
    {
        $user->loadMissing('companies');
        $result = [];
        $companiesInfo = [];

        foreach ($user->companies as $company) {
            $pivot = $company->pivot;
            $locIds = !empty($pivot->locations) ? (json_decode($pivot->locations, true) ?? []) : [];
            $arrIds = !empty($pivot->arrival_locations) ? (json_decode($pivot->arrival_locations, true) ?? []) : [];

            $locNames = !empty($locIds) ? CompanyLocation::whereIn('id', $locIds)->pluck('name')->toArray() : [];
            $arrNames = !empty($arrIds) ? ArrivalLocation::whereIn('id', $arrIds)->pluck('name')->toArray() : [];

            $role = !empty($pivot->role_id) ? Role::find($pivot->role_id) : null;
            $permissions = $role ? $role->permissions->pluck('name')->toArray() : [];

            $compKey = "company_{$company->id}";
            $result[$compKey . '_locations'] = !empty($locNames) ? implode(', ', $locNames) : 'None';
            $result[$compKey . '_arrivals'] = !empty($arrNames) ? implode(', ', $arrNames) : 'None';
            if (!empty($permissions)) {
                $result[$compKey . '_permissions'] = implode(', ', $permissions);
            }

            $parts = [];
            if (!empty($locNames)) {
                $parts[] = 'Locations: ' . implode(', ', $locNames);
            }
            if (!empty($arrNames)) {
                $parts[] = 'Arrivals: ' . implode(', ', $arrNames);
            }

            $companiesInfo[] = $company->name . (!empty($parts) ? ' (' . implode(' | ', $parts) . ')' : '');
        }

        $result['assigned_companies'] = !empty($companiesInfo) ? implode('; ', $companiesInfo) : 'None';

        return $result;
    }

    /**
     * Get audit logs for a user's specific company assignment (edit logs only).
     */
    public function getCompanyAuditLogs(Request $request, $userId, $companyId)
    {
        $user = User::findOrFail($userId);
        $company = Company::findOrFail($companyId);

        // Fetch logs for this user - ONLY edit/update logs, NO create logs
        $logs = AuditLog::with('user')
            ->where(function ($q) use ($user, $companyId, $company) {
                $q->where(function ($sq) use ($user) {
                    $sq->whereIn('model_type', [User::class, 'User', 'App\Models\User'])
                        ->where('model_id', $user->id);
                })
                ->orWhere('old_values', 'like', '%"company_' . $companyId . '_%')
                ->orWhere('new_values', 'like', '%"company_' . $companyId . '_%')
                ->orWhere('old_values', 'like', '%' . $company->name . '%')
                ->orWhere('new_values', 'like', '%' . $company->name . '%');
            })
            ->where(function ($q) {
                $q->where('action', 'like', '%assignment_saved%')
                  ->orWhere('action', 'like', '%assignment_updated%')
                  ->orWhere('action', 'like', '%assignment_deleted%')
                  ->orWhere('action', 'like', '%update%');
            })
            ->where('action', 'not like', '%user_created%')
            ->where('action', 'not like', '%store%')
            ->orderBy('created_at', 'desc')
            ->get();

        // Note: Do NOT add virtual create log because requirement specifies edit logs only ("create k logs nh lgane hn is me bss edit k lga do")

        $fieldLabels = [
            "company_{$companyId}_locations"   => 'Assigned Locations',
            "company_{$companyId}_arrivals"    => 'Assigned Arrival Locations',
            "company_{$companyId}_permissions" => 'Assigned Permissions',
            'assigned_companies'               => "Company Assignments ({$company->name})",
        ];

        $flattenArray = function ($arr, $prefix = '') use (&$flattenArray) {
            $result = [];
            if (!is_array($arr)) return $result;
            foreach ($arr as $key => $value) {
                $newKey = $prefix === '' ? $key : "{$prefix}.{$key}";
                if (is_array($value) && !empty($value) && !isset($value[0])) {
                    $result = array_merge($result, $flattenArray($value, $newKey));
                } else {
                    $result[$newKey] = $value;
                }
            }
            return $result;
        };

        $filteredLogs = [];
        $skipKeys = ['id', 'deleted_at', 'created_at', 'updated_at', 'company_id', 'remember_token', 'current_company_id'];

        foreach ($logs as $log) {
            $rawOld = is_array($log->old_values) ? $log->old_values : (!empty($log->old_values) ? json_decode($log->old_values, true) : []);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : (!empty($log->new_values) ? json_decode($log->new_values, true) : []);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = $flattenArray($rawOld);
            $new = $flattenArray($rawNew);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            foreach ($allKeys as $key) {
                if (in_array($key, $skipKeys)) continue;

                // Only show changes relevant to this company
                if (str_starts_with($key, 'company_') && !str_starts_with($key, "company_{$companyId}_")) {
                    continue;
                }
                // Skip user profile fields if this is a general user_updated log that didn't affect company
                if (in_array($key, ['name', 'username', 'email', 'password', 'user_type', 'parent_user', 'chart_of_account', 'status', 'profile_image'])) {
                    continue;
                }

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    $parsedChanges[] = [
                        'key'     => $key,
                        'label'   => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'old'     => is_array($oldVal) ? json_encode($oldVal) : $oldVal,
                        'new'     => is_array($newVal) ? json_encode($newVal) : $newVal,
                        'raw_old' => $oldVal,
                        'raw_new' => $newVal,
                    ];
                }
            }

            // Only keep this log if it has actual changes for this company
            if (!empty($parsedChanges)) {
                $log->parsed_changes = $parsedChanges;
                $filteredLogs[] = $log;
            }
        }

        $logs = $filteredLogs;
        $title = "Audit Logs: {$company->name} Assignment";
        $subtitle = "User: {$user->name} ({$user->username}) | Company: {$company->name} | Edit History";
        $emptyMessage = "No edits or updates have been recorded for {$company->name} assignment yet.";

        return view('components.audit-logs', compact('logs', 'title', 'subtitle', 'emptyMessage'));
    }

    /**
     * Get the allowed location IDs for the authenticated user.
     * Returns null for super-admin (no restrictions).
     * Returns array of string location IDs for restricted users.
     */
    protected function getAllowedLocationIds($companyId = null): ?array
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return [];
        }

        $isSuperAdmin = $authUser->user_type === 'super-admin'
            || (method_exists($authUser, 'hasAnyRole') && $authUser->hasAnyRole(['Super Admin', 'super-admin']))
            || (method_exists($authUser, 'hasRole') && ($authUser->hasRole('Super Admin') || $authUser->hasRole('super-admin')));

        if ($isSuperAdmin) {
            return null; // null represents no restriction for super-admin
        }

        $locations = [];

        // 1. If target company is specified, prioritize that company's pivot locations
        $targetCompanyId = $companyId ?: $authUser->current_company_id;
        if ($targetCompanyId) {
            $comp = $authUser->companies->firstWhere('id', $targetCompanyId);
            if ($comp && !empty($comp->pivot?->locations)) {
                $pivotLocs = is_array($comp->pivot->locations)
                    ? $comp->pivot->locations
                    : json_decode($comp->pivot->locations, true);
                if (is_array($pivotLocs)) {
                    $locations = array_merge($locations, $pivotLocs);
                }
            }
        }

        // 2. If no locations found for target company or no companyId specified, check helper
        if (empty($locations) && function_exists('getUserCurrentCompanyLocations')) {
            try {
                $hlpLocs = getUserCurrentCompanyLocations();
                if (is_array($hlpLocs)) {
                    $locations = array_merge($locations, $hlpLocs);
                }
            } catch (\Throwable $e) {
            }
        }

        // 3. If still empty, check all company pivots
        if (empty($locations)) {
            foreach ($authUser->companies as $comp) {
                if (!empty($comp->pivot?->locations)) {
                    $pivotLocs = is_array($comp->pivot->locations)
                        ? $comp->pivot->locations
                        : json_decode($comp->pivot->locations, true);
                    if (is_array($pivotLocs)) {
                        $locations = array_merge($locations, $pivotLocs);
                    }
                }
            }
        }

        // 4. Fallback to company_location_ids attribute on user
        if (empty($locations) && !empty($authUser->company_location_ids)) {
            $ids = is_array($authUser->company_location_ids)
                ? $authUser->company_location_ids
                : json_decode($authUser->company_location_ids, true);
            if (is_array($ids)) {
                $locations = array_merge($locations, $ids);
            }
        }

        // 5. Fallback to primary company_location_id attribute
        if (empty($locations) && !empty($authUser->company_location_id)) {
            $locations[] = (string) $authUser->company_location_id;
        }

        return array_values(array_unique(array_filter(array_map('strval', $locations))));
    }

    /**
     * Synchronize users.company_location_ids and users.arrival_location_ids
     * strictly based on the user's active company pivot assignments.
     */
    protected function syncUserCompanyLocations(User $user): void
    {
        $allCompanies = $user->companies()->get();
        if ($allCompanies->isEmpty()) {
            return;
        }

        $finalCompanyLocationIds = [];
        $finalArrivalLocationMap = [];

        foreach ($allCompanies as $comp) {
            $compLocs = [];
            if (!empty($comp->pivot?->locations)) {
                $compLocs = is_array($comp->pivot->locations)
                    ? $comp->pivot->locations
                    : (json_decode($comp->pivot->locations, true) ?: []);
                foreach ($compLocs as $lId) {
                    $finalCompanyLocationIds[] = (string) $lId;
                }
            }

            if (!empty($comp->pivot?->arrival_locations)) {
                $flatArrs = is_array($comp->pivot->arrival_locations)
                    ? $comp->pivot->arrival_locations
                    : (json_decode($comp->pivot->arrival_locations, true) ?: []);

                if (!empty($flatArrs) && !empty($compLocs)) {
                    $arrivalsByLoc = ArrivalLocation::whereIn('id', $flatArrs)
                        ->whereIn('company_location_id', $compLocs)
                        ->get()
                        ->groupBy('company_location_id');

                    foreach ($arrivalsByLoc as $locId => $arrItems) {
                        $finalArrivalLocationMap[(string) $locId] = $arrItems->pluck('id')->toArray();
                    }
                }
            }
        }

        $finalCompanyLocationIds = array_values(array_unique($finalCompanyLocationIds));

        $user->update([
            'company_location_ids' => $finalCompanyLocationIds,
            'arrival_location_ids' => $finalArrivalLocationMap,
        ]);
    }

    /**
     * Get all assigned location IDs for a user across company pivots and attributes.
     */
    protected function getUserAssignedLocationIds(User $user): array
    {
        $locations = [];

        foreach ($user->companies as $company) {
            if (!empty($company->pivot?->locations)) {
                $pivotLocs = is_array($company->pivot->locations)
                    ? $company->pivot->locations
                    : json_decode($company->pivot->locations, true);
                if (is_array($pivotLocs)) {
                    $locations = array_merge($locations, $pivotLocs);
                }
            }
        }

        if (empty($locations) && !empty($user->company_location_ids)) {
            $ids = is_array($user->company_location_ids)
                ? $user->company_location_ids
                : json_decode($user->company_location_ids, true);
            if (is_array($ids)) {
                $locations = array_merge($locations, $ids);
            }
        }

        return array_values(array_unique(array_filter(array_map('strval', $locations))));
    }

    /**
     * Apply location filtering to a user query.
     * When restricted, users only see target users whose assigned locations
     * are strictly a subset of the allowed locations (cannot have any outside location).
     */
    protected function applyUserLocationScope($query, ?array $allowedLocationIds, array $requestedLocationIds = [])
    {
        $allLocationIds = CompanyLocation::pluck('id')->map(fn($id) => (string) $id)->toArray();

        if ($allowedLocationIds === null) {
            // Super-admin: filter only if a specific location was requested
            if (!empty($requestedLocationIds)) {
                $query->where(function ($q) use ($requestedLocationIds) {
                    foreach ($requestedLocationIds as $locId) {
                        $q->orWhereJsonContains('company_location_ids', (string) $locId)
                          ->orWhereJsonContains('company_location_ids', (int) $locId)
                          ->orWhereHas('companies', function ($cq) use ($locId) {
                              $cq->where(function ($sub) use ($locId) {
                                  $sub->whereJsonContains('locations', (string) $locId)
                                      ->orWhereJsonContains('locations', (int) $locId);
                              });
                          });
                    }
                });
            }
            return $query;
        }

        // Non-super-admin: always restrict to allowed locations
        if (empty($allowedLocationIds)) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        if (!empty($requestedLocationIds)) {
            $effectiveAllowed = array_values(array_intersect($requestedLocationIds, $allowedLocationIds));
            if (empty($effectiveAllowed)) {
                $query->whereRaw('1 = 0');
                return $query;
            }
            $disallowedLocationIds = array_values(array_diff($allLocationIds, $effectiveAllowed));
        } else {
            $effectiveAllowed = $allowedLocationIds;
            $disallowedLocationIds = array_values(array_diff($allLocationIds, $allowedLocationIds));
        }

        // 1. Must have at least one allowed location OR be completely unassigned (no location assigned yet)
        $query->where(function ($q) use ($effectiveAllowed, $requestedLocationIds) {
            foreach ($effectiveAllowed as $locId) {
                $q->orWhereJsonContains('company_location_ids', (string) $locId)
                  ->orWhereJsonContains('company_location_ids', (int) $locId)
                  ->orWhereHas('companies', function ($cq) use ($locId) {
                      $cq->where(function ($sub) use ($locId) {
                          $sub->whereJsonContains('locations', (string) $locId)
                              ->orWhereJsonContains('locations', (int) $locId);
                      });
                  });
            }

            // Also show users who have not been assigned any location yet (when no specific location is filtered)
            if (empty($requestedLocationIds)) {
                $q->orWhere(function ($uq) {
                    $uq->where(function ($sub) {
                        $sub->whereNull('company_location_ids')
                            ->orWhere('company_location_ids', '[]')
                            ->orWhere('company_location_ids', '""')
                            ->orWhere('company_location_ids', '')
                            ->orWhereRaw('JSON_LENGTH(company_location_ids) = 0');
                    })->whereDoesntHave('companies', function ($cq) {
                        $cq->whereNotNull('locations')
                           ->where('locations', '!=', '[]')
                           ->where('locations', '!=', '""')
                           ->where('locations', '!=', '')
                           ->whereRaw('JSON_LENGTH(locations) > 0');
                    });
                });
            }
        });

        // 2. Must NOT have any disallowed location (strict subset)
        if (!empty($disallowedLocationIds)) {
            // Must not contain disallowed location in company_location_ids
            foreach ($disallowedLocationIds as $disLocId) {
                $query->where(function ($q) use ($disLocId) {
                    $q->whereNull('company_location_ids')
                      ->orWhere(function ($sub) use ($disLocId) {
                          $sub->whereJsonDoesntContain('company_location_ids', (string) $disLocId)
                              ->whereJsonDoesntContain('company_location_ids', (int) $disLocId);
                      });
                });
            }

            // Must not contain disallowed location in companies pivot locations
            $query->whereDoesntHave('companies', function ($cq) use ($disallowedLocationIds) {
                $cq->where(function ($sub) use ($disallowedLocationIds) {
                    foreach ($disallowedLocationIds as $disLocId) {
                        $sub->orWhereJsonContains('locations', (string) $disLocId)
                            ->orWhereJsonContains('locations', (int) $disLocId);
                    }
                });
            });
        }

        return $query;
    }

    /**
     * Check if a given user belongs strictly to the allowed locations.
     */
    protected function userBelongsToAllowedLocations(User $user, ?array $allowedLocationIds): bool
    {
        if ($allowedLocationIds === null) {
            return true;
        }
        if (empty($allowedLocationIds)) {
            return false;
        }

        $userLocs = $this->getUserAssignedLocationIds($user);
        // Users who have not been assigned any location yet are accessible
        if (empty($userLocs)) {
            return true;
        }

        // 1. Must have at least one allowed location
        $hasAllowed = false;
        foreach ($userLocs as $loc) {
            if (in_array((string) $loc, $allowedLocationIds)) {
                $hasAllowed = true;
                break;
            }
        }
        if (!$hasAllowed) {
            return false;
        }

        // 2. Must NOT have any location outside allowed locations
        foreach ($userLocs as $loc) {
            if (!in_array((string) $loc, $allowedLocationIds)) {
                return false;
            }
        }

        return true;
    }
}
