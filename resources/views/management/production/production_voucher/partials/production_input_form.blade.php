<form id="productionInputForm" method="POST" autocomplete="off">
    @csrf
    <input type="hidden" name="production_voucher_id" id="production_voucher_id" value="{{ $productionVoucher->id ?? '' }}">
    <input type="hidden" name="input_id" id="input_id">

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Input</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="input_product_id" class="form-control select2" required>
                            <option value="">Select Commodity</option>
                            @if(isset($products))
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" id="input_location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @if(isset($sublocations))
                                @foreach($sublocations as $sublocation)
                                    <option value="{{ $sublocation->id }}">{{ $sublocation->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Qty (kg):</label>
                        <input type="number" name="qty" id="input_qty" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" id="input_remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- Listings Table (for refresh functionality) -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="header-heading-sepration">Available Listings</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="inputListingsTable">
                            <thead>
                                <tr>
                                    <th>Commodity</th>
                                    <th>Location</th>
                                    <th>Available Qty (kg)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <button type="button" class="btn btn-sm btn-info" onclick="refreshInputListings()">
                                            <i class="ft-refresh-cw"></i> Refresh Listings
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="button" class="btn btn-primary" onclick="saveProductionInput()">Save Production Input</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();
    });

    function refreshInputListings() {
        const voucherId = $('#production_voucher_id').val();
        const productId = $('#input_product_id').val();
        const locationId = $('#input_location_id').val();

        if (!voucherId) {
            alert('Please select a production voucher first');
            return;
        }

        // AJAX call to refresh listings
        $.ajax({
            url: '{{ route("get.production-voucher") }}',
            method: 'POST',
            data: {
                voucher_id: voucherId,
                product_id: productId,
                location_id: locationId,
                action: 'refresh_input_listings'
            },
            success: function(response) {
                // Update listings table
                $('#inputListingsTable tbody').html(response.html);
            }
        });
    }

    function saveProductionInput() {
        const form = $('#productionInputForm');
        const voucherId = $('#production_voucher_id').val();
        const inputId = $('#input_id').val();
        const url = inputId 
            ? '{{ route("production-voucher.input.update", [":id", ":inputId"]) }}'.replace(':id', voucherId).replace(':inputId', inputId)
            : '{{ route("production-voucher.input.store", ":id") }}'.replace(':id', voucherId);
        const method = inputId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showNotification('success', response.success);
                // Reload the page or update the table
                location.reload();
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                showNotification('error', 'Please fix the errors');
            }
        });
    }
</script>

