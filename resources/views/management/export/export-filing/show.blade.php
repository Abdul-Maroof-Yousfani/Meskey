<div class="row form-mar">
    <div class="col-md-6">
        <div class="form-group">
            <label>Export Order:</label>
            <input type="text" class="form-control" value="{{ $filing->exportOrder->voucher_no ?? '-' }}" disabled>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Buyer:</label>
            <input type="text" class="form-control" value="{{ $filing->exportOrder->buyer->name ?? '-' }}" disabled>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Created By:</label>
            <input type="text" class="form-control" value="{{ $filing->createdBy->name ?? '-' }}" disabled>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Created At:</label>
            <input type="text" class="form-control" value="{{ $filing->created_at?->format('d-m-Y h:i A') }}" disabled>
        </div>
    </div>
    @if ($filing->remarks)
        <div class="col-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea class="form-control" rows="2" disabled>{{ $filing->remarks }}</textarea>
            </div>
        </div>
    @endif
</div>

<div class="row">
    <div class="col-12">
        <h6>Documents</h6>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Document Name</th>
                    <th>File</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filing->documents as $key => $document)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $document->document_name }}</td>
                        <td>{{ $document->file_name }}</td>
                        <td>
                            <a href="{{ asset($document->file_path) }}" target="_blank" class="info p-1">
                                <i class="ft-eye font-medium-3"></i>
                            </a>
                            <a href="{{ asset($document->file_path) }}" download="{{ $document->file_name }}" class="success p-1">
                                <i class="ft-download font-medium-3"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No documents</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
