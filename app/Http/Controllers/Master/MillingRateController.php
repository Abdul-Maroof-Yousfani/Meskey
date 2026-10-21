<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Master\MillingRate;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Master\Variable;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MillingRateController extends Controller
{
    public function index()
    {
        return view('management.master.milling_rate.index');
    }

    public function getList(Request $request)
    {
        $companyId = $request->company_id ?: (auth()->check() ? auth()->user()->current_company_id : null);
        $today = Carbon::today()->toDateString();

        $milling_rates = MillingRate::with(['location', 'subLocation', 'plant', 'product', 'variables.account'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('title', 'like', $searchTerm)
                       ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', $searchTerm));
                });
            })
            ->when($companyId, function ($q) use ($companyId) {
                return $q->where('company_id', $companyId);
            })
            ->latest('from_date')
            ->latest('id')
            ->paginate(request('per_page', 25));

        // Get all active/future combinations (Location + Sublocation + Plant + Product)
        $activeCombos = MillingRate::where('to_date', '>=', $today)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->select('location_id', 'sublocation_id', 'plant_id', 'product_id')
            ->distinct()
            ->get()
            ->map(fn($r) => "{$r->location_id}_{$r->sublocation_id}_{$r->plant_id}_{$r->product_id}")
            ->flip()
            ->toArray();

        // Expired rates that have NO active/future successor
        $expiredAlerts = MillingRate::with(['location', 'plant', 'product'])
            ->where('to_date', '<', $today)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->latest('to_date')
            ->get()
            ->unique(fn($r) => "{$r->location_id}_{$r->sublocation_id}_{$r->plant_id}_{$r->product_id}")
            ->filter(function ($r) use ($activeCombos) {
                $key = "{$r->location_id}_{$r->sublocation_id}_{$r->plant_id}_{$r->product_id}";
                return !isset($activeCombos[$key]);
            });

        return view('management.master.milling_rate.getList', compact('milling_rates', 'expiredAlerts', 'activeCombos', 'today'));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $products = Product::where(function ($q) {
            $q->where('status', 'active')->orWhere('status', 1);
        })->orderBy('name')->get();
        $variables = Variable::with('account')->where('status', 1)->get();
        return view('management.master.milling_rate.create', compact('companyLocations', 'products', 'variables'));
    }

    public function store(Request $request)
    {
        $messages = [
            'product_id.required' => 'Please select a Commodity / Product.',
            'product_id.exists' => 'The selected Commodity / Product is invalid.',
            'from_date.required' => 'The Effective From Date is required.',
            'to_date.required' => 'The Effective To Date is required.',
            'to_date.after_or_equal' => 'The Effective To Date must be equal to or after the From Date.',
            'location_id.required' => 'Please select a Location.',
            'sublocation_id.required' => 'Please select a Sub-Location.',
            'plant_id.required' => 'Please select a Plant.',
            'title.required' => 'The Title field is required.',
        ];

        $data = $request->validate([
            'location_id' => 'required|integer',
            'sublocation_id' => 'required|integer',
            'plant_id' => 'required|integer',
            'product_id' => 'required|integer|exists:products,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'variables' => 'nullable|array',
            'company_id' => 'nullable|integer'
        ], $messages);

        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->check() ? (auth()->user()->current_company_id ?? 1) : 1;
        }
        $data['status'] = $data['status'] ?? 1;

        // Check date range overlap for same Location, Sublocation, Plant, and Product
        $overlap = MillingRate::where('location_id', $data['location_id'])
            ->where('sublocation_id', $data['sublocation_id'])
            ->where('plant_id', $data['plant_id'])
            ->where('product_id', $data['product_id'])
            ->when($data['company_id'], fn($q) => $q->where('company_id', $data['company_id']))
            ->where(function ($q) use ($data) {
                $q->where('from_date', '<=', $data['to_date'])
                  ->where('to_date', '>=', $data['from_date']);
            })
            ->first();

        if ($overlap) {
            $product = Product::find($data['product_id']);
            $pName = $product?->name ?? 'Commodity';
            $existingFrom = Carbon::parse($overlap->from_date)->format('d M, Y');
            $existingTo = Carbon::parse($overlap->to_date)->format('d M, Y');
            $suggestedFrom = Carbon::parse($overlap->to_date)->addDay()->format('d M, Y');

            throw ValidationException::withMessages([
                'from_date' => ["A Milling Rate already exists for Commodity \"{$pName}\" at this Plant from {$existingFrom} to {$existingTo}. Please select dates after {$existingTo} (starting from {$suggestedFrom})."]
            ]);
        }

        $millingRate = MillingRate::create($data);

        if (isset($data['variables']) && is_array($data['variables'])) {
            $syncData = [];
            foreach ($data['variables'] as $variableId => $value) {
                if ($value !== null && $value !== '') {
                    $syncData[$variableId] = ['value' => $value];
                }
            }
            $millingRate->variables()->sync($syncData);
        }

        return response()->json(['success' => 'Milling Rate created successfully.', 'data' => $millingRate], 201);
    }

    public function edit(int $id)
    {
        $millingRate = MillingRate::with(['variables', 'product'])->findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('company_location_id', $millingRate->location_id)->where('status', 'active')->get();
        $plants = Plant::where('arrival_location_id', $millingRate->sublocation_id)->where('status', 'active')->get();
        $products = Product::where(function ($q) {
            $q->where('status', 'active')->orWhere('status', 1);
        })->orderBy('name')->get();
        $variables = Variable::with('account')->where('status', 1)->get();

        return view('management.master.milling_rate.edit', compact('millingRate', 'companyLocations', 'arrivalLocations', 'plants', 'products', 'variables'));
    }

    public function update(Request $request, int $id)
    {
        $messages = [
            'product_id.required' => 'Please select a Commodity / Product.',
            'product_id.exists' => 'The selected Commodity / Product is invalid.',
            'from_date.required' => 'The Effective From Date is required.',
            'to_date.required' => 'The Effective To Date is required.',
            'to_date.after_or_equal' => 'The Effective To Date must be equal to or after the From Date.',
            'location_id.required' => 'Please select a Location.',
            'sublocation_id.required' => 'Please select a Sub-Location.',
            'plant_id.required' => 'Please select a Plant.',
            'title.required' => 'The Title field is required.',
        ];

        $data = $request->validate([
            'location_id' => 'required|integer',
            'sublocation_id' => 'required|integer',
            'plant_id' => 'required|integer',
            'product_id' => 'required|integer|exists:products,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'variables' => 'nullable|array'
        ], $messages);

        $millingRate = MillingRate::findOrFail($id);
        $companyId = $millingRate->company_id ?? (auth()->check() ? auth()->user()->current_company_id : null);
        $data['status'] = $data['status'] ?? 1;

        // Check date range overlap excluding this record
        $overlap = MillingRate::where('location_id', $data['location_id'])
            ->where('sublocation_id', $data['sublocation_id'])
            ->where('plant_id', $data['plant_id'])
            ->where('product_id', $data['product_id'])
            ->where('id', '!=', $id)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($data) {
                $q->where('from_date', '<=', $data['to_date'])
                  ->where('to_date', '>=', $data['from_date']);
            })
            ->first();

        if ($overlap) {
            $product = Product::find($data['product_id']);
            $pName = $product?->name ?? 'Commodity';
            $existingFrom = Carbon::parse($overlap->from_date)->format('d M, Y');
            $existingTo = Carbon::parse($overlap->to_date)->format('d M, Y');
            $suggestedFrom = Carbon::parse($overlap->to_date)->addDay()->format('d M, Y');

            throw ValidationException::withMessages([
                'from_date' => ["A Milling Rate already exists for Commodity \"{$pName}\" at this Plant from {$existingFrom} to {$existingTo}. Please select dates after {$existingTo} (starting from {$suggestedFrom})."]
            ]);
        }

        $millingRate->update($data);

        if (isset($data['variables']) && is_array($data['variables'])) {
            $syncData = [];
            foreach ($data['variables'] as $variableId => $value) {
                if ($value !== null && $value !== '') {
                    $syncData[$variableId] = ['value' => $value];
                }
            }
            $millingRate->variables()->sync($syncData);
        } else {
            $millingRate->variables()->sync([]);
        }

        return response()->json(['success' => 'Milling Rate updated successfully.', 'data' => $millingRate], 200);
    }

    public function show(int $id)
    {
        $millingRate = MillingRate::with(['location', 'subLocation', 'plant', 'product', 'variables.account'])->findOrFail($id);
        return view('management.master.milling_rate.show', compact('millingRate'));
    }

    public function getSubLocations($locationId)
    {
        $arrivalLocations = ArrivalLocation::where('company_location_id', $locationId)->where('status', 'active')->get();
        return response()->json($arrivalLocations);
    }

    public function getPlants($subLocationId)
    {
        $plants = Plant::where('arrival_location_id', $subLocationId)->where('status', 'active')->get();
        return response()->json($plants);
    }
}
