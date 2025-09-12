<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreItemPaymentRequestRequest;
use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderPaymentRequestController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_order_payment_request.index');
    }

    public function getList(Request $request)
    {
        $paymentRequests = PaymentRequest::with(['purchaseOrder', 'grn', 'supplier'])
            ->where('payment_type', '!=', null)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_order_payment_request.getList', compact('paymentRequests'));
    }

    public function create()
    {
        return view('management.procurement.store.purchase_order_payment_request.create');
    }

    public function getSources(Request $request)
    {
        $isAdvance = $request->is_advance == 'true';

        if ($isAdvance) {
            $purchaseOrders = PurchaseOrder::with(['items', 'items.supplier'])
                // ->where('status', 'approved')
                ->get()
                ->map(function ($purchaseOrder) {
                    $purchaseOrder->total_amount = $purchaseOrder->items->sum('total');
                    $purchaseOrder->supplier_id = $purchaseOrder->items->first()->supplier_id ?? null;
                    $purchaseOrder->supplier_name = $purchaseOrder->items->first()->supplier->name ?? null;
                    $purchaseOrder->total_paid = $purchaseOrder->paymentRequests()->where('status', 'approved')->sum('amount');
                    $purchaseOrder->remaining_amount = max(0, $purchaseOrder->total_amount - $purchaseOrder->total_paid);

                    return $purchaseOrder;
                })
                ->filter(function ($purchaseOrder) {
                    return $purchaseOrder->remaining_amount > 0;
                });

            return response()->json([
                'purchase_orders' => $purchaseOrders,
                'grns' => []
            ]);
        } else {
            $grns = GoodReceiveNote::with(['purchaseOrder', 'product', 'supplier'])
                ->where('status', 'received')
                ->get()
                ->map(function ($grn) {
                    $grn->total_paid = $grn->paymentRequests()->where('status', 'approved')->sum('amount');
                    // $grn->supplier_id = $grn->supplier_id;
                    $grn->remaining_amount = max(0, $grn->price - $grn->total_paid);

                    return $grn;
                })
                ->filter(function ($grn) {
                    return $grn->remaining_amount > 0;
                });

            return response()->json([
                'purchase_orders' => [],
                'grns' => $grns
            ]);
        }
    }

    public function getPaidAmount(Request $request)
    {
        $paidAmount = 0;

        if ($request->has('purchase_order_id')) {
            $paidAmount = PaymentRequest::where('purchase_order_id', $request->purchase_order_id)
                // ->where('status', 'approved')
                ->sum('amount');
        } elseif ($request->has('grn_id')) {
            $paidAmount = PaymentRequest::where('grn_id', $request->grn_id)
                // ->where('status', 'approved')
                ->sum('amount');
        }

        return response()->json([
            'paid_amount' => $paidAmount
        ]);
    }

    public function store(StoreItemPaymentRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $paymentRequestData = PaymentRequestData::create([
                'store_purchase_order_id' => $request->purchase_order_id,
                'grn_id' => $request->grn_id,
                'supplier_id' => $request->supplier_id,
                'remaining_amount' => $request->remaining_amount,
                'total_amount' => $request->amount,
                'description' => $request->description,
                'payment_type' => $request->is_advance ? 'advance' : 'against_receiving',
                'is_advance_payment' => $request->is_advance ? 1 : 0,
            ]);

            $paymentRequest = PaymentRequest::create([
                'request_no' => $this->generatePaymentRequestNumber(),
                'payment_request_data_id' => $paymentRequestData->id,
                'supplier_id' => $request->supplier_id,
                'purchase_order_id' => $request->purchase_order_id,
                'grn_id' => $request->grn_id,
                'requested_by' => Auth::user()->id,
                'request_date' => now(),
                'amount' => $request->amount,
                'description' => $request->description,
                'payment_type' => $request->is_advance ? 'advance' : 'against_receiving',
                'is_advance_payment' => $request->is_advance ? 1 : 0,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Payment request created successfully.',
                'data' => $paymentRequest,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generatePaymentRequestNumber()
    {
        $prefix = 'PR-';
        $year = date('Y');
        $month = date('m');

        $latest = PaymentRequest::where('request_no', 'like', $prefix . $year . $month . '%')
            ->orderBy('request_no', 'desc')
            ->first();

        if ($latest) {
            $number = intval(substr($latest->request_no, -4)) + 1;
        } else {
            $number = 1;
        }

        return $prefix . $year . $month . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $paymentRequest = PaymentRequest::findOrFail($id);
            $paymentRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::user()->id,
                'approved_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment request approved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $paymentRequest = PaymentRequest::with(['purchaseOrder', 'grn', 'supplier'])
            ->findOrFail($id);

        return view('management.procurement.store.purchase_order_payment_request.edit', compact('paymentRequest'));
    }

    public function update(StoreItemPaymentRequestRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $paymentRequest = PaymentRequest::findOrFail($id);

            $paymentRequest->update([
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            if ($paymentRequest->paymentRequestData) {
                $paymentRequest->paymentRequestData->update([
                    'total_amount' => $request->amount,
                    'description' => $request->description,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment request updated successfully.',
                'data' => $paymentRequest,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $paymentRequest = PaymentRequest::findOrFail($id);

            if ($paymentRequest->paymentRequestData) {
                $paymentRequest->paymentRequestData->delete();
            }

            $paymentRequest->delete();

            DB::commit();

            return response()->json(['success' => 'Payment request deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
