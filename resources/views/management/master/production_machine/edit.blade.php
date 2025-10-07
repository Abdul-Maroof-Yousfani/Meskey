<form method="POST" action="{{route('production-machine.update', $productionMachine->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Company Location:</label>
                <select id="company_location_id" name="company_location_id" class="form-control select2" required>
                    <option value="">Select Location</option>
                    @foreach ($companyLocations as $companyLocation)
                        <option 
                            value="{{ $companyLocation->id }}" 
                            {{ $productionMachine->company_location_id == $companyLocation->id ? 'selected' : '' }}>
                            {{ $companyLocation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Arrival Location:</label>
                <select id="arrival_location_id" name="arrival_location_id" class="form-control select2" required>
                    <option value="">Select Location</option>
                    @foreach ($arrivalLocations as $arrivalLocation)
                        <option 
                            value="{{ $arrivalLocation->id }}" 
                            {{ $productionMachine->arrival_location_id == $arrivalLocation->id ? 'selected' : '' }}>
                            {{ $arrivalLocation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" 
                    name="name" 
                    value="{{ $productionMachine->name }}" 
                    placeholder="Name" 
                    class="form-control" 
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control">{{ $productionMachine->description }}</textarea>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{ $productionMachine->status == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ $productionMachine->status == 'inactive' ? 'selected' : '' }} value="inactive">Inactive
                    </option>
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
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>