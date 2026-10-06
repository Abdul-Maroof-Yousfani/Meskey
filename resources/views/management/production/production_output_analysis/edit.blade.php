<form action="{{ route('production-output-analysis.update', $item->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-output-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Edit Output Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ \Carbon\Carbon::parse($item->analysis_date)->format('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Analysis Request: <span class="text-danger">*</span></label>
                        <select name="analysis_request_id" id="analysis_request_id" class="form-control select2" required>
                            <option value="">Select Analysis Request</option>
                            @foreach($analysisRequests as $req)
                                <option value="{{ $req->id }}"
                                    data-company-location-id="{{ $req->company_location_id }}"
                                    data-company-location-name="{{ $req->companyLocation?->name }}"
                                    data-arrival-location-id="{{ $req->arrival_location_id }}"
                                    data-arrival-location-name="{{ $req->arrivalLocation?->name }}"
                                    data-plant-id="{{ $req->plant_id }}"
                                    data-plant-name="{{ $req->plant?->name }}"
                                    data-job-order-id="{{ $req->job_order_id }}"
                                    data-job-order-no="{{ $req->jobOrder?->job_order_no }}"
                                    @selected($item->analysis_request_id == $req->id)>
                                    {{ $req->request_no }} - {{ $req->companyLocation?->name }} (Plant: {{ $req->plant?->name ?? 'N/A' }}) - {{ $req->request_date->format('d-m-Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Product (Commodity):</label>
                        <select name="product_id" id="product_id" class="form-control select2" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected($item->product_id == $product->id)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Selected Request Details Summary -->
                <div id="request_details_box" class="col-12 mb-2 {{ $item->analysisRequest ? '' : 'd-none' }}">
                    <div class="alert alert-light border py-2 px-3 mb-0 d-flex flex-wrap align-items-center justify-content-between" style="background: #f8fafc; border-radius: 6px;">
                        <div>
                            <i class="ft-map-pin text-primary mr-1"></i> Location: <strong id="req_disp_location">{{ $item->analysisRequest?->companyLocation?->name ?? $item->location?->name ?? '' }}</strong> &bull;
                            <i class="ft-navigation text-info mr-1 ml-2"></i> Arrival: <span id="req_disp_arrival">{{ $item->analysisRequest?->arrivalLocation?->name ?? $item->arrivalLocation?->name ?? '' }}</span> &bull;
                            <i class="ft-cpu text-success mr-1 ml-2"></i> Plant: <span id="req_disp_plant">{{ $item->analysisRequest?->plant?->name ?? $item->plant?->name ?? '' }}</span>
                            <span id="req_disp_jo_wrapper" class="ml-2 {{ $item->analysisRequest?->jobOrder ? '' : 'd-none' }}">
                                &bull; <i class="ft-file-text text-warning mr-1"></i> Job Order: <strong id="req_disp_jo">{{ $item->analysisRequest?->jobOrder?->job_order_no ?? '' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Location, Arrival Location, Plant dropdowns -->
                <div class="d-none">
                    <select name="location_id" id="location_id">
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option value="{{ $location->id }}" @selected($item->location_id == $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="arrival_location_id" id="arrival_location_id">
                        <option value="">Select Arrival Location</option>
                        @foreach($arrivalLocations as $loc)
                            <option value="{{ $loc->id }}" @selected($item->arrival_location_id == $loc->id)>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="plant_id" id="plant_id">
                        <option value="">Select Plant</option>
                        @foreach($plants as $pl)
                            <option value="{{ $pl->id }}" @selected($item->plant_id == $pl->id)>
                                {{ $pl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Extra Fields for Output Analysis -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="d-block">Milling Degree:</label>
                        <div class="d-flex flex-column" style="gap: 5px;">
                            @foreach(['silky polish', 'double polish', 'single mill polish', 'reasonably well milled'] as $degree)
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="milling_{{ str_replace(' ', '_', $degree) }}" name="milling_degree" value="{{ $degree }}" class="custom-control-input" @checked($item->milling_degree == $degree)>
                                    <label class="custom-control-label" for="milling_{{ str_replace(' ', '_', $degree) }}">{{ ucwords($degree) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="d-block">Stitching:</label>
                        <div class="d-flex flex-column" style="gap: 5px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="stitch1" name="inner_stitching" value="double" class="custom-control-input" @checked($item->inner_stitching == 'double')>
                                <label class="custom-control-label" for="stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="stitch2" name="inner_stitching" value="single" class="custom-control-input" @checked($item->inner_stitching == 'single')>
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
                                <input type="radio" id="outer_stitch1" name="outer_stitching" value="double" class="custom-control-input" @checked($item->outer_stitching == 'double')>
                                <label class="custom-control-label" for="outer_stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="outer_stitch2" name="outer_stitching" value="single" class="custom-control-input" @checked($item->outer_stitching == 'single')>
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
                        @foreach($item->items as $index => $analysisItem)
                            <tr>
                                <td>
                                    <input type="time" name="items[{{ $index }}][time]" class="form-control" value="{{ \Carbon\Carbon::parse($analysisItem->analysis_time)->format('H:i') }}">
                                </td>
                                <td>
                                    <select name="items[{{ $index }}][unit_id]" class="form-control select2">
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" @selected($analysisItem->unit_id == $unit->id)>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach($productSlabTypes as $productSlabType)
                                    @php
                                        $slabValue = $analysisItem->slabs->where('slab_type_id', $productSlabType->id)->first();
                                    @endphp
                                    <td class="dynamic-col">
                                        <input type="text" name="items[{{ $index }}][params][{{ $productSlabType->id }}]" 
                                               class="form-control" value="{{ $slabValue->production_analysis_value ?? '' }}">
                                    </td>
                                @endforeach
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks">{{ $item->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Analysis</button>
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

        // Handle Analysis Request Selection
        $('#analysis_request_id').on('change', function() {
            let $opt = $(this).find(':selected');
            let compLocId = $opt.data('company-location-id');
            let arrivalLocId = $opt.data('arrival-location-id');
            let plantId = $opt.data('plant-id');
            let jobOrderId = $opt.data('job-order-id');
            let compLocName = $opt.data('company-location-name');
            let arrivalLocName = $opt.data('arrival-location-name');
            let plantName = $opt.data('plant-name');
            let jobOrderNo = $opt.data('job-order-no');

            if (compLocId) {
                $('#location_id').val(compLocId);
                $('#arrival_location_id').val(arrivalLocId);
                $('#plant_id').val(plantId);

                $('#req_disp_location').text(compLocName || '');
                $('#req_disp_arrival').text(arrivalLocName || '');
                $('#req_disp_plant').text(plantName || '');
                if (jobOrderNo) {
                    $('#req_disp_jo').text(jobOrderNo);
                    $('#req_disp_jo_wrapper').removeClass('d-none');
                } else {
                    $('#req_disp_jo_wrapper').addClass('d-none');
                }
                $('#request_details_box').removeClass('d-none');
            } else {
                $('#location_id').val('');
                $('#arrival_location_id').val('');
                $('#plant_id').val('');
                $('#request_details_box').addClass('d-none');
            }
        });

        if ($('#analysis_request_id').val()) {
            $('#analysis_request_id').trigger('change');
        }
    });
</script>
