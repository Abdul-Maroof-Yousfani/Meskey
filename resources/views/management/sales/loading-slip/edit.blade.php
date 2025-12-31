<form action="{{ route('sales.loading-slip.update', $loadingSlip->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-slip') }}" />

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
                <input type="text" name="factory" value="{{ $loadingSlip->factory ?? '' }}" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Gala:</label>
                <input type="text" name="gala" value="{{ $loadingSlip->gala ?? '' }}" class="form-control" readonly />
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
                <input type="number" name="no_of_bags" id="no_of_bags" value="{{ $loadingSlip->no_of_bags }}" class="form-control" min="1" required>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="number" name="kilogram" id="kilogram" value="{{ $loadingSlip->kilogram ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Created By:</label>
                <input type="text" value="{{ $loadingSlip->createdBy->name ?? 'N/A' }}" class="form-control" readonly />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3">{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Created Date:</label>
                <input type="text" value="{{ $loadingSlip->created_at->format('d-m-Y H:i:s') }}" class="form-control" readonly />
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a href="{{ route('sales.loading-slip.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
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
