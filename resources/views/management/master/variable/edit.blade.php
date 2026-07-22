<form method="POST" action="{{route('variable.update', $variable->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.variables') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Title:</label>
                <input type="text" name="title" value="{{$variable->title}}" placeholder="Title" class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3">{{$variable->description}}</textarea>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <input type="hidden" name="status" value="0">
                <label>
                    <input type="checkbox" name="status" value="1" {{$variable->status == 1 ? 'checked' : ''}}> Active
                </label>
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
