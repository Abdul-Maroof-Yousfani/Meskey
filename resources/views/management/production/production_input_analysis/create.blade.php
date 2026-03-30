<form action="{{ route('production-input-analysis.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-input-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Input Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_ids[]" class="form-control select2" multiple required>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}">{{ $jobOrder->job_order_no }}</option>
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
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
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
                                <option value="{{ $packing->id }}">{{ $packing->name }}</option>
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
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Variety:</label>
                        <input type="text" name="variety" class="form-control" placeholder="Enter Variety">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Crop Year:</label>
                        <select name="crop_year_id" class="form-control select2" required>
                            <option value="">Select Crop Year</option>
                            @foreach($cropYears as $cropYear)
                                <option value="{{ $cropYear->id }}">{{ $cropYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Section -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Line Items
                <div>
                    <!-- <button type="button" class="btn btn-sm btn-info" id="addColumn">Add Column</button> -->
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
                        <tr>
                            <td>
                                <input type="time" name="items[0][time]" class="form-control" value="{{ date('H:i') }}">
                            </td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td class="dynamic-col">
                                    <input type="text" name="items[0][params][]" class="form-control">
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
        // Initialize Select2
        if ($.fn.select2) {
            $('.select2').select2({
                width: '100%'
            });
        }

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

        // Add Column
        $('#addColumn').on('click', function() {
            let colName = prompt("Enter Column Name:", "New Parameter");
            if (colName) {
                // Add header before the last 'Action' column
                $(`<th class="dynamic-col" style="min-width: 250px;">${colName}</th>`).insertBefore('#headerRow th:last-child');
                
                // Add cell to each row before the last 'Action' column
                $('#lineItemsBody tr').each(function() {
                    $(`<td class="dynamic-col"><input type="text" name="items[${$(this).index()}][params][]" class="form-control"></td>`).insertBefore($(this).find('td:last-child'));
                });
                
                // Adjust table min-width to maintain broad fields
                let currentWidth = parseInt($('#lineItemsTable').css('min-width'));
                $('#lineItemsTable').css('min-width', (currentWidth + 250) + 'px');
            }
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
