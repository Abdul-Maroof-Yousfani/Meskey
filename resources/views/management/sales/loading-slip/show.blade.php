<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Customer:</label>
                <input type="text" value="{{ $loadingSlip->customer ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Commodity:</label>
                <input type="text" value="{{ $loadingSlip->commodity ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>SO Qty:</label>
                <input type="text" value="{{ $loadingSlip->so_qty ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>DO Qty:</label>
                <input type="text" value="{{ $loadingSlip->do_qty ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Factory:</label>
                <select class="form-control select2 w-100" id="factory_display" multiple disabled style="width: 100% !important;">
                    {{-- @php
                        $deliveryOrder = $loadingSlip->loadingProgramItem->arrival_location_id ?? null;
                        if ($deliveryOrder && $deliveryOrder->arrival_location_id) {
                            $arrivalLocationIds = explode(',', $deliveryOrder->arrival_location_id);
                            $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                            foreach($arrivalLocations as $location) {
                                echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                            }
                        }
                    @endphp --}}
                    <option value="" selected>{{ $loadingSlip->factory }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Gala:</label>
                <select class="form-control select2 w-100" id="gala_display" multiple disabled style="width: 100% !important;">
                    {{-- @php
                        if ($deliveryOrder && $deliveryOrder->sub_arrival_location_id) {
                            $subArrivalLocationIds = explode(',', $deliveryOrder->sub_arrival_location_id);
                            $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
                            foreach($subArrivalLocations as $location) {
                                echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                            }
                        }
                    @endphp --}}
                    <option value="" selected>{{ $loadingSlip->gala }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Bag Size:</label>
                <input type="text" value="{{ $loadingSlip->bag_size ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>No. of Bags:</label>
                <input type="text" value="{{ $loadingSlip->no_of_bags ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="text" value="{{ $loadingSlip->kilogram ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Labour</label>
                <select name='labour' class='form-control select2'>
                    <option value='paid' @selected($loadingSlip->labour == 'paid')>Paid</option>
                    <option value='not_paid' @selected($loadingSlip->labour == 'not_paid')>Not Paid</option>    
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea class="form-control" readonly>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
        {{-- <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Created Date:</label>
                <input type="text" value="{{ $loadingSlip->created_at->format('d-m-Y H:i:s') }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div> --}}
    </div>
</div>
<script>
    $(".select2").select2();
    </script>