<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayTypeRequest;
use App\Models\Master\PayType;
use Illuminate\Http\Request;

class PayTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.pay_type.index');
    }

    public function getList(Request $request)
    {
        $pay_types =  PayType::latest()->paginate(25);
        // $pay_types = PayType::where('company_id', auth()->user()->company_id)
        //     ->when($request->filled('search'), function ($q) use ($request) {
        //         $searchTerm = '%' . strtolower($request->search) . '%';
        //         return $q->where(function ($sq) use ($searchTerm) {
        //             $sq->whereRaw('LOWER(`name`) LIKE ?', [$searchTerm])
        //                 ->orWhereRaw('LOWER(`description`) LIKE ?', [$searchTerm]);
        //         });
        //     })
        //     ->latest()
        //     ->paginate(25);

        return view('management.master.pay_type.getList', compact('pay_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("management.master.pay_type.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayTypeRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->company_id;
        
        $payType = PayType::create($data);

        return response()->json(['success' => 'Pay Type created successfully.', 'data' => $payType], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payType = PayType::findOrFail($id);
        return view('management.master.pay_type.edit', compact('payType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $payType = PayType::findOrFail($id);
        return view('management.master.pay_type.edit', compact('payType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PayTypeRequest $request, PayType $payType)
    {
        $data = $request->validated();
        $payType->update($data);

        return response()->json(['success' => 'Pay Type updated successfully.', 'data' => $payType], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PayType $payType)
    {
        $payType->delete();
        return response()->json(['success' => 'Pay Type deleted successfully.'], 200);
    }
}

