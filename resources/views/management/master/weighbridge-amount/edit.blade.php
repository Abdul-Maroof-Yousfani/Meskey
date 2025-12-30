<form action="{{ route('weighbridge-amount.update', $WeighbridgeAmount->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.weighbridge-amount') }}" />
    <div class="row form-mar">

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Location:</label>
                <select class="form-control select2" name="company_location_id" id="company_location_id">
                    <option value="">-- Select Location --</option>
                    @foreach ($CompanyLocations as $location)
                        <option value="{{ $location->id }}" {{ $location->id == $WeighbridgeAmount->company_location_id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Truck Type:</label>
                <select class="form-control select2" name="truck_type_id" id="truck_type_id">
                    <option value="">-- Select Truck Type --</option>
                    @foreach ($TruckTypes as $truckType)
                        <option value="{{ $truckType->id }}" {{ $truckType->id == $WeighbridgeAmount->truck_type_id ? 'selected' : '' }}>
                            {{ $truckType->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Weighbridge Amount:</label>
                <input type="number" name="weighbridge_amount" placeholder="Enter Amount" class="form-control" step="0.01" min="0"
                    value="{{ $WeighbridgeAmount->weighbridge_amount }}" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description (Optional)" class="form-control" rows="3">{{ $WeighbridgeAmount->description }}</textarea>
            </div>
        </div>

    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
