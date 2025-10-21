<?php

namespace App\Http\Controllers\ApprovalsModule;

use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    public function approve(Request $request, $modelType, $id)
    {
        // dd($request->all());
        $approvalModule = ApprovalModule::findOrFail($request->mc);

        $reqType = $request->type ?? '';
        $modelClass = $approvalModule->model_class ?? '';

        if (!class_exists($modelClass)) {
            abort(404, 'Model not found');
        }

        $record = $modelClass::findOrFail($id);

        if (!empty($request->model_data_ids)) {
            $dataIds = json_decode($request->model_data_ids, true);

            if (!empty($dataIds) && $request->type === 'approve') {
                $dataModelClass = $modelClass . 'Data';

                if (class_exists($dataModelClass)) {
                    $dataModelClass::whereIn('id', $dataIds)->update(['am_approval_status' => 'approved']);
                }
            }
        }


        if ($reqType == 'revert') {
            $record->am_change_made = 0;
            $record->save();

            $returned = $record->revert($request->comments);

            if ($returned) {
                return response()->json([
                    'success' => 'Request reverted successfully.'
                ]);
            }

            return response()->json([
                'success' => 'Revert failed'
            ]);

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

    // public function bulk_quotation_approval(Request $request, $modelType, $id)
    // {
    //     $approvalModule = ApprovalModule::findOrFail($request->mc);

    //     $reqType = $request->type ?? '';
    //     $modelClass = $approvalModule->model_class ?? '';

    //     if (!class_exists($modelClass)) {
    //         abort(404, 'Model not found');
    //     }

    //     // Decode JSON string into array
    //     $modelDataIds = json_decode($request->model_data_ids, true);

    //     if (!is_array($modelDataIds)) {
    //         abort(400, 'Invalid model_data_ids format');
    //     }

    //     $results = [];

    //     foreach ($modelDataIds as $dataId) {
    //         $record = $modelClass::find($dataId);

    //         if (!$record) {
    //             $results[] = [
    //                 'id' => $dataId,
    //                 'status' => 'failed',
    //                 'message' => 'Record not found'
    //             ];
    //             continue;
    //         }

    //         if ($reqType == 'revert') {
    //             $record->am_change_made = 0;
    //             $record->save();

    //             $returned = $record->revert($request->comments);
    //             $results[] = [
    //                 'id' => $dataId,
    //                 'status' => $returned ? 'success' : 'failed',
    //                 'message' => $returned ? 'Reverted successfully' : 'Revert failed'
    //             ];
    //             continue;
    //         }

    //         if ($reqType == 'reject') {
    //             $record->am_change_made = 0;
    //             $record->save();

    //             $rejected = $record->reject($request->comments);
    //             $results[] = [
    //                 'id' => $dataId,
    //                 'status' => $rejected ? 'success' : 'failed',
    //                 'message' => $rejected ? 'Rejected successfully' : 'Rejection failed'
    //             ];
    //             continue;
    //         }

    //         // Default: Approve
    //         if (!$record->canApprove()) {
    //             $results[] = [
    //                 'id' => $dataId,
    //                 'status' => 'failed',
    //                 'message' => 'You cannot approve this record'
    //             ];
    //             continue;
    //         }

    //         $approved = $record->approve($request->comments);

    //         $results[] = [
    //             'id' => $dataId,
    //             'status' => $approved ? 'success' : 'failed',
    //             'message' => $approved ? 'Approved successfully' : 'Approval failed'
    //         ];
    //     }

    //     return response()->json([
    //         'summary' => [
    //             'total' => count($modelDataIds),
    //             'success' => collect($results)->where('status', 'success')->count(),
    //             'failed' => collect($results)->where('status', 'failed')->count(),
    //         ],
    //         'details' => $results
    //     ]);
    // }

    public function bulk_quotation_approval(Request $request, $modelType, $id)
{
    $approvalModule = ApprovalModule::findOrFail($request->mc);

    $reqType = $request->type ?? '';
    $modelClass = $approvalModule->model_class ?? '';

    if (!class_exists($modelClass)) {
        abort(404, 'Model not found');
    }

    $modelDataIds = json_decode($request->model_data_ids, true);

    if (!is_array($modelDataIds)) {
        abort(400, 'Invalid model_data_ids format');
    }

    $results = [];
    $uniqueParents = [];

    // First, process all child records
    foreach ($modelDataIds as $dataId) {
        $record = $modelClass::find($dataId);

        if (!$record) {
            $results[] = [
                'id' => $dataId,
                'status' => 'failed',
                'message' => 'Record not found'
            ];
            continue;
        }

        $parentRecord = $record->purchase_quotation ?? null;
        if ($parentRecord) {
            $uniqueParents[$parentRecord->id] = $parentRecord;
        }

        if ($reqType == 'revert') {
            $record->am_change_made = 0;
            $record->save();

            $returnedChild = $record->revert($request->comments);
        } elseif ($reqType == 'reject') {
            $record->am_change_made = 0;
            $record->save();

            $returnedChild = $record->reject($request->comments);
        } else { // approve
            if ($record->canApprove()) {
                $returnedChild = $record->approve($request->comments);
            } else {
                $returnedChild = false;
            }
        }

        $childStatus = $returnedChild ? 'success' : 'failed';

        $results[] = [
            'child_id' => $record->id,
            'child_status' => $childStatus,
            'parent_id' => $parentRecord ? $parentRecord->id : null,
            'parent_status' => 'pending', // Will update after processing parents
            'message' => 'Child processed, parent pending'
        ];
    }

    // Now, process unique parents
    foreach ($uniqueParents as $parentId => $parentRecord) {
        if ($reqType == 'revert') {
            $parentRecord->am_change_made = 0;
            $parentRecord->save();

            $returnedParent = $parentRecord->revert($request->comments);
        } elseif ($reqType == 'reject') {
            $parentRecord->am_change_made = 0;
            $parentRecord->save();

            $returnedParent = $parentRecord->reject($request->comments);
        } else { // approve
            $NoRemainingPendingChild = $parentRecord->quotation_data()->where('am_approval_status', 'pending')->count() === 0;
                    // dd($NoRemainingPendingChild);

            if ($parentRecord->canApprove()) {
                if (!$NoRemainingPendingChild) { 
                    // dd("ok1");
                    $returnedParent = $parentRecord->partial_approve($request->comments);
                } else { 
                    // dd("ok2");

                    $returnedParent = $parentRecord->approve($request->comments);
                }
            } else {
                $returnedParent = false;
            }
        }

        $parentStatus = $returnedParent ? 'success' : 'failed';

        // Update results for all children of this parent
        foreach ($results as &$result) {
            if ($result['parent_id'] == $parentId) {
                $result['parent_status'] = $parentStatus;
                $result['message'] = match (true) {
                    $result['child_status'] === 'success' && $parentStatus === 'success' => 'Both child and parent processed successfully',
                    $result['child_status'] === 'success' && $parentStatus !== 'success' => 'Child processed, parent failed',
                    $result['child_status'] !== 'success' && $parentStatus === 'success' => 'Parent processed, child failed',
                    default => 'Both child and parent failed'
                };
            }
        }
    }

    // Handle cases where parent was skipped (no parent)
    foreach ($results as &$result) {
        if ($result['parent_status'] === 'pending') {
            $result['parent_status'] = 'skipped';
            $result['message'] = $result['child_status'] === 'success' ? 'Child processed, no parent' : 'Child failed, no parent';
        }
    }

    return response()->json([
        'summary' => [
            'total' => count($modelDataIds),
            'success' => collect($results)->filter(fn($r) => $r['child_status'] === 'success' || $r['parent_status'] === 'success')->count(),
            'failed' => collect($results)->filter(fn($r) => $r['child_status'] === 'failed' && $r['parent_status'] === 'failed')->count(),
        ],
        'details' => $results
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
