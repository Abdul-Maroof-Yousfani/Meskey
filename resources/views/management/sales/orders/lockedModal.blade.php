<div class="modal-body p-4 text-center">
    <div class="my-3">
        <span class="badge rounded-circle p-3 mb-3" style="background-color: #fff3cd; color: #856404; font-size: 32px; display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px;">
            <i class="fa fa-lock"></i>
        </span>
        <h4 class="font-weight-bold text-dark mt-2">Sale Order Locked for Editing</h4>
        <div class="alert alert-warning text-left mt-3 p-3" style="border-radius: 6px; font-size: 14px; line-height: 1.6;">
            <strong><i class="fa fa-info-circle me-1"></i> Stage 1 Approved:</strong>
            Sale Order <strong>{{ $sale_order->reference_no }}</strong> has already been approved at <strong>Stage 1 (Branch / Parent)</strong> and is currently awaiting final approval from <strong>Head Office</strong>.
            <hr class="my-2">
            <span>To maintain data integrity, editing is disabled while awaiting Head Office approval. If any modification is required, please ask the Head Office authority to <strong>Revert</strong> the request.</span>
        </div>
    </div>
    {{-- <div class="d-flex justify-content-center mt-3">
        <button type="button" class="btn btn-secondary px-4 py-2" data-dismiss="modal" data-bs-dismiss="modal" onclick="$(this).closest('.modal').modal('hide');">
            <i class="fa fa-times me-1"></i> Close
        </button>
    </div> --}}
    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
        </div>
    </div>
</div>
