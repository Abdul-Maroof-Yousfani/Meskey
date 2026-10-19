<div class="row form-mar">
    @if ($millingRate->isExpired() && !$millingRate->hasSuccessor())
        <div class="col-12">
            <div class="alert alert-danger mb-3 p-2 font-weight-bold" role="alert">
                <i class="ft-alert-triangle mr-2"></i>
                <strong>Attention:</strong> This milling rate expired on {{ $millingRate->to_date ? $millingRate->to_date->format('d M, Y') : '-' }} and no subsequent active milling rate is configured for Commodity "{{ $millingRate->product->name ?? 'N/A' }}".
            </div>
        </div>
    @endif

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Location:</label>
            <input type="text" value="{{ $millingRate->location->name ?? '' }}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Sub Location:</label>
            <input type="text" value="{{ $millingRate->subLocation->name ?? '' }}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Plant:</label>
            <input type="text" value="{{ $millingRate->plant->name ?? '' }}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Commodity / Product:</label>
            <input type="text" value="{{ $millingRate->product->name ?? 'N/A' }}" class="form-control font-weight-bold text-primary" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Effective From Date:</label>
            <input type="text" value="{{ $millingRate->from_date ? $millingRate->from_date->format('d M, Y') : 'N/A' }}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label>Effective To Date:</label>
            <input type="text" value="{{ $millingRate->to_date ? $millingRate->to_date->format('d M, Y') : 'N/A' }}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Title:</label>
            <input type="text" value="{{$millingRate->title}}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Description:</label>
            <textarea class="form-control" rows="3" readonly>{{$millingRate->description}}</textarea>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Status:</label>
            <input type="text" value="{{$millingRate->status == 1 ? 'Active' : 'Pending'}}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
        @if($millingRate->variables->count() > 0)
            <div class="border rounded p-3">
                <div class="row align-items-center mb-1">
                    <div class="col-md-5">
                        <label class="font-weight-bold text-uppercase mb-0">Variable Name</label>
                    </div>
                    <div class="col-md-7">
                        <label class="font-weight-bold text-uppercase mb-0">Value</label>
                    </div>
                </div>
                <hr class="mt-1 mb-3">
                @foreach($millingRate->variables as $variable)
                    <div class="row align-items-center mb-3">
                        <div class="col-md-5">
                            <label class="mb-0 pt-1"><i class="ft-check-circle text-success mr-1"></i>{{ $variable->title }}</label>
                        </div>
                        <div class="col-md-7">
                            <input type="text" class="form-control bg-white" value="{{ $variable->pivot->value }}" readonly />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p>No variables configured.</p>
        @endif
    </div>

</div>
<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
