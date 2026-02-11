<form action="{{ route('labour-rates.update', ['labour_rate' => $labourRate]) }}" id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field("PUT") }}
    <input type="hidden" id="listRefresh" value="{{ route('get.labour-rate') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Rate:</label>
                <input type="text" name="rate" placeholder="Rate" class="form-control" value="{{ $labourRate->rate }}"/>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Packing:</label>
                <select class="form-control select2" name="bag_packing">
                    @foreach($bag_packings as $bag_packing)
                        <option value="{{ $bag_packing->id }}" @selected($labourRate->bag_packing_id == $bag_packing->id)>{{ $bag_packing->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Commodity:</label>
                <select class="form-control select2" name="category_id">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($labourRate->category_id == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Factories:</label>
                <select class="form-control select2" name="factory_id">
                    @foreach($factories as $factory)
                        <option value="{{ $factory->id }}" @selected($labourRate->factory_id == $factory->id)>{{ $factory->name }}</option>
                    @endforeach
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

<script>
    $(".select2").select2();
</script>
