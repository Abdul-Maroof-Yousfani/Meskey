<form method="POST" action="{{route('payment-term.update', $paymentTerm->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.payment-terms') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Title:</label>
                <input type="text" name="title" value="{{$paymentTerm->title}}" placeholder="Title" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Description:</label>
                <input type="text" name="desc" value="{{$paymentTerm->desc}}" placeholder="Name" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status</label>
                <select name="status" class="select2 form-control">
                    <option value="active" @selected($paymentTerm->status == 'active')>Active</option>
                    <option value="inactive" @selected($paymentTerm->status == 'inactive')>In-active</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>