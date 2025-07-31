<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\IndicativePriceRequest;
use App\Models\IndicativePrice;
use Illuminate\Http\Request;

class IndicativePriceController extends Controller
{
    public function index()
    {
        return view('management.procurement.raw_material.indicative_prices.index');
    }

    public function getList(Request $request)
    {
        $IndicativePrices = IndicativePrice::with(['product', 'location', 'type'])
            ->when($request->filled('product_id'), function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('location_id'), function ($q) use ($request) {
                $q->where('location_id', $request->location_id);
            })
            ->when($request->filled('type_id'), function ($q) use ($request) {
                $q->where('type_id', $request->type_id);
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.indicative_prices.getList', compact('IndicativePrices'));
    }

    public function store(IndicativePriceRequest $request)
    {
        $data = $request->validated();

        $indicativePrice = IndicativePrice::create([
            'company_id' => $request->company_id,
            'product_id' => $request->product_id,
            'location_id' => $request->location_id,
            'type_id' => $request->type_id,
            'crop_year' => $request->crop_year,
            'delivery_condition' => $request->delivery_condition,
            'cash_rate' => $request->cash_rate,
            'cash_days' => $request->cash_days,
            'credit_rate' => $request->credit_rate,
            'credit_days' => $request->credit_days,
            'others' => $request->others,
            'remarks' => $request->remarks,
            'created_by' => auth()->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Indicative price created successfully',
            'data' => $indicativePrice
        ]);
    }

    public function update(IndicativePriceRequest $request, $id)
    {
        $data = $request->validated();

        $indicativePrice = IndicativePrice::findOrFail($id);

        $indicativePrice->update([
            'product_id' => $request->product_id,
            'location_id' => $request->location_id,
            'type_id' => $request->type_id,
            'crop_year' => $request->crop_year,
            'delivery_condition' => $request->delivery_condition,
            'cash_rate' => $request->cash_rate,
            'cash_days' => $request->cash_days,
            'credit_rate' => $request->credit_rate,
            'credit_days' => $request->credit_days,
            'others' => $request->others,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Indicative price updated successfully',
            'data' => $indicativePrice
        ]);
    }

    public function destroy($id)
    {
        $indicativePrice = IndicativePrice::findOrFail($id);
        $indicativePrice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Indicative price deleted successfully'
        ]);
    }

    public function reportsView()
    {
        return view('management.procurement.raw_material.indicative_prices.reports');
    }

    public function reports(Request $request)
    {
        $reports = IndicativePrice::with(['product', 'location', 'type', 'createdBy'])
            ->when($request->filled('product_id'), function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('location_id'), function ($q) use ($request) {
                $q->where('location_id', $request->location_id);
            })
            ->when($request->filled('type_id'), function ($q) use ($request) {
                $q->where('type_id', $request->type_id);
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('created_at', $request->date);
            }, function ($q) {
                $q->whereDate('created_at', now()->format('Y-m-d'));
            })
            ->orderBy('product_id')
            ->orderBy('location_id')
            ->orderBy('created_at')
            ->get();

        $reports = $reports->groupBy('product_id')->map(function ($productGroup) {
            return $productGroup->groupBy('location_id');
        });

        $groupedReports = [];
        $index = 1;

        foreach ($reports as $productId => $locationGroups) {
            $firstProductRow = true;
            $productRowCount = $locationGroups->flatten(1)->count();
            $productName = $locationGroups->flatten(1)->first()->product->name ?? '';

            foreach ($locationGroups as $locationId => $items) {
                $firstLocationRow = true;
                $locationRowCount = count($items);
                $locationName = $items->first()->location->name ?? '';

                foreach ($items as $item) {
                    $groupedReports[] = [
                        'sno' => $firstProductRow ? $index++ : '',
                        'show_commodity' => $firstProductRow,
                        'commodity_rowspan' => $productRowCount,
                        'commodity_name' => $productName,
                        'show_location' => $firstLocationRow,
                        'location_rowspan' => $locationRowCount,
                        'location_name' => $locationName,
                        'type' => $item->type->name ?? '',
                        'crop_year' => $item->crop_year,
                        'delivery_condition' => $item->delivery_condition,
                        'time' => $item->created_at->format('h:i A'),
                        'purchaser' => $item->createdBy->name ?? '',
                        'cash_rate' => $item->cash_rate,
                        'cash_days' => $item->cash_days,
                        'credit_rate' => $item->credit_rate,
                        'credit_days' => $item->credit_days,
                        'others' => $item->others,
                        'remarks' => $item->remarks
                    ];

                    $firstProductRow = false;
                    $firstLocationRow = false;
                }
            }
        }

        return view('management.procurement.raw_material.indicative_prices.partials.report_table', [
            'reports' => $groupedReports
        ]);
    }
}
