<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrder;
use App\Models\Master\Customer;
use App\Models\Production\JobOrder\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Database\QueryException;

class ExportFormEController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $form_es = ExportFormE::orderBy('id', 'ASC')->paginate(0);
        } catch (QueryException $e) {
            $form_es = collect();
        }

        return view('management.export.form-e.index', compact('form_es'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportFormETable(Request $request)
    {
        try {
            $form_es = ExportFormE::with(['exportOrder', 'buyer', 'jobOrder'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $searchTerm = '%'.$request->search.'%';
                    return $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                        $sq->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    });
                })
                ->latest()
                ->paginate(request('per_page', 25));
        } catch (QueryException $e) {
            $form_es = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        }

        return view('management.export.form-e.getList', compact('form_es'));
    }

    public function create(): View
    {
        $buyers = collect();
        $export_orders = collect();
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $buyers = Customer::get();
            $job_orders = JobOrder::latest()->get();
        } catch (QueryException $e) {}

        return view('management.export.form-e.create', compact(
            'buyers', 'job_orders', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations'
        ));
    }

    public function getExportOrderDetails($id)
    {
        try {
            $exportOrder = ExportOrder::with([
                'product', 
                'specifications', 
                'packingItems.bagType', 
                'packingItems.bagPacking', 
                'packingItems.brand', 
                'packingItems.bagColor',
                'broker', 
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($id);

            // Calculate total quantity of all packing items
            $totalQuantity = 0;
            foreach ($exportOrder->packingItems as $item) {
                // summing up metric_tons
                $totalQuantity += (float) ($item->metric_tons ?? 0);
            }

            // Get total used quantity from existing Form-E for this export order
            $usedQuantity = 0;
            try {
                $usedQuantity = ExportFormE::where('export_order_id', $id)->sum('input_quantity');
            } catch (\Exception $e) {
                // Table might not exist yet
                $usedQuantity = 0;
            }
            
            $remainingQuantity = $totalQuantity - $usedQuantity;
            if ($remainingQuantity < 0) $remainingQuantity = 0;

            return response()->json([
                'success' => true,
                'data' => $exportOrder,
                'total_quantity' => $totalQuantity,
                'remaining_quantity' => $remainingQuantity,
                'used_quantity' => $usedQuantity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required|exists:export_orders,id',
            'buyer_id' => 'required|exists:customers,id',
            'job_order_id' => 'nullable|exists:job_orders,id',
            'form_e_no' => 'required|string|unique:export_form_es,form_e_no',
            'form_e_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'input_quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::with([
                'product', 
                'specifications', 
                'packingItems.bagType', 
                'packingItems.bagPacking', 
                'packingItems.brand', 
                'packingItems.bagColor',
                'broker', 
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($request->export_order_id);
            
            // Re-calculate to ensure it does not exceed remaining quantity
            $totalQuantity = 0;
            foreach ($exportOrder->packingItems as $item) {
                $totalQuantity += (float) ($item->metric_tons ?? 0);
            }

            $usedQuantity = 0;
            try {
                $usedQuantity = ExportFormE::where('export_order_id', $exportOrder->id)->sum('input_quantity');
            } catch (\Exception $e) {
                $usedQuantity = 0;
            }
            $remainingQuantity = $totalQuantity - $usedQuantity;

            if ($request->input_quantity > $remainingQuantity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Input quantity cannot exceed remaining quantity (' . $remainingQuantity . ')',
                ], 422);
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('export/form_e', 'public');
            }

            $formE = ExportFormE::create([
                'export_order_id' => $request->export_order_id,
                'buyer_id' => $request->buyer_id,
                'job_order_id' => $request->job_order_id,
                'form_e_no' => $request->form_e_no,
                'form_e_date' => $request->form_e_date,
                'attachment' => $attachmentPath,
                'total_quantity' => $totalQuantity,
                'remaining_quantity' => $remainingQuantity - $request->input_quantity,
                'input_quantity' => $request->input_quantity,
                'export_snapshot' => $exportOrder->toArray(),
                'created_by' => auth()->user() ? auth()->user()->id : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Export Form-E created successfully',
                'data' => $formE,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        try {
            $formE = ExportFormE::with(['exportOrder', 'buyer', 'jobOrder'])->findOrFail($id);
            $exportOrderData = $formE->export_snapshot;
        } catch (\Exception $e) {
            $formE = new ExportFormE();
            $exportOrderData = [];
        }

        return view('management.export.form-e.show', compact('formE', 'exportOrderData'));
    }

    public function edit($id): View
    {
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $formE = ExportFormE::findOrFail($id);
            $buyers = Customer::get();
            $job_orders = JobOrder::latest()->get();
            $export_orders = ExportOrder::latest()->get();
        } catch (QueryException $e) {
            $formE = new ExportFormE();
            $buyers = collect();
            $job_orders = collect();
            $export_orders = collect();
        }

        return view('management.export.form-e.edit', compact(
            'formE', 'buyers', 'job_orders', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            $formE = ExportFormE::findOrFail($id);
            $request->validate([
                'form_e_no' => 'required|string|unique:export_form_es,form_e_no,' . $id,
                'form_e_date' => 'required|date',
                'input_quantity' => 'required|numeric|min:0.01',
            ]);
            // Re-calculate quantities if they update input_quantity. 
            // The requirement only says to create multi Form-E but not exceed. 
            // If they update, we should check again.
            if ($request->has('input_quantity')) {
                $exportOrder = ExportOrder::with(['packingItems'])->findOrFail($formE->export_order_id);
                $totalQuantity = 0;
                foreach ($exportOrder->packingItems as $item) {
                    $totalQuantity += (float) ($item->metric_tons ?? 0);
                }

                // Used qty excluding this formE instance
                $usedQuantity = 0;
                try {
                    $usedQuantity = ExportFormE::where('export_order_id', $exportOrder->id)
                                               ->where('id', '!=', $id)
                                               ->sum('input_quantity');
                } catch (\Exception $e) {
                    $usedQuantity = 0;
                }
                
                $remainingQuantity = $totalQuantity - $usedQuantity;

                if ($request->input_quantity > $remainingQuantity) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Input quantity cannot exceed remaining quantity (' . $remainingQuantity . ')',
                    ], 422);
                }
                
                $data = $request->only(['input_quantity', 'buyer_id', 'job_order_id', 'form_e_no', 'form_e_date']);
                if ($request->hasFile('attachment')) {
                    if ($formE->attachment) {
                        Storage::disk('public')->delete($formE->attachment);
                    }
                    $data['attachment'] = $request->file('attachment')->store('export/form_e', 'public');
                }

                $formE->update(array_merge($data, [
                     'remaining_quantity' => $remainingQuantity - $request->input_quantity,
                ]));
            } else {
                $data = $request->only(['buyer_id', 'job_order_id', 'form_e_no', 'form_e_date']);
                if ($request->hasFile('attachment')) {
                    if ($formE->attachment) {
                        Storage::disk('public')->delete($formE->attachment);
                    }
                    $data['attachment'] = $request->file('attachment')->store('export/form_e', 'public');
                }
                $formE->update($data);
            }

            return response()->json([
                'success' => 'Export Form-E updated successfully',
                'data' => $formE
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $formE = ExportFormE::findOrFail($id);
            $formE->delete();

            return response()->json([
                'success' => true,
                'message' => 'Export Form-E deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Form-E'
            ], 500);
        }
    }

    public function getOrdersByBuyer($buyer_id)
    {
        try {
            $export_orders = ExportOrder::with(['packingItems'])->where('id', '>', 0)->where('buyer_id', $buyer_id)->latest()->get();
            
            $filtered_orders = $export_orders->filter(function($order) {
                // Total quantity of all packing items
                $totalQuantity = 0;
                foreach ($order->packingItems as $item) {
                    $totalQuantity += (float) ($item->metric_tons ?? 0);
                }

                // Sum of input_quantity from existing Form-E
                $usedQuantity = 0;
                try {
                    $usedQuantity = ExportFormE::where('export_order_id', $order->id)->sum('input_quantity');
                } catch (\Exception $e) {
                    $usedQuantity = 0;
                }

                return ($totalQuantity - $usedQuantity) > 0.001; // Filter out if fully used
            })->values();

            return response()->json([
                'success' => true,
                'data' => $filtered_orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFormEsByOrder($order_id)
    {
        try {
            $formEs = ExportFormE::where('export_order_id', $order_id)->latest()->get();
            return response()->json([
                'success' => true,
                'data' => $formEs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
