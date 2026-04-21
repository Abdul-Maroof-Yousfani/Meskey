<form action="{{ route('store.bag-issuance.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.bag-issuance') }}" />

    <div class="row">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Issuance Information</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Bag Request:</label>
                        <select name="bag_request_id" id="bag_request_id" class="form-control select2">
                            <option value="">Select Bag Request</option>
                            @if(isset($bag_requests))
                                @foreach($bag_requests as $br)
                                    <option value="{{ $br->id }}">{{ $br->request_number }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Issuance Date:</label>
                        <input type="date" name="issuance_date" id="issuance_date" class="form-control" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" id="issuance_number_container">
                        <label>Issuance Number:</label>
                        <input type="text" name="issuance_number" id="issuance_number" class="form-control" readonly value="{{ $issuance_number }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Company Location:</label>
                        <select name="company_location_id" id="company_location_id" class="form-control select2">
                            <option value="">Select Location</option>
                            @if(isset($company_locations))
                                @foreach($company_locations as $location)
                                    <option value="{{ $location->id }}" {{ session('company_location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Arrival Location:</label>
                        <select name="arrival_location_id" id="arrival_location_id" class="form-control select2">
                            <option value="">Select Arrival Location</option>
                            @if(isset($arrival_locations))
                                @foreach($arrival_locations as $al)
                                    <option value="{{ $al->id }}">{{ $al->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Gala:</label>
                        <select name="gala_id" id="gala_id" class="form-control select2">
                            <option value="">Select Gala</option>
                            @if(isset($galas))
                                @foreach($galas as $gala)
                                    <option value="{{ $gala->id }}">{{ $gala->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_ids[]" id="job_order_ids" class="form-control select2" multiple data-placeholder="Select Job Orders">
                            @if(isset($jobOrders))
                                @foreach($jobOrders as $jo)
                                    <option value="{{ $jo->id }}">{{ $jo->job_order_no }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter Remarks"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <h6 class="header-heading-sepration">
                Item Details
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 20%">Item</th>
                            <th style="width: 15%">Brand</th>
                            <th style="width: 10%">Packing Size (Kg)</th>
                            <th style="width: 10%">Requested Qty</th>
                            <th style="width: 10%">Balance Qty</th>
                            <th style="width: 10%">Issuance Qty</th>
                            <th style="width: 10%">Unit</th>
                            <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td colspan="8" class="text-center">Select Bag Request to load items</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-danger modal-sidebar-close closebutton" data-dismiss="modal">Close</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#ajaxSubmit')
        });

        $('#issuance_date').on('change', function() {
            let date = $(this).val();
            let requestId = $('#bag_request_id').val();
            if (requestId) {
                $.ajax({
                    url: '{{ route("store.bag-issuance.get-number") }}',
                    method: 'GET',
                    data: { contract_date: date },
                    success: function(response) {
                        $('#issuance_number').val(response.issuance_number);
                    }
                });
            }
        });

        $('#bag_request_id').on('change', function() {
            let requestId = $(this).val();
            if (requestId) {
                // Fetch Issuance Number when request is selected
                let date = $('#issuance_date').val();
                $.ajax({
                    url: '{{ route("store.bag-issuance.get-number") }}',
                    method: 'GET',
                    data: { contract_date: date },
                    success: function(response) {
                        $('#issuance_number').val(response.issuance_number);
                    }
                });

                $('#itemsBody').html('<tr><td colspan="8" class="text-center">Loading items...</td></tr>');
                
                let url = '{{ route("store.bag-issuance.get-bag-request-details", ":id") }}';
                url = url.replace(':id', requestId);

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(response) {
                        $('#company_location_id').val(response.company_location_id).trigger('change.select2');
                        $('#arrival_location_id').val(response.arrival_location_id).trigger('change.select2');
                        $('#gala_id').val(response.gala_id).trigger('change.select2');
                        
                        // Handle multiple job orders
                        if (response.job_order_ids) {
                            $('#job_order_ids').val(response.job_order_ids).trigger('change.select2');
                        }

                        $('#itemsBody').empty();
                        if (response.items && response.items.length > 0) {
                            response.items.forEach(function(item, index) {
                                let newRow = `
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                            <input type="hidden" name="items[${index}][job_order_id]" value="${item.job_order_id}">
                                            <span class="form-control-static">${item.item ? item.item.name : 'N/A'}</span>
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[${index}][brand_id]" value="${item.brand_id}">
                                            <span class="form-control-static">${item.brand ? item.brand.name : 'N/A'}</span>
                                        </td>
                                        <td>
                                            <span class="form-control-static">${item.display_size || ''}</span>
                                        </td>
                                        <td>
                                            <span class="form-control-static">${item.quantity}</span>
                                        </td>
                                        <td>
                                            <span class="form-control-static">${item.balance_qty}</span>
                                        </td>
                                        <td>
                                            <input type="number" name="items[${index}][quantity]" class="form-control" step="0.01" value="${item.balance_qty}" max="${item.balance_qty}">
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[${index}][unit_id]" value="${item.unit_id}">
                                            <span class="form-control-static">${item.unit ? item.unit.name : 'N/A'}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-success issuance-btn">Issuance</button>
                                        </td>
                                    </tr>
                                `;
                                $('#itemsBody').append(newRow);
                            });
                        } else {
                            $('#itemsBody').html('<tr><td colspan="8" class="text-center">No items found for this request</td></tr>');
                        }
                    },
                    error: function() {
                        $('#itemsBody').html('<tr><td colspan="8" class="text-center">Error loading items</td></tr>');
                    }
                });
            } else {
                $('#issuance_number').val('');
                $('#itemsBody').html('<tr><td colspan="8" class="text-center">Select Bag Request to load items</td></tr>');
            }
        });
        // Use a namespace to avoid duplicate bindings
$(document).off('click.issuance').on('click.issuance', '.issuance-btn', function() {
    let btn = $(this);

    // Prevent multiple clicks if already processing
    if (btn.data('processing')) return;
    btn.data('processing', true);

    // Disable all issuance buttons
    $('.issuance-btn').prop('disabled', true);
    
    // Show spinner
    btn.html('<i class="ft-refresh-cw spinner"></i>');

    let row = btn.closest('tr');

    // Disable inputs from other rows
    $('#itemsBody tr').not(row).find('input').prop('disabled', true);

    // Submit the form
    $('#ajaxSubmit').submit();
});
    });
</script>
