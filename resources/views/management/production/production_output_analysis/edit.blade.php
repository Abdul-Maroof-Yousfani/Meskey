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
                        <input type="date" name="date" class="form-control" value="{{ $item->analysis_date->format('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_ids[]" class="form-control select2" multiple required>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}" {{ $item->jobOrders->contains($jobOrder->id) ? 'selected' : '' }}>
                                    {{ $jobOrder->job_order_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Brand:</label>
                        <select name="brand_id" class="form-control select2" required>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $item->brand_id == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Packing:</label>
                        <select name="packing_id" class="form-control select2" required>
                            <option value="">Select Packing</option>
                            @foreach($packings as $packing)
                                <option value="{{ $packing->id }}" {{ $item->bag_packing_id == $packing->id ? 'selected' : '' }}>
                                    {{ $packing->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" {{ $item->location_id == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Variety:</label>
                        <input type="text" name="variety" class="form-control" placeholder="Enter Variety" value="{{ $item->variety }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Crop Year:</label>
                        <select name="crop_year_id" class="form-control select2" required>
                            <option value="">Select Crop Year</option>
                            @foreach($cropYears as $cropYear)
                                <option value="{{ $cropYear->id }}" {{ $item->crop_year_id == $cropYear->id ? 'selected' : '' }}>
                                    {{ $cropYear->name }}
                                </option>
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
                                <input type="radio" id="milling1" name="milling_degree" value="silky polish" class="custom-control-input" {{ $item->milling_degree == 'silky polish' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="milling1">Silky Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling2" name="milling_degree" value="double polish" class="custom-control-input" {{ $item->milling_degree == 'double polish' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="milling2">Double Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling3" name="milling_degree" value="single mill polish" class="custom-control-input" {{ $item->milling_degree == 'single mill polish' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="milling3">Single Mill Polish</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="milling4" name="milling_degree" value="reasonably well milled" class="custom-control-input" {{ $item->milling_degree == 'reasonably well milled' ? 'checked' : '' }}>
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
                                <input type="radio" id="stitch1" name="inner_stitching" value="double" class="custom-control-input" {{ $item->inner_stitching == 'double' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="stitch2" name="inner_stitching" value="single" class="custom-control-input" {{ $item->inner_stitching == 'single' ? 'checked' : '' }}>
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
                                <input type="radio" id="outer_stitch1" name="outer_stitching" value="double" class="custom-control-input" {{ $item->outer_stitching == 'double' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="outer_stitch1">Double</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="outer_stitch2" name="outer_stitching" value="single" class="custom-control-input" {{ $item->outer_stitching == 'single' ? 'checked' : '' }}>
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
                            @foreach($productSlabTypes as $productSlabType)
                                <th class="dynamic-col" style="min-width: 250px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                            @endforeach
                            <th style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        @php $rowCount = 0; @endphp
                        @forelse($groupedData as $time => $dataRows)
                            <tr>
                                <td>
                                    <input type="time" name="items[{{ $rowCount }}][time]" class="form-control" value="{{ \Carbon\Carbon::parse($time)->format('H:i') }}">
                                </td>
                                @foreach($productSlabTypes as $productSlabType)
                                    <td class="dynamic-col">
                                        @php
                                            $val = $dataRows->where('slab_type_id', $productSlabType->id)->first()->production_analysis_value ?? '';
                                        @endphp
                                        <input type="text" name="items[{{ $rowCount }}][params][]" class="form-control" value="{{ $val }}">
                                    </td>
                                @endforeach
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button>
                                </td>
                            </tr>
                            @php $rowCount++; @endphp
                        @empty
                            <tr>
                                <td><input type="time" name="items[0][time]" class="form-control" value="{{ date('H:i') }}"></td>
                                @foreach($productSlabTypes as $productSlabType)
                                    <td class="dynamic-col"><input type="text" name="items[0][params][]" class="form-control"></td>
                                @endforeach
                                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button></td>
                            </tr>
                        @endforelse
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
            @if(!$viewonly)
                <button type="submit" class="btn btn-primary submitbutton">Update Analysis</button>
            @endif
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        // Initialize Select2
        if ($.fn.select2) {
            $('.select2').select2({
                width: '100%'
            });
        }

        @if($viewonly)
            $('#ajaxSubmit :input').prop('disabled', true);
            $('.closebutton').prop('disabled', false); // Ensure close button is still clickable
        @endif

        // Add Row
        $('#addLineItem').on('click', function() {
            let rowCount = $('#lineItemsBody tr').length;
            let colCount = $('#headerRow th.dynamic-col').length;
            let currentTime = new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0');
            
            let newRow = `<tr>
                <td><input type="time" name="items[${rowCount}][time]" class="form-control" value="${currentTime}"></td>`;
            
            for(let i=0; i<colCount; i++) {
                newRow += `<td class="dynamic-col"><input type="text" name="items[${rowCount}][params][]" class="form-control"></td>`;
            }
            
            newRow += `<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button></td></tr>`;
            $('#lineItemsBody').append(newRow);
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#lineItemsBody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one row is required.');
            }
        });
    });
</script>

<style>
    .custom-control-label::before, 
    .custom-control-label::after {
        top: 0.25rem !important;
    }
    .custom-radio .custom-control-label::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    }
</style>
