<form action="{{ route('payment-term.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.payment-terms') }}" />

    <div class="row form-mar">
        
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Payment Term:</label>
                <input type="text" name="desc" placeholder="Payment Term" class="form-control" autocomplete="off" />
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