<div class="row">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            QC Checklist
        </h6>
    </div>
</div>
<div class="striped-rows">
    @if (count($slabs) != 0)
        @foreach ($slabs as $slab)
            <div class="form-group row">
                <input type="hidden" name="product_slab_type_id[]" value="{{$slab->slabType->id}}">
                <label class="col-md-3 label-control font-weight-bold" for="striped-form-1">{{$slab->slabType->name}}</label>
                <div class="col-md-9">
                    <input type="text" id="striped-form-1" class="form-control" name="checklist_value[]" placeholder="%">
                </div>
            </div>
        @endforeach 
    @else
        <div class="alert alert-warning">
            No Slabs Found
        </div>
    @endif
</div>