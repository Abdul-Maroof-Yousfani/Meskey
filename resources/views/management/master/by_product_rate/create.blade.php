@extends('management.layouts.master')
@section('title')
    Create Product Rate
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto mb-2">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title font-weight-bold text-dark">
                        Create Product Rate
                    </h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('by-product-rate.index') }}" class="btn btn-secondary">
                        <i class="ft-arrow-left mr-1"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border">
                <div class="card-body">
                    <form action="{{ route('by-product-rate.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
                        @csrf
                        <input type="hidden" id="listRefresh" value="{{ route('by-product-rate.index') }}" />

                        <div class="row form-mar">
                            <div class="col-xs-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Location: <span class="text-danger">*</span></label>
                                    <select name="location_id" id="location_id" class="form-control select2" required onchange="getSubLocations(this.value)">
                                        <option value="">Select Location</option>
                                        @foreach($companyLocations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Sub Location:</label>
                                    <select name="sublocation_id" id="sublocation_id" class="form-control select2" onchange="getPlants(this.value)">
                                        <option value="">Select Sub Location</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Plant:</label>
                                    <select name="plant_id" id="plant_id" class="form-control select2">
                                        <option value="">Select Plant</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Commodity / Product: <span class="text-danger">*</span></label>
                                    <select name="product_id" id="product_id" class="form-control select2" required onchange="loadCommodityByProducts(this.value)">
                                        <option value="">Select Commodity</option>
                                        @foreach($products as $prod)
                                            <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                                {{ $prod->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Selecting a commodity will automatically load all its linked B2 and By-Products.</small>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Title / Schedule Name:</label>
                                    <input type="text" name="title" placeholder="e.g. Irri-6 By-Products Standard Rates" class="form-control" autocomplete="off" />
                                    <small class="text-muted">Optional: Will auto-generate if left blank.</small>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-9">
                                <div class="form-group">
                                    <label class="font-weight-bold">Description / Notes:</label>
                                    <textarea name="description" placeholder="Optional remarks or notes about this rate schedule..." class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-3">
                                <div class="form-group pt-4">
                                    <input type="hidden" name="status" value="0">
                                    <div class="custom-control custom-switch custom-control-inline">
                                        <input type="checkbox" name="status" value="1" class="custom-control-input" id="statusSwitch" checked>
                                        <label class="custom-control-label font-weight-bold" for="statusSwitch">Active Status</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic B2 & By-Products Container -->
                            <div class="col-xs-12 col-sm-12 col-md-12 mt-1">
                                <div class="card border mb-2 shadow-none">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <h5 class="mb-0 font-weight-bold text-dark">
                                            B2 & By-Products Standard Recovery Rates
                                        </h5>
                                        <small class="text-muted">Enter rate per KG for each product</small>
                                    </div>
                                    <div class="card-body p-2" id="by_products_container">
                                        <div class="text-center text-muted py-4">
                                            Please select a <strong>Commodity / Product</strong> above to load its linked B2 & By-Products.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 text-right mt-2">
                                <a href="{{ route('by-product-rate.index') }}" class="btn btn-secondary mr-1">
                                    <i class="ft-x mr-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="saveBtn">
                                    <i class="ft-check mr-1"></i> Save Product Rates
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        let initialProdId = $('#product_id').val();
        if (initialProdId) {
            loadCommodityByProducts(initialProdId);
        }

        $('#location_id').on('change', function() {
            let currentProdId = $('#product_id').val();
            if (currentProdId) {
                loadCommodityByProducts(currentProdId);
            }
        });
    });

    function loadCommodityByProducts(productId) {
        if (!productId) {
            $('#by_products_container').html(`
                <div class="text-center text-muted py-4">
                    <i class="ft-arrow-up font-large-1 d-block mb-1 text-primary"></i>
                    Please select a <strong>Commodity / Product</strong> above to load its linked Products.
                </div>
            `);
            $('#saveBtn').prop('disabled', false).removeClass('disabled').removeAttr('title');
            return;
        }

        var locationId = $('#location_id').val();

        $('#by_products_container').html(`
            <div class="text-center text-primary py-4">
                <i class="fa fa-spinner fa-spin font-large-1 d-block mb-1"></i>
                Loading B2 & By-Products for selected commodity...
            </div>
        `);

        $.ajax({
            url: `{{ url('master/by-product-rate/get-by-products') }}/${productId}`,
            type: 'GET',
            data: {
                location_id: locationId
            },
            success: function(response) {
                $('#by_products_container').html(response);
            },
            error: function() {
                $('#by_products_container').html(`
                    <div class="alert alert-danger mb-0 p-2">
                        <i class="ft-alert-triangle mr-1"></i> Failed to fetch by-products. Please try again.
                    </div>
                `);
            }
        });
    }

    function getSubLocations(locationId) {
        $('#sublocation_id').empty().append('<option value="">Select Sub Location</option>').trigger('change');
        $('#plant_id').empty().append('<option value="">Select Plant</option>').trigger('change');
        
        if (locationId) {
            $.ajax({
                url: `{{ url('master/by-product-rate/get-sub-locations') }}/${locationId}`,
                type: 'GET',
                success: function(res) {
                    var html = '<option value="">Select Sub Location</option>';
                    $.each(res, function(i, v) {
                        html += `<option value="${v.id}">${v.name}</option>`;
                    });
                    $('#sublocation_id').html(html).trigger('change');
                }
            });
        }
    }

    function getPlants(subLocationId) {
        $('#plant_id').empty().append('<option value="">Select Plant</option>').trigger('change');
        
        if (subLocationId) {
            $.ajax({
                url: `{{ url('master/by-product-rate/get-plants') }}/${subLocationId}`,
                type: 'GET',
                success: function(res) {
                    var html = '<option value="">Select Plant</option>';
                    $.each(res, function(i, v) {
                        html += `<option value="${v.id}">${v.name}</option>`;
                    });
                    $('#plant_id').html(html).trigger('change');
                }
            });
        }
    }
</script>
@endsection
