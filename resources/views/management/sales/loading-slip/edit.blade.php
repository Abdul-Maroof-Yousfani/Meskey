<form action="{{ route('sales.loading-slip.update', $loadingSlip->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-slip') }}" />

    @if(isset($rejectedDispatchQc) && $rejectedDispatchQc)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="ft-alert-triangle"></i> Dispatch QC Rejected!</strong>
                <p class="mb-0 mt-1">This loading slip's Dispatch QC has been rejected. Please review and update the loading slip details.</p>
                @if($rejectedDispatchQc->qc_remarks)
                <hr>
                <strong>QC Remarks:</strong>
                <p class="mb-0">{{ $rejectedDispatchQc->qc_remarks }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(isset($canEdit) && !$canEdit)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <strong><i class="ft-info"></i> Read Only</strong>
                <p class="mb-0">This loading slip cannot be edited because its Dispatch QC has been accepted.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <input type="text" class="form-control" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? '' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? '' }}" readonly>
                <input type="hidden" name="loading_program_item_id" value="{{ $loadingSlip->loading_program_item_id }}">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Customer:</label>
                <input type="text" name="customer" value="{{ $loadingSlip->customer ?? '' }}" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Commodity:</label>
                <input type="text" name="commodity" value="{{ $loadingSlip->commodity ?? '' }}" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>SO Qty:</label>
                <input type="number" name="so_qty" value="{{ $loadingSlip->so_qty ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>DO Qty:</label>
                <input type="number" name="do_qty" value="{{ $loadingSlip->do_qty ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Factory:</label>
                <select class="form-control select2 w-100" name="factory_display[]" id="factory_display" multiple disabled style="width: 100% !important;">
                    {{-- @php
                        $deliveryOrder = $loadingSlip->loadingProgramItem->loadingProgram->deliveryOrder ?? null;
                        if ($deliveryOrder && $deliveryOrder->arrival_location_id) {
                            $arrivalLocationIds = explode(',', $deliveryOrder->arrival_location_id);
                            $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                            foreach($arrivalLocations as $location) {
                                echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                            }
                        }
                    @endphp --}}
                    <option value="" selected>{{ $loadingSlip->factory ?? '' }}</option>
                </select>
                <input type="hidden" name="factory" value="{{ $loadingSlip->factory ?? '' }}" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Gala:</label>
                <select class="form-control select2 w-100" name="gala_display[]" id="gala_display" multiple disabled style="width: 100% !important;">
        
                    <option value="" selected>{{ $loadingSlip->gala ?? '' }}</option>
                </select>
                <input type="hidden" name="gala" value="{{ $loadingSlip->gala ?? '' }}" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Bag Size:</label>
                <input type="number" name="bag_size" value="{{ $loadingSlip->bag_size ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>No. of Bags: <span class="text-danger">*</span></label>
                <input type="number" name="no_of_bags" id="no_of_bags" value="{{ $loadingSlip->no_of_bags }}" class="form-control" min="1" required {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="number" name="kilogram" id="kilogram" value="{{ $loadingSlip->kilogram ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Labour</label>
                <select name='labour' class='form-control select2' {{ (isset($canEdit) && !$canEdit) ? 'disabled' : '' }}>
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
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3" {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a href="{{ route('sales.loading-slip.index') }}" class="btn btn-secondary">Cancel</a>
            @if(isset($canEdit) && $canEdit)
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
            @else
            <button type="button" class="btn btn-secondary" disabled>Editing Disabled</button>
            @endif
        </div>
    </div>
</form>

@if(isset($loadingSlip) && $loadingSlip->logs->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ft-clock"></i> Edit History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>No. of Bags</th>
                        <th>Kilogram</th>
                        <th>QC Remarks</th>
                        <th>Edited By</th>
                        <th>Edited At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loadingSlip->logs->sortByDesc('created_at') as $index => $log)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $log->no_of_bags }}</td>
                        <td>{{ number_format($log->kilogram, 2) }}</td>
                        <td>{{ $log->qc_remarks ?? '-' }}</td>
                        <td>{{ $log->editedBy->name ?? 'N/A' }}</td>
                        <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<script>
    $(document).ready(function() {
        $(".select2").select2();
        // Calculate kilogram when no_of_bags changes
        $('#no_of_bags').on('input', function() {
            calculateKilogram();
        });

        function calculateKilogram() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
        }
    });
</script>
