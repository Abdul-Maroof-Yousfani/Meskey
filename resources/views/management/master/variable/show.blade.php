<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Title:</label>
            <input type="text" value="{{$variable->title}}" class="form-control" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Description:</label>
            <textarea class="form-control" rows="3" readonly>{{$variable->description}}</textarea>
        </div>
    </div>
</div>
<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
