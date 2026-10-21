<div class="row form-mar">


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
            <div>
                @if ($millingRate->isExpired() && !$millingRate->hasSuccessor())
                    <span class="badge badge-danger font-medium-1 px-2 py-1"><i class="ft-alert-circle mr-1"></i> Expired (No Active Rate)</span>
                @elseif ($millingRate->status == 1)
                    <span class="badge badge-success font-medium-1 px-2 py-1">Active</span>
                @else
                    <span class="badge badge-warning font-medium-1 px-2 py-1">Inactive</span>
                @endif
            </div>
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
                            <label class="mb-0 pt-1">
                                <i class="ft-check-circle text-success mr-1"></i>
                                <strong>{{ $variable->title }}</strong>
                                @if($variable->account)
                                    <small class="badge badge-light border ml-1 font-weight-normal text-muted">{{ $variable->account->hierarchy_path }}</small>
                                @endif
                            </label>
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
