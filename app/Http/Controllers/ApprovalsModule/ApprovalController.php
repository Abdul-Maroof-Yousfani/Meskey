<?php

namespace App\Http\Controllers\ApprovalsModule;

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

        if ($reqType == 'reject') {
            $record->am_change_made = 0;
            $record->save();

            $rejected = $record->reject($request->comments);

            if ($rejected) {
                return response()->json([
                    'success' =>  'Rejected successfully. All approvals have been reset.'
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
                'success' =>  'Approved successfully'
            ]);
        }

        return response()->json([
            'success' =>  'Approval failed'
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
