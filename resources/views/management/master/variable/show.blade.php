<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Title:</label>
            <input type="text" value="{{$variable->title}}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Linked COA Account (Accrued Operational Liability):</label>
            @if($variable->account)
                <div class="p-2 border rounded bg-light">
                    <strong class="text-primary">{{ $variable->account->name }}</strong>
                    <div class="mt-1">
                        <span class="badge badge-info mr-1">Code: {{ $variable->account->unique_no }}</span>
                        <span class="badge badge-secondary mr-1">Hierarchy Path: {{ $variable->account->hierarchy_path }}</span>
                        <span class="badge badge-warning text-uppercase">Type: {{ $variable->account->account_type }}</span>
                    </div>
                </div>
            @else
                <input type="text" value="Not linked to COA" class="form-control text-muted" readonly />
            @endif
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Description:</label>
            <textarea class="form-control" rows="3" readonly>{{$variable->description}}</textarea>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Status:</label>
            <div>
                <span class="badge badge-{{ $variable->status == 1 ? 'success' : 'danger' }} font-medium-1 px-2 py-1">
                    {{ $variable->status == 1 ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>
</div>
<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton" data-dismiss="modal">Close</a>
    </div>
</div>
