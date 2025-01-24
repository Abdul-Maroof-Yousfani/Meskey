<?php

namespace App\Http\Controllers\Acl;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Writer\Xlsx};
use Spatie\Permission\Models\{Role, Permission};
use DB;
use Illuminate\View\View;
use App\Http\Requests\Role\{StoreRoleRequest, UpdateRoleRequest};


class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('check.company:role-list', ['only' => ['index']]);
        $this->middleware('check.company:role-list', ['only' => ['getTable']]);
        $this->middleware('check.company:role-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:role-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:role-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): View
    {
        $roles = Role::orderBy('id', 'ASC')->paginate(0);
        return view('management.acl.roles.index', compact('roles'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): View
    {
        $permission = Permission::get();
        return view('management.acl.roles.create', compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->input('name'), 'description' => $request->input('description')]);
        $role->syncPermissions($request->input('permission'));
        return response()->json(['success' => 'Successfully Saved.', 'data' => $role]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id): View
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table('role_has_permissions')->where('role_has_permissions.role_id', $id)->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')->all();

        return view('management.acl.roles.show', compact('role', 'rolePermissions', 'permission'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id): View
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table('role_has_permissions')->where('role_has_permissions.role_id', $id)->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')->all();

        return view('management.acl.roles.edit', compact('role', 'permission', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, $id): JsonResponse
    {

        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->save();

        $role->syncPermissions($request->input('permission'));

        return response()->json(['success' => 'Successfully Saved.', 'data' => $role]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $contact = DB::table('roles')->where('id', $id)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(['success' => 'Successfully Deleted.', 'data' => $contact]);
    }

    public function getTable(Request $request)
    {
        $roles = Role::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.acl.roles.getList', compact('roles'));
    }

    public function exportToExcel()
    {
        $discounts = Role::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'name');
        $sheet->setCellValue('B1', 'created at');

        $row = 2;
        foreach ($discounts as $discount) {
            $sheet->setCellValue('A' . $row, $discount->name);
            $sheet->setCellValue('B' . $row, date('D d M Y', strtotime($discount->created_at)));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'roles.xlsx';
        $writer->save($fileName);

        return response()->download($fileName)->deleteFileAfterSend(true);
    }
}
