<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalCustomSampling;
use Illuminate\Http\Request;

class ArrivalCustomSamplingController extends Controller
{
    public function index()
    {
        $samplings = ArrivalCustomSampling::all();
        return view('arrival_custom_sampling.index', compact('samplings'));
    }

    public function create()
    {
        return view('arrival_custom_sampling.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_ref_no' => 'nullable|string|max:255',
            // Add other validation rules
        ]);

        ArrivalCustomSampling::create($validated);

        return redirect()->route('arrival-custom-sampling.index')
            ->with('success', 'Custom sampling created successfully.');
    }

    public function show(ArrivalCustomSampling $arrivalCustomSampling)
    {
        return view('arrival_custom_sampling.show', compact('arrivalCustomSampling'));
    }

    public function edit(ArrivalCustomSampling $arrivalCustomSampling)
    {
        return view('arrival_custom_sampling.edit', compact('arrivalCustomSampling'));
    }

    public function update(Request $request, ArrivalCustomSampling $arrivalCustomSampling)
    {
        $validated = $request->validate([
            'party_ref_no' => 'nullable|string|max:255',
            // Add other validation rules
        ]);

        $arrivalCustomSampling->update($validated);

        return redirect()->route('arrival-custom-sampling.index')
            ->with('success', 'Custom sampling updated successfully.');
    }

    public function destroy(ArrivalCustomSampling $arrivalCustomSampling)
    {
        $arrivalCustomSampling->delete();



        return redirect()->route('arrival-custom-sampling.index')
            ->with('success', 'Custom sampling deleted successfully.');
    } 
}
