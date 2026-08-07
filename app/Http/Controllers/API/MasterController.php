<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Helpers\ApiResponse;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\LocationType;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function getBagTypes()
    {
        try {
            $bagTypes = BagType::get(['name', 'id', 'status']);
            return ApiResponse::success($bagTypes, 'Bag types retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve bag types: ' . $e->getMessage(), 500);
        }
    }

    public function getBagConditions()
    {
        try {
            $bagConditions = BagCondition::get(['name', 'id', 'status']);
            return ApiResponse::success($bagConditions, 'Bag conditions retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve bag conditions: ' . $e->getMessage(), 500);
        }
    }
    public function getLocationType()
    {
        try {
            $locationTypes = LocationType::get(['name', 'id', 'status']);
            return ApiResponse::success($locationTypes, 'Location types retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve location types: ' . $e->getMessage(), 500);
        }
    }

    public function getBagPackings()
    {
        try {
            $bagPackings = BagPacking::get(['name', 'id', 'status']);
            return ApiResponse::success($bagPackings, 'Bag packings retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve bag packings: ' . $e->getMessage(), 500);
        }
    }
    public function getGala(Request $request)
    {

        try {
            $gala = ArrivalSubLocation::with('arrivalLocation')
                ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                    // Ensure arrival_location_id is not null
                    if (auth()->user()->arrival_location_id) {
                        return $q->where('arrival_location_id', auth()->user()->arrival_location_id);
                    }
                    return $q;
                })
                ->when($request->filled('arrival_location_id'), function ($q) use ($request) {
                    // Use filled() instead of checking directly
                    return $q->where('arrival_location_id', $request->arrival_location_id);
                })
                ->get(['id', 'name', 'status', 'arrival_location_id']);

            return ApiResponse::success($gala, 'Gala retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve Gala: ' . $e->getMessage(), 500);
        }




    }

}
