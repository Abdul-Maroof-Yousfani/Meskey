<form id="productionOutputForm" method="POST" autocomplete="off">
    @csrf
    <input type="hidden" name="production_voucher_id" id="output_production_voucher_id" value="{{ $productionVoucher->id ?? '' }}">
    <input type="hidden" name="output_id" id="output_id">

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Output</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="output_product_id" class="form-control select2" required>
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
                        <label>Qty (kg):</label>
                        <input type="number" name="qty" id="output_qty" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Storage Location:</label>
                        <select name="storage_location_id" id="output_storage_location_id" class="form-control select2" required>
                            <option value="">Select Storage Location</option>
                            @if(isset($companyLocations))
                                @foreach($companyLocations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Brand:</label>
                        <select name="brand_id" id="output_brand_id" class="form-control select2">
                            <option value="">Select Brand</option>
                            @if(isset($brands))
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" id="output_remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="button" class="btn btn-primary" onclick="saveProductionOutput()">Save Production Output</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();
    });

    function saveProductionOutput() {
        const form = $('#productionOutputForm');
        const voucherId = $('#output_production_voucher_id').val();
        const outputId = $('#output_id').val();
        const url = outputId 
            ? '{{ route("production-voucher.output.update", [":id", ":outputId"]) }}'.replace(':id', voucherId).replace(':outputId', outputId)
            : '{{ route("production-voucher.output.store", ":id") }}'.replace(':id', voucherId);
        const method = outputId ? 'PUT' : 'POST';

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

