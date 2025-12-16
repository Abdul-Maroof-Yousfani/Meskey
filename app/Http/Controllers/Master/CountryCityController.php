<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Country;
use App\Models\Master\CountryCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CountryCityController extends Controller
{
    public function index(Request $request): View
    {
        $cities = CountryCity::orderBy('name', 'ASC')->paginate(10);

        return view('management.master.city.index', compact('cities'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $countries = Country::orderBy('name', 'ASC')->get(); // dropdown

        return view('management.master.city.create', compact('countries'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:255|unique:cities,name',
            'country_id' => 'required|exists:countries,id',
            'country_code' => 'required|string|size:2',
        ];

        $messages = [
            'name.required' => 'City name is required.',
            'name.max' => 'City name must not exceed 255 characters.',
            'name.unique' => 'This city already exists.',
            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'Selected country is invalid.',
            'country_code.required' => 'Country code is required.',
            'country_code.size' => 'Country code must be exactly 2 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $city = CountryCity::create($request->only(['name', 'country_id', 'country_code']));

        return response()->json([
            'success' => 'City successfully saved.',
            'data' => $city,
        ], 200);
    }

    public function show(int $id)
    {
        $city = CountryCity::with('country')->findOrFail($id);
        $countries = Country::orderBy('name', 'ASC')->get();

        return view('management.master.city.show', compact('city', 'countries'));
    }

    public function edit(int $id): View
    {
        $city = CountryCity::findOrFail($id);
        $countries = Country::orderBy('name', 'ASC')->get();

        return view('management.master.city.edit', compact('city', 'countries'));
    }

    // Update city
    public function update(Request $request, int $id): JsonResponse
    {
        $city = CountryCity::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255|unique:cities,name,'.$city->name,
            'country_id' => 'required|exists:countries,id',
            'country_code' => 'required|string|size:2',
        ];

        $messages = [
            'name.required' => 'City name is required.',
            'name.max' => 'City name must not exceed 255 characters.',
            'name.unique' => 'This city already exists.',
            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'Selected country is invalid.',
            'country_code.required' => 'Country code is required.',
            'country_code.size' => 'Country code must be exactly 2 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $city->update($request->only(['name', 'country_id', 'country_code']));

        return response()->json([
            'success' => 'City successfully updated.',
            'data' => $city,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $city = CountryCity::find($id);

            if (! $city) {
                return response()->json([
                    'error' => 'City not found.',
                ], 404);
            }

            $city->delete();

            DB::commit();

            return response()->json([
                'success' => 'City deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCountryCityTable(Request $request)
    {
        $cities = CountryCity::with('country')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%'.$request->search.'%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('country_code', 'like', $searchTerm)
                        ->orWhereHas('country', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        });
                });
            })
            ->orderBy('id', 'ASC')
            ->paginate(10);

        return view('management.master.city.getList', compact('cities'));
    }
}
