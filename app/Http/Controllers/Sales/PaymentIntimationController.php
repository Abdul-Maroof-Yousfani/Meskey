<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Export\Bank;
use App\Models\Master\Customer;
use App\Models\Sales\PaymentIntimation;
use App\Models\Sales\SalesOrder;
use App\Models\User;
use App\Notifications\NewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PaymentIntimationController extends Controller
{
    public function index()
    {
        return view('management.sales.payment-intimation.index');
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $search = $request->search;

        $query = PaymentIntimation::with(['customer', 'sale_order', 'bank'])->latest();

        if ($search) {
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            })->orWhereHas('sale_order', function($q) use ($search) {
                $q->where('reference_no', 'like', "%$search%");
            })->orWhereHas('bank', function($q) use ($search) {
                $q->where('bank_name', 'like', "%$search%");
            });
        }

        $payment_intimations = $query->paginate($perPage);

        return view('management.sales.payment-intimation.getList', compact('payment_intimations'));
    }

    public function create()
    {
        $customers = Customer::where("status", "active")->get();
        $banks = Bank::where("status", "active")->get();
        return view('management.sales.payment-intimation.create', compact('customers', 'banks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'sale_order_id' => 'required',
            'bank_id' => 'required',
            'payment_deposit' => 'required|numeric',
        ]);

        $payload = $request->except('_token');

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = 'uploads/payment-intimations';
            $file->move(public_path($path), $filename);
            $payload['attachment'] = $path . '/' . $filename;
        }
        $payload['created_by'] = auth()->user()->id ?? null;
        $payload['company_id'] = auth()->user()->company_id ?? null;

        $payment_intimation = PaymentIntimation::create($payload);
        $payment_intimation->load('sale_order', 'customer');

        $users = User::permission('payment-intimation')->get();
        if ($users->count() > 0) {
            $userName = auth()->user()->name ?? 'System';
            $soNo = $payment_intimation->sale_order->reference_no ?? 'N/A';
            $customerName = $payment_intimation->customer->name ?? 'N/A';
            
            $message = '<a href="' . route('sales.payment-intimation.index') . '" style="color: inherit; text-decoration: none;"><strong>'.$userName.'</strong> created a Payment Intimation for: <strong>'.$customerName.'</strong> • SO No: <strong>' . $soNo . '</strong></a>';
            try {
                Notification::send($users, new NewNotification($message, auth()->user()->id, ['database', 'mail']));
            } catch (\Exception $e) {
                \Log::error('Failed to send notification: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => 'Payment Intimation has been created successfully']);
    }

    public function edit($id)
    {
        $payment_intimation = PaymentIntimation::findOrFail($id);
        $customers = Customer::where("status", "active")->get();
        $banks = Bank::where("status", "active")->get();
        return view('management.sales.payment-intimation.edit', compact('payment_intimation', 'customers', 'banks'));
    }

    public function show($id)
    {
        $payment_intimation = PaymentIntimation::with(['customer', 'sale_order', 'bank'])->findOrFail($id);
        return view('management.sales.payment-intimation.show', compact('payment_intimation'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required',
            'sale_order_id' => 'required',
            'bank_id' => 'required',
            'payment_deposit' => 'required|numeric',
        ]);

        $payment_intimation = PaymentIntimation::findOrFail($id);
        $payload = $request->except('_token', '_method');

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = 'uploads/payment-intimations';
            $file->move(public_path($path), $filename);
            $payload['attachment'] = $path . '/' . $filename;
        }

        $payment_intimation->update($payload);

        return response()->json(['success' => 'Payment Intimation has been updated successfully']);
    }

    public function destroy($id)
    {
        $payment_intimation = PaymentIntimation::findOrFail($id);
        $payment_intimation->delete();

        return response()->json(['success' => 'Payment Intimation has been deleted successfully']);
    }

    public function getSaleOrders(Request $request)
    {
        $customer_id = $request->customer_id;
        $sale_orders = SalesOrder::where('customer_id', $customer_id)
            ->select('id', 'reference_no')
            ->get();

        $data = [];
        foreach ($sale_orders as $so) {
            $data[] = [
                'id' => $so->id,
                'text' => $so->reference_no,
            ];
        }

        return response()->json($data);
    }
}
