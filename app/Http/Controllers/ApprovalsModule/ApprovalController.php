<?php

namespace App\Http\Controllers\ApprovalsModule;

use App\Models\Procurement\Store\PurchaseRequestData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    public function approve(Request $request, $modelType, $id)
    {
        $approvalModule = ApprovalModule::findOrFail($request->mc);

        $reqType = $request->type ?? '';
        $modelClass = $approvalModule->model_class ?? '';

        if (!class_exists($modelClass)) {
            abort(404, 'Model not found');
        }

        $record = $modelClass::findOrFail($id);

        if ($request->filled('approved_qty_data')) {
        $approvedQtys = json_decode($request->approved_qty_data, true);

        if (!empty($approvedQtys)) {
            // if approving PurchaseRequestData itself (single record)
            if ($modelClass === PurchaseRequestData::class) {
                $record->approved_qty = $approvedQtys[0] ?? 0;
                $record->save();
            }

            // if approving a parent model that has child data
            elseif (method_exists($record, 'details')) {
                foreach ($record->details as $index => $detail) {
                    $detail->approved_qty = $approvedQtys[$index] ?? 0;
                    $detail->save();
                }
            }
        }
    }

        if ($reqType == 'reject') {
            $record->am_change_made = 0;
            $record->save();

            $rejected = $record->reject($request->comments);

            if ($rejected) {
                return response()->json([
                    'success' => 'Rejected successfully. All approvals have been reset.'
                ]);
            }

            return response()->json([
                'success' => 'Rejection failed'
            ]);
        }


        if (!$record->canApprove()) {
            abort(403, 'You cannot approve this record');
        }

        $approved = $record->approve($request->comments);

        if ($approved) {
            return response()->json([
                'success' => 'Approved successfully'
            ]);
        }

        return response()->json([
            'success' => 'Approval failed'
        ]);
    }

    // public function reject(Request $request, $modelType, $id)
    // {

    //     // return response()->json([
    //     //     'success' =>  'Approval failed'
    //     // ]);

    //     $approvalModule = ApprovalModule::findOrFail($request->mc);

    //     $modelClass = $approvalModule->model_class ?? '';

    //     if (!class_exists($modelClass)) {
    //         abort(404, 'Model not found');
    //     }

    //     $record = $modelClass::findOrFail($id);

    //     $record->am_change_made = 0;
    //     $record->save();

    //     $rejected = $record->reject($request->comments);

    //     if ($rejected) {
    //         return response()->json([
    //             'success' =>  'Rejected successfully. All approvals have been reset.'
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => 'Rejection failed'
    //     ]);
    // }
}
