<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(Request $request): View
    {
        $countries = Country::orderBy('name', 'ASC')->paginate(10);

        return view('management.master.country.index', compact('countries'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        return view('management.master.country.create');
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:255|unique:countries,name',
            'alpha_2_code' => 'required|string|size:2|unique:countries,alpha_2_code',
            'alpha_3_code' => 'required|string|size:3|unique:countries,alpha_3_code',
            'phone_code' => 'required|string|max:20',
        ];

        $messages = [
            'name.required' => 'Country name is required.',
            'name.max' => 'Country name must not exceed 255 characters.',
            'name.unique' => 'This country already exists.',

            'alpha_2_code.required' => 'Alpha-2 code is required.',
            'alpha_2_code.size' => 'Alpha-2 code must be exactly 2 characters.',
            'alpha_2_code.unique' => 'This alpha-2 code is already used.',

            'alpha_3_code.required' => 'Alpha-3 code is required.',
            'alpha_3_code.size' => 'Alpha-3 code must be exactly 3 characters.',
            'alpha_3_code.unique' => 'This alpha-3 code is already used.',

            'phone_code.required' => 'Phone Code is required.',
            'phone_code.max' => 'Phone code must not exceed 20 digits.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $country = Country::create($request->only(['name', 'alpha_2_code', 'alpha_3_code', 'phone_code']));

        return response()->json([
            'success' => 'Country successfully saved.',
            'data' => $country,
        ], 200);
    }

    public function show(int $id)
    {
        $country = Country::findOrFail($id);

        return view('management.master.country.show', compact('country'));
    }

    public function edit(int $id): View
    {
        $country = Country::findOrFail($id);

        return view('management.master.country.edit', compact('country'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $country = Country::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255|unique:countries,name,'.$country->id,
            'alpha_2_code' => 'required|string|size:2|unique:countries,alpha_2_code,'.$country->id,
            'alpha_3_code' => 'required|string|size:3|unique:countries,alpha_3_code,'.$country->id,
            'phone_code' => 'required|string|max:20',
        ];

        $messages = [
            'name.required' => 'Country name is required.',
            'name.max' => 'Country name must not exceed 255 characters.',
            'name.unique' => 'This country already exists.',

            'alpha_2_code.required' => 'Alpha-2 code is required.',
            'alpha_2_code.size' => 'Alpha-2 code must be exactly 2 characters.',
            'alpha_2_code.unique' => 'This alpha-2 code is already used.',

            'alpha_3_code.required' => 'Alpha-3 code is required.',
            'alpha_3_code.size' => 'Alpha-3 code must be exactly 3 characters.',
            'alpha_3_code.unique' => 'This alpha-3 code is already used.',

            'phone_code.required' => 'Phone Code is required.',
            'phone_code.max' => 'Phone code must not exceed 20 digits.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $country->update($request->only(['name', 'alpha_2_code', 'alpha_3_code', 'phone_code']));

        return response()->json([
            'success' => 'Country successfully updated.',
            'data' => $country,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $country = Country::find($id);

            if (! $country) {
                return response()->json([
                    'error' => 'Country not found.',
                ], 404);
            }

            $country->delete();

            DB::commit();

            return response()->json([
                'success' => 'Country deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCountryTable(Request $request)
    {
        $countries = Country::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                    ->orWhere('alpha_2_code', 'like', $searchTerm)
                    ->orWhere('alpha_3_code', 'like', $searchTerm)
                    ->orWhere('phone_code', 'like', $searchTerm);
            });
        })
            ->orderBy('name', 'ASC')
            ->paginate(10);

        return view('management.master.country.getList', compact('countries'));
    }
}
