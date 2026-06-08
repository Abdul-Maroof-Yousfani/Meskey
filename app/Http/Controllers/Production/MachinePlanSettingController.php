<?php
// app/Http/Controllers/Production/MachinePlanSettingController.php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\MachinePlanSettingRequest;
use App\Models\Master\ProductionMachine;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\MachinePlanSettingItem;
use App\Models\Master\Plant;
use App\Models\Master\Machine;
use App\Models\Production\ProductionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachinePlanSettingController extends Controller
{
    public function index()
    {
        return view('management.production.machine_plan_setting.index');
    }

    public function getList(Request $request)
    {
        $machinePlanSettings = MachinePlanSetting::with(['plant', 'productionVoucher', 'user', 'items.machine'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('plant', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    })
                        ->orWhereHas('productionVoucher', function ($q) use ($searchTerm) {
                            $q->where('prod_no', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                return $q->whereDate('date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                return $q->whereDate('date', '<=', $request->date_to);
            })
            ->latest('date')
            ->latest('created_at')
            ->paginate(request('per_page', 25));

        return view('management.production.machine_plan_setting.getList', compact('machinePlanSettings'));
    }

    public function create(Request $request)
    {
        $plants = Plant::where('status', 'active')->get();
        $productionVouchers = ProductionVoucher::latest()->get();
        $machines = ProductionMachine::where('status', 'active')
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->get();

        return view('management.production.machine_plan_setting.create', compact('plants', 'productionVouchers', 'machines'));
    }

    public function store(MachinePlanSettingRequest $request)
    {
        DB::beginTransaction();

        try {
            $machinePlanSettingData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
                'remarks',
            ]);
            $machinePlanSettingData['user_id'] = auth()->user()->id;
            $machinePlanSetting = MachinePlanSetting::create($machinePlanSettingData);

            // Save machine items
            if ($request->has('machines') && is_array($request->machines)) {
                foreach ($request->machines as $machineData) {
                    if (!empty($machineData['production_machine_id'])) {
                        MachinePlanSettingItem::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_machine_id' => $machineData['production_machine_id'],
                            'hours' => $machineData['hours'] ?? 0,
                            'is_enabled' => $machineData['is_enabled'] ?? false,
                            'remarks' => $machineData['remarks'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Machine Plan Setting created successfully.',
                'data' => $machinePlanSetting->load('items.machine')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        $machinePlanSetting = MachinePlanSetting::with('items.machine')->findOrFail($id);

        $plants = Plant::where('status', 'active')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->get();

        $productionVouchers = ProductionVoucher::when($request->filled('company_id'), function ($q) use ($request) {
            return $q->where('company_id', $request->company_id);
        })
            ->latest()
            ->get();

        $machines = ProductionMachine::where('status', 'active')
            ->when($machinePlanSetting->plant_id, function ($q) use ($machinePlanSetting) {
                return $q->where('plant_id', $machinePlanSetting->plant_id);
            })
            ->get();

        return view('management.production.machine_plan_setting.edit', compact('machinePlanSetting', 'plants', 'productionVouchers', 'machines'));
    }

    public function update(MachinePlanSettingRequest $request, $id)
    {
        $machinePlanSetting = MachinePlanSetting::findOrFail($id);

        DB::beginTransaction();

        try {
            $machinePlanSettingData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
                'remarks',
            ]);

            $machinePlanSettingData['user_id'] = auth()->user()->id;
            $machinePlanSetting->update($machinePlanSettingData);

            // Delete existing items
            $machinePlanSetting->items()->delete();

            // Save new machine items
            if ($request->has('machines') && is_array($request->machines)) {
                foreach ($request->machines as $machineData) {
                    if (!empty($machineData['production_machine_id'])) {
                        MachinePlanSettingItem::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_machine_id' => $machineData['production_machine_id'],
                            'hours' => $machineData['hours'] ?? 0,
                            'is_enabled' => $machineData['is_enabled'] ?? false,
                            'remarks' => $machineData['remarks'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            // Load the relationship separately after commit
            $machinePlanSetting->load('items.machine');

            return response()->json([
                'success' => 'Machine Plan Setting updated successfully.',
                'data' => $machinePlanSetting
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        $machinePlanSetting = MachinePlanSetting::findOrFail($id);
        $machinePlanSetting->delete();

        return response()->json([
            'success' => 'Machine Plan Setting deleted successfully.'
        ], 200);
    }

    public function getMachinesByPlant(Request $request)
    {
        $machines = ProductionMachine::where('status', 'active')
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->get();

        return response()->json([
            'machines' => $machines
        ]);
    }
}