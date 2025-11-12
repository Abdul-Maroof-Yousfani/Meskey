<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Helpers\ApiResponse;
use App\Models\Master\ArrivalSubLocation;

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

    public function getBagPackings()
    {
        try {
            $bagPackings = BagPacking::get(['name', 'id', 'status']);
            return ApiResponse::success($bagPackings, 'Bag packings retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve bag packings: ' . $e->getMessage(), 500);
        }
    }
    public function getGala()
    {
        try {
            $gala = ArrivalSubLocation::with('arrivalLocation') // ✅ Relation include
                ->get(['id', 'name', 'status', 'arrival_location_id']); // arrival_location_id bhi chahiye hoga
    
            return ApiResponse::success($gala, 'Gala retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve Gala: ' . $e->getMessage(), 500);
        }
    }
    
}
