<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Acl\Company;
use App\Models\Master\Country;
use App\Models\Master\CountryCity;
use App\Models\Master\Port;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PortController extends Controller
{
    public function index(Request $request): View
    {
        $ports = Port::orderBy('name', 'ASC')->paginate(10);

        return view('management.master.port.index', compact('ports'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getCities($country_id)
    {
        $cities = CountryCity::where('country_id', $country_id)
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json($cities);
    }

    public function create(): View
    {
        $companies = Company::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('management.master.port.create', compact('companies', 'countries'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255|unique:ports,name',
            'description' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:country_cities,id',
            'type' => 'required|string|max:255',
            'status' => 'required|string|max:50',
        ];

        $messages = [
            'company_id.required' => 'Please select a company.',
            'name.required' => 'Port name is required.',
            'country_id.required' => 'Please select a country.',
            'city_id.required' => 'Please select a city.',
            'type.required' => 'Port type is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $port = Port::create($request->only([
            'company_id',
            'name',
            'description',
            'country_id',
            'city_id',
            'type',
            'status',
        ]));

        return response()->json([
            'success' => 'Port successfully created.',
            'data' => $port,
        ]);
    }

    public function show($id)
    {
        $port = Port::with(['company', 'country', 'city'])->findOrFail($id);

        return view('management.master.port.show', compact('port'));
    }

    public function edit($id)
    {
        $port = Port::with(['company', 'country', 'city'])->findOrFail($id);

        $companies = Company::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        // Cities of the selected country
        $cities = CountryCity::where('country_id', $port->country_id)
            ->orderBy('name', 'ASC')
            ->get();

        return view('management.master.port.edit', compact('port', 'companies', 'countries', 'cities'));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $port = Port::findOrFail($id);

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255|unique:ports,name,'.$id,
            'description' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:country_cities,id',
            'type' => 'required|string|max:255',
            'status' => 'required|string|max:50',
        ];

        $messages = [
            'company_id.required' => 'Please select a company.',
            'company_id.exists' => 'Selected company is invalid.',

            'name.required' => 'Port name is required.',
            'name.max' => 'Port name must not exceed 255 characters.',
            'name.unique' => 'This port name already exists.',

            'description.string' => 'Description must be valid text.',

            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'Selected country is invalid.',

            'city_id.required' => 'Please select a city.',
            'city_id.exists' => 'Selected city is invalid.',

            'type.required' => 'Port type is required.',
            'type.max' => 'Port type must not exceed 255 characters.',

            'status.required' => 'Status is required.',
            'status.max' => 'Status must not exceed 50 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $port->update($request->only([
            'company_id',
            'name',
            'description',
            'country_id',
            'city_id',
            'type',
            'status',
        ]));

        return response()->json([
            'success' => 'Port updated successfully.',
            'data' => $port,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $port = Port::find($id);

            if (! $port) {
                return response()->json([
                    'error' => 'Port not found.',
                ], 404);
            }

            $port->delete();

            DB::commit();

            return response()->json([
                'success' => 'Port deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getPortTable(Request $request)
    {
        $ports = Port::with(['country', 'city'])
            ->when($request->search, function ($q) use ($request) {
                $s = '%'.$request->search.'%';
                $q->where('name', 'like', $s)
                    ->orWhereHas('country', fn ($c) => $c->where('name', 'like', $s))
                    ->orWhereHas('city', fn ($ci) => $ci->where('name', 'like', $s));
            })
            ->orderBy('id', 'ASC')
            ->paginate(10);

        return view('management.master.port.getList', compact('ports'));

    }
}
