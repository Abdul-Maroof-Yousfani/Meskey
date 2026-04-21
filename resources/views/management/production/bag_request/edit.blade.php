<form action="{{ route('bag-requests.update', $bagRequest->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.bag-requests') }}" />

    <div class="row">
        <div class="col-md-12">
        <h6 class="header-heading-sepration">Edit Request Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Request Date:</label>
                    <input type="date" name="request_date" id="request_date" class="form-control" value="{{ $bagRequest->request_date->format('Y-m-d') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" id="request_number_container">
                    <label>Request Number:</label>
                    <input type="text" name="request_number" id="request_number" class="form-control" readonly value="{{ $bagRequest->request_number }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Company Location:</label>
                    <select name="company_location_id" id="company_location_id" class="form-control select2">
                        <option value="">Select Location</option>
                        @foreach($company_locations as $location)
                            <option value="{{ $location->id }}" {{ $bagRequest->company_location_id == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Location:</label>
                    <select name="arrival_location_id" id="arrival_location_id" class="form-control select2">
                        <option value="">Select Arrival Location</option>
                        @foreach($arrival_locations as $al)
                            <option value="{{ $al->id }}" {{ $bagRequest->arrival_location_id == $al->id ? 'selected' : '' }}>{{ $al->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Gala:</label>
                    <select name="gala_id" id="gala_id" class="form-control select2">
                        <option value="">Select Gala</option>
                        @foreach($galas as $gala)
                            <option value="{{ $gala->id }}" {{ $bagRequest->gala_id == $gala->id ? 'selected' : '' }}>{{ $gala->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Job Order:</label>
                    <select name="job_order_ids[]" id="job_order_ids" class="form-control select2" multiple data-placeholder="Select Job Orders">
                        @php $selectedJO = $bagRequest->job_order_ids ?? []; @endphp
                        @foreach($jobOrders as $jo)
                            <option value="{{ $jo->id }}" {{ in_array($jo->id, $selectedJO) ? 'selected' : '' }}>{{ $jo->job_order_no }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Enter Remarks">{{ $bagRequest->remarks }}</textarea>
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
                        <th style="width: 15%">Packing Size (Kg)</th>
                        <th style="width: 15%">Requested Quantity</th>
                        <th style="width: 15%">Unit</th>
                        <th style="width: 15%">Remarks</th>
                        <th style="width: 5%">Action</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    @foreach($bagRequest->items as $index => $item)
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->item_id }}">
                                <select class="form-control select2" disabled>
                                    <option value="">Select Item</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ $item->item_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][brand_id]" value="{{ $item->brand_id }}">
                                <select class="form-control select2" disabled>
                                     <option value="">Select Brand</option>
                                     @foreach($brands as $brand)
                                         <option value="{{ $brand->id }}" {{ $item->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                     @endforeach
                                 </select>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][bag_size]" class="form-control" value="{{ $item->display_size }}" readonly>
                            </td>
                             <td>
                                <input type="number" name="items[{{ $index }}][quantity]" class="form-control" step="0.01" value="{{ $item->quantity }}" {{ $item->issued_qty > 0 ? 'readonly' : '' }}>
                                @if($item->issued_qty > 0)
                                    <small class="text-info">Issued: {{ number_format($item->issued_qty, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][unit_id]" value="{{ $item->unit_id }}">
                                <select class="form-control select2" disabled>
                                     <option value="">Select Unit</option>
                                     @foreach($units as $unit)
                                         <option value="{{ $unit->id }}" {{ $item->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                     @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][remarks]" class="form-control" value="{{ $item->remarks }}">
                            </td>
                            <td class="text-center">
                                @if($item->issued_qty == 0)
                                    <button type="button" class="btn btn-sm btn-danger removeRow"><i class="ft-trash"></i></button>
                                @else
                                    <i class="ft-lock text-info" title="Locked: Partial/Full issuance exists"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-danger modal-sidebar-close closebutton" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary submitbutton">Update Request</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#ajaxSubmit')
        });

        $('#request_date').on('change', function() {
            let date = $(this).val().replace(/-/g, '');
            $('#request_number').val('BR-' + date + '-001');
        });

        // 1. Company Location -> Arrival Location
        $('#company_location_id').on('change', function() {
            let companyLocationId = $(this).val();
            $('#arrival_location_id').empty().append('<option value="">Select Arrival Location</option>');
            $('#gala_id').empty().append('<option value="">Select Gala</option>');
            $('#job_order_ids').empty();
            $('#itemsBody').empty();
            addDefaultRow();

            if (companyLocationId) {
                $.ajax({
                    url: '{{ route("bag-requests.get-arrival-locations") }}',
                    method: 'GET',
                    data: { company_location_id: companyLocationId },
                    success: function(response) {
                        response.forEach(function(loc) {
                            $('#arrival_location_id').append(`<option value="${loc.id}">${loc.name}</option>`);
                        });
                    }
                });
            }
        });

        // 2. Arrival Location -> Gala
        $('#arrival_location_id').on('change', function() {
            let arrivalLocationId = $(this).val();
            $('#gala_id').empty().append('<option value="">Select Gala</option>');
            $('#job_order_ids').empty();
            $('#itemsBody').empty();
            addDefaultRow();

            if (arrivalLocationId) {
                $.ajax({
                    url: '{{ route("bag-requests.get-galas") }}',
                    method: 'GET',
                    data: { arrival_location_id: arrivalLocationId },
                    success: function(response) {
                        response.forEach(function(gala) {
                            $('#gala_id').append(`<option value="${gala.id}">${gala.name}</option>`);
                        });
                    }
                });
            }
        });

        // 3. Gala -> Job Orders
        $('#gala_id').on('change', function() {
            let galaId = $(this).val();
            let companyLocationId = $('#company_location_id').val();
            $('#job_order_ids').empty();
            $('#itemsBody').empty();
            addDefaultRow();

            if (galaId && companyLocationId) {
                $.ajax({
                    url: '{{ route("bag-requests.get-job-orders") }}',
                    method: 'GET',
                    data: { 
                        gala_id: galaId, 
                        company_location_id: companyLocationId,
                        bag_request_id: '{{ $bagRequest->id }}'
                    },
                    success: function(response) {
                        response.forEach(function(jo) {
                            $('#job_order_ids').append(`<option value="${jo.id}">${jo.job_order_no}</option>`);
                        });
                    }
                });
            }
        });

        let rowIndex = {{ count($bagRequest->items) }};

        // 4. Job Orders -> Items
        $('#job_order_ids').on('change', function() {
            let jobOrderIds = $(this).val();
            let companyLocationId = $('#company_location_id').val();
            
            if (jobOrderIds && jobOrderIds.length > 0 && companyLocationId) {
                $.ajax({
                    url: '{{ route("get.job-order-items") }}',
                    method: 'GET',
                    data: { 
                        job_order_ids: jobOrderIds,
                        company_location_id: companyLocationId,
                        bag_request_id: '{{ $bagRequest->id }}'
                    },
                    success: function(response) {
                        $('#itemsBody').empty();
                        rowIndex = 0;
                        if (response.length > 0) {
                            response.forEach(function(item) {
                                let newRow = `
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[${rowIndex}][item_id]" value="${item.item_id}">
                                            <select class="form-control select2" disabled>
                                                <option value="${item.item_id}" selected>${item.item_name}</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[${rowIndex}][brand_id]" value="${item.brand_id}">
                                            <select class="form-control select2" disabled>
                                                <option value="${item.brand_id}" selected>${item.brand_name}</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[${rowIndex}][bag_size]" class="form-control" value="${item.packing_size_kg}" readonly>
                                        </td>
                                        <td>
                                            <input type="number" name="items[${rowIndex}][quantity]" class="form-control" step="0.01" value="${item.quantity}">
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[${rowIndex}][unit_id]" value="${item.unit_id}">
                                            <select class="form-control select2" disabled>
                                                <option value="${item.unit_id}" selected>${item.unit_name}</option>
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[${rowIndex}][remarks]" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger removeRow"><i class="ft-trash"></i></button>
                                        </td>
                                    </tr>
                                `;
                                $('#itemsBody').append(newRow);
                                $('#itemsBody tr:last .select2').select2({
                                    dropdownParent: $('#ajaxSubmit')
                                });
                                rowIndex++;
                            });
                        } else {
                            addDefaultRow();
                        }
                    }
                });
            }
        });

        function addDefaultRow() {
            let newRow = `
                <tr>
                    <td>
                        <input type="hidden" name="items[0][item_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Item</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="items[0][brand_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[0][bag_size]" class="form-control" readonly>
                    </td>
                    <td>
                        <input type="number" name="items[0][quantity]" class="form-control" step="0.01">
                    </td>
                    <td>
                        <input type="hidden" name="items[0][unit_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[0][remarks]" class="form-control">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger removeRow"><i class="ft-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#itemsBody').append(newRow);
            $('#itemsBody tr:last .select2').select2({
                dropdownParent: $('#ajaxSubmit')
            });
            rowIndex = 1;
        }

        $('#addItemRow').on('click', function() {
            let newRow = `
                <tr>
                    <td>
                        <input type="hidden" name="items[${rowIndex}][item_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Item</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="items[${rowIndex}][brand_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][bag_size]" class="form-control" readonly>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][quantity]" class="form-control" step="0.01">
                    </td>
                    <td>
                        <input type="hidden" name="items[${rowIndex}][unit_id]">
                        <select class="form-control select2" disabled>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][remarks]" class="form-control">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger removeRow"><i class="ft-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#itemsBody').append(newRow);
            $('#itemsBody tr:last .select2').select2({
                dropdownParent: $('#ajaxSubmit')
            });
            rowIndex++;
        });

        $(document).on('click', '.removeRow', function() {
            if ($('#itemsBody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one item is required.');
            }
        });
    });
</script>
