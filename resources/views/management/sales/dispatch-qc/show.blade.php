

<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $DispatchQc->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $DispatchQc->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Customer:</label>
                <input type="text" value="{{ $DispatchQc->customer ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Commodity:</label>
                <input type="text" value="{{ $DispatchQc->commodity ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>SO Qty:</label>
                <input type="text" value="{{ $DispatchQc->so_qty ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>DO Qty:</label>
                <input type="text" value="{{ $DispatchQc->do_qty ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Factory:</label>
                <input type="text" value="{{ $DispatchQc->factory ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Gala:</label>
                <input type="text" value="{{ $DispatchQc->gala ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea class="form-control" readonly>{{ $DispatchQc->qc_remarks }}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <input type="text" value="{{ ucfirst($DispatchQc->status) }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    @if($DispatchQc->attachments->count() > 0)
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Attachments:</label>
                <div class="row">
                    @foreach($DispatchQc->attachments as $attachment)
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body text-center">
                                    @if(Str::contains($attachment->file_type, ['image']))
                                        <img src="{{ asset($attachment->file_path) }}" alt="{{ $attachment->file_name }}" class="img-fluid rounded" style="max-height: 100px;">
                                    @else
                                        <i class="ft-file-text font-large-2"></i>
                                    @endif
                                    <p class="mt-1 mb-1">{{ Str::limit($attachment->file_name, 20) }}</p>
                                    <a href="{{ asset($attachment->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>


