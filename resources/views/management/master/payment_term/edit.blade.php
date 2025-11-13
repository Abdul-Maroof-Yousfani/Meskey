<form method="POST" action="{{route('payment-term.update', $paymentTerm->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.payment-terms') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Name:</label>
                <input type="text" name="desc" value="{{$paymentTerm->desc}}" placeholder="Name" class="form-control" autocomplete="off" />
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