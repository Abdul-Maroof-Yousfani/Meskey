<form action="{{ route('production-output-analysis.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-output-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Output Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Company Location:</label>
                        <select name="location_id" id="location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" @selected($preSelectedLocationId == $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Arrival Location:</label>
                        <select name="arrival_location_id" id="arrival_location_id" class="form-control select2" required>
                            <option value="">Select Arrival Location</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Product (Commodity):</label>
                        <select name="product_id" id="product_id" class="form-control select2" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Extra Fields for Output Analysis -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="d-block">Milling Degree:</label>
                        <div class="d-flex flex-column" style="gap: 5px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling1" name="milling_degree" value="silky polish" class="custom-control-input" checked>
                                <label class="custom-control-label" for="milling1">Silky Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling2" name="milling_degree" value="double polish" class="custom-control-input">
                                <label class="custom-control-label" for="milling2">Double Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling3" name="milling_degree" value="single mill polish" class="custom-control-input">
                                <label class="custom-control-label" for="milling3">Single Mill Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling4" name="milling_degree" value="reasonably well milled" class="custom-control-input">
                                <label class="custom-control-label" for="milling4">Reasonably Well Milled</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="d-block">Stitching:</label>
                        <div class="d-flex flex-column" style="gap: 5px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="stitch1" name="inner_stitching" value="double" class="custom-control-input" checked>
                                <label class="custom-control-label" for="stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="stitch2" name="inner_stitching" value="single" class="custom-control-input">
                                <label class="custom-control-label" for="stitch2">Single</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="d-block">Outer Stitching:</label>
                        <div class="d-flex flex-column" style="gap: 5px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="outer_stitch1" name="outer_stitching" value="double" class="custom-control-input" checked>
                                <label class="custom-control-label" for="outer_stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="outer_stitch2" name="outer_stitching" value="single" class="custom-control-input">
                                <label class="custom-control-label" for="outer_stitch2">Single</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Section -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Line Items
                <div>
                    <button type="button" class="btn btn-sm btn-success" id="addLineItem">Add Row</button>
                </div>
            </h6>
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered" id="lineItemsTable" style="min-width: 1200px;">
                    <thead>
                        <tr id="headerRow">
                            <th style="min-width: 150px;">Time</th>
                            <th style="min-width: 150px;">Unit</th>
                            @foreach($productSlabTypes as $productSlabType)
                                <th class="dynamic-col" data-slab-id="{{ $productSlabType->id }}" style="min-width: 150px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                            @endforeach
                            <th style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        <tr>
                            <td>
                                <input type="time" name="items[0][time]" class="form-control" value="{{ date('H:i') }}">
                            </td>
                            <td>
                                <select name="items[0][unit_id]" class="form-control select2">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td class="dynamic-col">
                                    <input type="text" name="items[0][params][{{ $productSlabType->id }}]" class="form-control">
                                </td>
                            @endforeach
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Analysis</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        function initSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({ width: '100%' });
            }
        }
        initSelect2('.select2');

        if ($('#location_id').val()) {
            $('#location_id').trigger('change');
        }

        $('#product_id').on('change', function() {
            let productId = $(this).val();
            if(!productId) return;

            $.ajax({
                url: "{{ route('production-output-analysis.get-slabs') }}",
                type: "GET",
                data: { product_id: productId },
                success: function(response) {
                    let headerRow = $('#headerRow');
                    headerRow.find('.dynamic-col').remove();
                    
                    let actionTh = headerRow.find('th:last-child');
                    response.forEach(slab => {
                        $(`<th class="dynamic-col" data-slab-id="${slab.id}" style="min-width: 150px;">${slab.name} ${slab.qc_symbol || ''}</th>`)
                            .insertBefore(actionTh);
                    });

                    $('#lineItemsBody').html('');
                    addRow(response);
                }
            });
        });

        function addRow(slabs = null) {
            let rowCount = $('#lineItemsBody tr').length;
            let currentTime = new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0');
            
            let newRow = `<tr>
                <td><input type="time" name="items[${rowCount}][time]" class="form-control" value="${currentTime}"></td>
                <td>
                    <select name="items[${rowCount}][unit_id]" class="form-control select2-row">
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </td>`;
            
            if (slabs) {
                slabs.forEach(slab => {
                    newRow += `<td class="dynamic-col"><input type="text" name="items[${rowCount}][params][${slab.id}]" class="form-control"></td>`;
                });
            } else {
                $('#headerRow th.dynamic-col').each(function() {
                    let slabId = $(this).data('slab-id');
                    newRow += `<td class="dynamic-col"><input type="text" name="items[${rowCount}][params][${slabId}]" class="form-control"></td>`;
                });
            }
            
            newRow += `<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button></td></tr>`;
            
            let $row = $(newRow);
            $('#lineItemsBody').append($row);
            initSelect2($row.find('.select2-row'));
        }

        $('#addLineItem').on('click', function() {
            addRow();
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#lineItemsBody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one row is required.');
            }
        });

        // Cascading Dropdowns
        $('#location_id').on('change', function() {
            let companyId = $(this).val();
            $('#arrival_location_id').html('<option value="">Select Arrival Location</option>').trigger('change');
            $('#plant_id').html('<option value="">Select Plant</option>').trigger('change');
            
            if (companyId) {
                let url = '{{ route("production-machine-analysis.get-arrival-locations", ":id") }}';
                url = url.replace(':id', companyId);
                $.get(url, function(data) {
                    $.each(data, function(i, item) {
                        $('#arrival_location_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $('#arrival_location_id').trigger('change');
                });
            }
        });

        $('#arrival_location_id').on('change', function() {
            let companyId = $('#location_id').val();
            let arrivalId = $(this).val();
            $('#plant_id').html('<option value="">Select Plant</option>').trigger('change');
            
            if (companyId && arrivalId) {
                let url = '{{ route("production-machine-analysis.get-plants", [":companyId", ":arrivalId"]) }}';
                url = url.replace(':companyId', companyId).replace(':arrivalId', arrivalId);
                $.get(url, function(data) {
                    $.each(data, function(i, item) {
                        $('#plant_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $('#plant_id').trigger('change');
                });
            }
        });
    });
</script>
