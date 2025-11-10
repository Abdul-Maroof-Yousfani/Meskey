<form method="POST" action="{{route('size.update', $size->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.sizes') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Siz:</label>
                <input type="text" name="size" value="{{$size->size}}" placeholder="Size" class="form-control" autocomplete="off" />
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