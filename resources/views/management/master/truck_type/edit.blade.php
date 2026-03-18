<form action="{{ route('truck-type.update', $ArrivalTruckType->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.truck-type') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{ $ArrivalTruckType->name }}" placeholder="Name"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Sample Money:</label>
                <input type="text" name="sample_money" value="{{ $ArrivalTruckType->sample_money ?? 0 }}"
                    placeholder="Sample Money" class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Weighbridge Amount (Fallback):</label>
                <input type="text" name="weighbridge_amount" value="{{ $ArrivalTruckType->weighbridge_amount ?? 0 }}"
                    placeholder="Weighbridge Amount (Fallback)" class="form-control" />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control">{{ $ArrivalTruckType->description }}</textarea>
            </div>
        </div>

        <div class="col-12">
            <h6 class="header-heading-sepration">Location Amounts</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Company Location</th>
                            <th style="width: 200px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locations ?? [] as $location)
                            @php
                                $existingAmount = $ArrivalTruckType->locationAmounts->where('id', $location->id)->first()->pivot->amount ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $location->name }}</td>
                                <td>
                                    <input type="number" name="location_amounts[{{ $location->id }}]" value="{{ $existingAmount }}" step="0.01" min="0" class="form-control" placeholder="Amount">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Status -->
        {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status" >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div> --}}
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>
