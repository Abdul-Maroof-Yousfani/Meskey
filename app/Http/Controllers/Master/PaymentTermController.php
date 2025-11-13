<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentTermRequest;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.payment_term.index');
    }

    public function getList(Request $request) {
         $payment_terms = PaymentTerm::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . strtolower($request->search) . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->whereRaw('LOWER(`desc`) LIKE ?', [strtolower($searchTerm)]);
            });
        })
        ->latest()
        ->paginate(25);


        return view('management.master.payment_term.getList', compact('payment_terms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("management.master.payment_term.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentTermRequest $request)
    {
        
        $data = $request->validated();
        $color = PaymentTerm::create($request->all());

        return response()->json(['success' => 'Payment Term created successfully.', 'data' => $color], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $paymentTerm = PaymentTerm::findOrFail($id);
        $paymentTerms = PaymentTerm::where('id', '!=', $id)->get(); // Exclude current category from parent list
        return view('management.master.payment_term.edit', compact('paymentTerm', 'paymentTerms'));
   
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $paymentTerm = PaymentTerm::findOrFail($id);
        $paymentTerms = PaymentTerm::where('id', '!=', $id)->get(); // Exclude current category from parent list
        return view('management.master.payment_term.edit', compact('paymentTerm', 'paymentTerms'));
   
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentTermRequest $request, PaymentTerm $paymentTerm)
    {
        $data = $request->validated();
        $paymentTerm->update($data);

        return response()->json(['success' => 'Category updated successfully.', 'data' => $paymentTerm], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTerm $paymentTerm)
    {
        $paymentTerm->delete();
        return response()->json(['success' => 'Payment Term deleted successfully.'], 200);
    }
}
