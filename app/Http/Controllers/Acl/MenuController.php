<?php

namespace App\Http\Controllers\Acl;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Acl\{Menu};
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.acl.menu.index');
    }

    public function getList(Request $request)
    {
        $menus = Menu::leftJoin('permissions','permissions.id','menus.permission_id')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('menus.name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->select('permissions.name as permission_name','menus.*')
            ->paginate(request('per_page', 25));
         
        return view('management.acl.menu.getList', compact('menus'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $menus = Menu::whereStatus(1)->get();
        $permissions = Permission::get();
        return view('management.acl.menu.create', compact('menus', 'permissions'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

          $validator = Validator::make($request->all(), [
            'name' => 'required|unique:menus,name',
            'permission_id' => 'required',
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $request->all();
        $data['creator_id'] = auth()->user()->id;

        Menu::create($data);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        dd($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
