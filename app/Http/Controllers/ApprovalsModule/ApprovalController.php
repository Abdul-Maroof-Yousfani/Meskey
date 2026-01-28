<?php

namespace App\Http\Controllers\ApprovalsModule;

use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Support\Str;
use Schema;

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


        if($reqType == 'approve') {
            if (Schema::hasColumn($record->getTable(), 'is_qc_approved')) {
                $record->is_qc_approved = 'approved';
                $record->save();
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
        // dd($request->all());
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
            $purchaseRequest = $record->purchase_quotation->purchase_request ?? null;

            if (!$purchaseRequest) {
                abort(404, 'Purchase request not found.');
            }

            $purchaseRequestId = $purchaseRequest->id;
            $purchase_quotation_id = $record->purchase_quotation->id;


            $parentModelClass = get_class($record->purchase_quotation);

            $allParentIds = $parentModelClass::where('purchase_request_id', $purchaseRequestId)
                ->whereNotIn("am_approval_status", ["rejected", "approved"])
                ->pluck('id')
                ->toArray();

            $selectedParentIds = $modelClass::whereIn('id', json_decode($request->model_data_ids, true))
                ->pluck('purchase_quotation_id')
                ->unique()
                ->toArray();


            $unselectedParentIds = $parentModelClass::with(["quotation_data" => function($query) use ($request) {
                $query->whereNotIn("id", json_decode($request->model_data_ids))
                        ->whereIn("am_approval_status", ["pending", "reverted"]);
            }])->where("purchase_request_id", $purchaseRequestId)->first();
                                                    


            foreach($unselectedParentIds->quotation_data as $unselectedParent) {
                $unselectedParent->am_approval_status = "rejected";
                $unselectedParent->save();
            }
            $unselectedParent = $unselectedParentIds;
          
            $unselectedParentIds = array_diff($allParentIds, $selectedParentIds);
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
                        $returnedParent = $parentRecord->approve($request->comments);
                    } else {
                        // dd("ok2");

                        $returnedParent = $parentRecord->approve($request->comments);
                    }
                } else {
                    $returnedParent = false;
                }
            }

          

        }

        if($reqType != "reject") {
            foreach ($unselectedParentIds as $parentId) {
                // Use the parent model to find parent quotation
                $parent = $parentModelClass::find($parentId);
                if (!$parent) {
                    continue;
                }
    
                $parent->am_change_made = 0;
                $parent->save();
                $parent->reject($request->comments);
    
                $childIds = $modelClass::where('purchase_quotation_id', $parentId)
                    ->pluck('id')
                    ->toArray();
    
                if (!empty($childIds)) {
                    $modelClass::whereIn('id', $childIds)
                        ->each(function ($child) use ($request) {
                            $child->reject($request->comments);
                        });
                }
    
                $results[] = [
                    'parent_id' => $parent->id,
                    'status' => 'rejected (unselected)',
                    'action' => 'auto-rejected',
                ];
            }
        }


        return response()->json([
            'summary' => [
                'total' => count($modelDataIds),
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
