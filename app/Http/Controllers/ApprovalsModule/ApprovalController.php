<?php

namespace App\Http\Controllers\ApprovalsModule;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApprovalController extends Controller
{
    public function approve(Request $request, $model, $id)
    {
        $modelClass = "App\\Models\\" . str_replace('_', '', ucwords($model, '_'));

        if (!class_exists($modelClass)) {
            abort(404, 'Model not found');
        }

        $record = $modelClass::findOrFail($id);

        if (!$record->canApprove()) {
            abort(403, 'You cannot approve this record');
        }

        $approved = $record->approve($request->comments);

        if ($approved) {
            return redirect()->back()->with('success', 'Approved successfully');
        }

        return redirect()->back()->with('error', 'Approval failed');
    }

    public function reject(Request $request, $model, $id)
    {
        $modelClass = "App\\Models\\" . str_replace('_', '', ucwords($model, '_'));

        if (!class_exists($modelClass)) {
            abort(404, 'Model not found');
        }

        $record = $modelClass::findOrFail($id);

        $rejected = $record->reject($request->comments);

        if ($rejected) {
            return redirect()->back()->with('success', 'Rejected successfully');
        }

        return redirect()->back()->with('error', 'Rejection failed');
    }
}
