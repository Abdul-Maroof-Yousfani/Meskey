<form method="POST" action="{{ route('production-voucher.output.update', [$productionVoucher->id, $productionOutput->id]) }}" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" name="production_voucher_id" id="output_production_voucher_id" value="{{ $productionVoucher->id ?? '' }}">
    <input type="hidden" name="output_id" id="output_id" value="{{ $productionOutput->id ?? '' }}">
    <!-- <input type="hidden" id="url" value="{{ route('production-voucher.edit', $productionVoucher->id) }}" /> -->
    <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher-outputs', $productionVoucher->id) }}" data-url="{{ route('get.production-voucher-outputs', $productionVoucher->id) }}" data-appenddiv="productionOutputsTable" data-formid="productionOutputsFilterForm" data-loadmore="false" />
    
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Output</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Production Slot: <span class="text-danger">*</span></label>
                        <select name="slot_id" id="output_slot_id" class="form-control select2" required>
                            <option value="">Select Slot</option>
                            @if(isset($slots))
                                @foreach($slots as $slot)
                                    <option value="{{ $slot->id }}" {{ $productionOutput->slot_id == $slot->id ? 'selected' : '' }}>
                                        {{ $slot->date ? $slot->date->format('Y-m-d') : '' }} - 
                                        {{ $slot->start_time ?? '' }} 
                                        @if($slot->end_time)
                                            to {{ $slot->end_time }}
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="output_product_id" class="form-control select2" required>
                            <option value="">Select Commodity</option>
                            @if(isset($products))
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ $productionOutput->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Qty (kg):</label>
                        <input type="number" name="qty" id="output_qty" class="form-control" step="0.01" min="0.01" value="{{ $productionOutput->qty ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>No of Bags:</label>
                        <input type="number" name="no_of_bags" id="output_no_of_bags" class="form-control" step="1" min="0" value="{{ $productionOutput->no_of_bags ?? '' }}" onchange="calculateAvgWeight()">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Bag Size:</label>
                        <select name="bag_size" id="output_bag_size" class="form-control select2">
                            <option value="">Select Bag Size</option>
                            <option value="100g" {{ ($productionOutput->bag_size ?? '') == '100g' ? 'selected' : '' }}>100g</option>
                            <option value="1kg" {{ ($productionOutput->bag_size ?? '') == '1kg' ? 'selected' : '' }}>1kg</option>
                            <option value="5kg" {{ ($productionOutput->bag_size ?? '') == '5kg' ? 'selected' : '' }}>5kg</option>
                            <option value="10kg" {{ ($productionOutput->bag_size ?? '') == '10kg' ? 'selected' : '' }}>10kg</option>
                            <option value="15kg" {{ ($productionOutput->bag_size ?? '') == '15kg' ? 'selected' : '' }}>15kg</option>
                            <option value="25kg" {{ ($productionOutput->bag_size ?? '') == '25kg' ? 'selected' : '' }}>25kg</option>
                            <option value="50kg" {{ ($productionOutput->bag_size ?? '') == '50kg' ? 'selected' : '' }}>50kg</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Avg Weight per Bag (kg):</label>
                        <input type="number" name="avg_weight_per_bag" id="output_avg_weight_per_bag" class="form-control" step="0.001" min="0" value="{{ $productionOutput->avg_weight_per_bag ?? '' }}" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Storage Location:</label>
                        <select name="arrival_sub_location_id" id="output_arrival_sub_location_id" class="form-control select2" required>
                            <option value="">Select Storage Location</option>
                            @if(isset($arrivalSubLocations))
                                @foreach($arrivalSubLocations as $subLocation)
                                    <option value="{{ $subLocation->id }}" {{ $productionOutput->arrival_sub_location_id == $subLocation->id ? 'selected' : '' }}>
                                        {{ $subLocation->name }} ({{ $subLocation->arrivalLocation->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_id" id="output_job_order_id" class="form-control select2" onchange="loadBrandsByJobOrder()">
                            <option value="">Select Job Order (Optional)</option>
                            @if(isset($jobOrders) && count($jobOrders) > 0)
                                @foreach($jobOrders as $jobOrder)
                                    <option value="{{ $jobOrder->id }}" {{ $productionOutput->job_order_id == $jobOrder->id ? 'selected' : '' }}>
                                        {{ $jobOrder->job_order_no }} - {{ $jobOrder->ref_no ?? '' }}
                                    </option>
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
                                    <option value="{{ $brand->id }}" {{ $productionOutput->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>



                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" id="output_remarks" class="form-control" rows="3">{{ $productionOutput->remarks ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Production Output</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();
        
        // Calculate avg weight when qty or no_of_bags changes
        $('#output_qty, #output_no_of_bags').on('input change', function() {
            calculateAvgWeight();
        });

        // Load brands on page load if job order is selected
        const jobOrderId = $('#output_job_order_id').val();
        if (jobOrderId) {
            loadBrandsByJobOrder();
        }
    });

    function calculateAvgWeight() {
        var qty = parseFloat($('#output_qty').val()) || 0;
        var noOfBags = parseInt($('#output_no_of_bags').val()) || 0;
        
        if (noOfBags > 0 && qty > 0) {
            var avgWeight = qty / noOfBags;
            $('#output_avg_weight_per_bag').val(avgWeight.toFixed(3));
        } else {
            $('#output_avg_weight_per_bag').val('');
        }
    }

    function loadBrandsByJobOrder() {
        const jobOrderId = $('#output_job_order_id').val();
        const brandSelect = $('#output_brand_id');
        const currentBrandId = '{{ $productionOutput->brand_id ?? "" }}';

        // Clear existing options
        brandSelect.empty().append('<option value="">Select Brand</option>');

        // if (!jobOrderId) {
        //     brandSelect.trigger('change');
        //     return;
        // }

        // Show loading
        brandSelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-brands-by-job-orders") }}',
            method: 'POST',
            data: {
                job_order_ids: jobOrderId ? jobOrderId : null,    
                location_id: '{{ $productionVoucher->location_id ?? "" }}',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.brands && response.brands.length > 0) {
                    $.each(response.brands, function(index, brand) {
                        const selected = (currentBrandId && brand.id == currentBrandId) ? 'selected' : '';
                        brandSelect.append(
                            $('<option></option>')
                                .attr('value', brand.id)
                                .attr('selected', selected)
                                .text(brand.name)
                        );
                    });
                } else {
                    brandSelect.append('<option value="">No Brands Found</option>');
                }
                brandSelect.trigger('change');
            },
            error: function(xhr) {
                console.error('Error loading brands:', xhr);
                brandSelect.append('<option value="">Error loading brands</option>');
            },
            complete: function() {
                brandSelect.prop('disabled', false);
            }
        });
    }
</script>

