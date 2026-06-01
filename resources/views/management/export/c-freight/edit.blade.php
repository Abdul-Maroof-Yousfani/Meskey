<div class="row">
    <!-- Summary Section -->
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-header p-2">
                <h5 class="mb-0">Request Details</h5>
            </div>
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>EO No</label>
                            <input type="text" class="form-control" value="{{ $cFreight->exportOrder->voucher_no ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Port</label>
                            <input type="text" class="form-control" value="{{ $cFreight->exportOrder->portOfDischarge->name ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Req. Containers</label>
                            <input type="text" class="form-control" value="{{ $cFreight->requested_containers }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Status</label>
                            <input type="text" class="form-control" value="{{ $cFreight->status }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@php
    $hasApprovedRate = $cFreight->rates->where('is_approved', 1)->count() > 0;
@endphp

    <!-- Rates Section (Image 2) -->
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-header p-2">
                <h5 class="mb-0">1. Freight Rates</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table m-0" id="ratesTable">
                        <thead>
                            <tr>
                                <th>Third Party</th>
                                <th>Shipping Line</th>
                                <th>Container Size</th>
                                <th>Port</th>
                                <th>Price/Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cFreight->rates as $rate)
                                <tr>
                                    <td>{{ $rate->third_party }}</td>
                                    <td>{{ $rate->shipping_line }}</td>
                                    <td>{{ $rate->container_size }}</td>
                                    <td>{{ $rate->port }}</td>
                                    <td>{{ $rate->price }}</td>
                                    <td>
                                        @if($rate->is_approved)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$rate->is_approved && !$hasApprovedRate)
                                            <button type="button" class="btn btn-sm btn-success approve-rate-btn mb-1" data-id="{{ $rate->id }}" style="user-select: none;">Approve</button>
                                            <button type="button" class="btn btn-sm btn-danger delete-rate-btn mb-1" data-id="{{ $rate->id }}" style="user-select: none;">Remove</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-3">No rates added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!$hasApprovedRate)
                    <div class="border-top mt-3">
                        <div class="p-2 border-bottom">
                            <h6 class="font-weight-bold mb-0">Add New Rates</h6>
                        </div>
                        <form id="addRatesForm">
                            <div class="table-responsive">
                                <table class="table m-0" id="newRatesTable">
                                    <thead>
                                        <tr>
                                            <th>Third Party</th>
                                            <th>Shipping Line <span class="text-danger">*</span></th>
                                            <th>Container Size <span class="text-danger">*</span></th>
                                            <th>Port <span class="text-danger">*</span></th>
                                            <th>Price/Amount <span class="text-danger">*</span></th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" name="rates[0][third_party]" class="form-control" placeholder="e.g. Cordellia"></td>
                                            <td><input type="text" name="rates[0][shipping_line]" class="form-control" required placeholder="e.g. NVOCC"></td>
                                            <td><input type="text" name="rates[0][container_size]" class="form-control" required placeholder="e.g. 20FT"></td>
                                            <td><input type="text" name="rates[0][port]" class="form-control" required placeholder="e.g. Ningbo"></td>
                                            <td><input type="text" name="rates[0][price]" class="form-control" required placeholder="e.g. $25 + locals"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger remove-new-rate-row" style="user-select: none;" disabled><i class="ft-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="border-top-0 pt-2 pb-2 px-3">
                                                <button type="button" class="btn btn-primary" id="addMoreRateRow"><i class="ft-plus"></i> Add More</button>
                                                <button type="submit" class="btn btn-success float-right">Save New Rates</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Booking Details Section (Image 3) -->
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-header p-2">
                <h5 class="mb-0">2. Confirm Booking Details</h5>
            </div>
            <div class="card-body p-3">
                @if(!$hasApprovedRate)
                    <div class="alert alert-warning mb-0 p-2">
                        <strong>Notice:</strong> Please approve a Freight Rate above to unlock Booking Confirmation.
                    </div>
                @else
                    <form action="{{ route('c-freight.update', $cFreight->id) }}" method="POST" id="cFreightForm" class="form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <!-- Row 1 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Booking No <span class="text-danger">*</span></label>
                                    <input type="text" name="booking_no" class="form-control" value="{{ $cFreight->booking_no }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bill of Lading Number</label>
                                    <input type="text" name="bl_number" class="form-control" value="{{ $cFreight->bl_number }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Quantity <span class="text-danger">*</span></label>
                                    <input type="text" name="quantity" class="form-control" value="{{ $cFreight->quantity }}" required>
                                </div>
                            </div>
                            
                            <!-- Row 2 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Shipping Line <span class="text-danger">*</span></label>
                                    <input type="text" name="shipping_line" class="form-control" value="{{ $cFreight->shipping_line }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Through Logistic <span class="text-danger">*</span></label>
                                    <select name="through_logistic" class="form-control select2" required>
                                        <option value="">Select Company</option>
                                        @foreach($shipmentCompanies as $company)
                                            <option value="{{ $company->name }}" {{ $cFreight->through_logistic == $company->name ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>T/S <span class="text-danger">*</span></label>
                                    <input type="text" name="t_s" class="form-control" value="{{ $cFreight->t_s }}" required>
                                </div>
                            </div>

                            <!-- Row 3 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>POD (Port of Discharge)</label>
                                    <input type="text" class="form-control" value="{{ $cFreight->exportOrder->portOfDischarge->name ?? '' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Return Port <span class="text-danger">*</span></label>
                                    <input type="text" name="return_port" class="form-control" value="{{ $cFreight->return_port }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vessel Name <span class="text-danger">*</span></label>
                                    <input type="text" name="vessel_name" class="form-control" value="{{ $cFreight->vessel_name }}" required>
                                </div>
                            </div>
                            
                            <!-- Row 4 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cutoff SI <span class="text-danger">*</span></label>
                                    <input type="date" name="cutoff_si" class="form-control" value="{{ $cFreight->cutoff_si }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cutoff CY <span class="text-danger">*</span></label>
                                    <input type="date" name="cutoff_cy" class="form-control" value="{{ $cFreight->cutoff_cy }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ETD <span class="text-danger">*</span></label>
                                    <input type="date" name="etd" class="form-control" value="{{ $cFreight->etd }}" required>
                                </div>
                            </div>

                            <!-- Row 5 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ETA <span class="text-danger">*</span></label>
                                    <input type="date" name="eta" class="form-control" value="{{ $cFreight->eta }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 text-right">
                                <button type="button" class="btn btn-secondary modal-sidebar-close">Close</button>
                                <button type="submit" class="btn btn-success">Save Booking / Confirm</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let rateRowIndex = 1;

        // Add More Rate Row
        $('#addMoreRateRow').click(function() {
            var newRow = `
                <tr>
                    <td><input type="text" name="rates[${rateRowIndex}][third_party]" class="form-control" placeholder="e.g. Cordellia"></td>
                    <td><input type="text" name="rates[${rateRowIndex}][shipping_line]" class="form-control" required placeholder="e.g. NVOCC"></td>
                    <td><input type="text" name="rates[${rateRowIndex}][container_size]" class="form-control" required placeholder="e.g. 20FT"></td>
                    <td><input type="text" name="rates[${rateRowIndex}][port]" class="form-control" required placeholder="e.g. Ningbo"></td>
                    <td><input type="text" name="rates[${rateRowIndex}][price]" class="form-control" required placeholder="e.g. $25 + locals"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger remove-new-rate-row" style="user-select: none;"><i class="ft-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#newRatesTable tbody').append(newRow);
            rateRowIndex++;
            updateRemoveButtons();
        });

        // Remove New Rate Row
        $(document).on('click', '.remove-new-rate-row', function() {
            $(this).closest('tr').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            let rows = $('#newRatesTable tbody tr').length;
            if (rows > 1) {
                $('.remove-new-rate-row').prop('disabled', false);
            } else {
                $('.remove-new-rate-row').prop('disabled', true);
            }
        }

        // Save Multiple Rates via AJAX
        $('#addRatesForm').submit(function(e) {
            e.preventDefault();
            
            let $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: "{{ route('c-freight.add-rate', $cFreight->id) }}",
                type: 'POST',
                data: $(this).serialize() + "&_token={{ csrf_token() }}",
                success: function(res) {
                    if(res.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Success",
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        openModal($('<button></button>'), "{{ route('c-freight.edit', $cFreight->id) }}", 'Add Rates & Booking', false, '90%');
                    } else {
                        Swal.fire("Error", res.message, "error");
                        $btn.prop('disabled', false).text('Save New Rates');
                    }
                },
                error: function(err) {
                    Swal.fire("Error", "Error saving rates. Check required fields.", "error");
                    $btn.prop('disabled', false).text('Save New Rates');
                }
            });
        });

        // Approve Existing Rate
        $('.approve-rate-btn').click(function() {
            var rate_id = $(this).data('id');
            var $btn = $(this);
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This rate will become the final Freight Amount.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).text('...');
                    $.ajax({
                        url: "{{ route('c-freight.approve-rate', $cFreight->id) }}",
                        type: 'POST',
                        data: {
                            rate_id: rate_id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if(res.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Approved!",
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                openModal($('<button></button>'), "{{ route('c-freight.edit', $cFreight->id) }}", 'Add Rates & Booking', false, '90%');
                            } else {
                                Swal.fire("Error", res.message, "error");
                                $btn.prop('disabled', false).text('Approve');
                            }
                        },
                        error: function(err) {
                            Swal.fire("Error", "Failed to approve rate. Please try again.", "error");
                            $btn.prop('disabled', false).text('Approve');
                        }
                    });
                }
            });
        });

        // Delete Existing Rate
        $('.delete-rate-btn').click(function() {
            var rate_id = $(this).data('id');
            var $btn = $(this);
            
            Swal.fire({
                title: 'Remove Rate?',
                text: "Are you sure you want to remove this rate?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).text('...');
                    
                    var deleteUrl = "{{ route('c-freight.delete-rate', ':id') }}";
                    deleteUrl = deleteUrl.replace(':id', rate_id);
                    
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if(res.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Removed!",
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                openModal($('<button></button>'), "{{ route('c-freight.edit', $cFreight->id) }}", 'Add Rates & Booking', false, '90%');
                            } else {
                                Swal.fire("Error", res.message, "error");
                                $btn.prop('disabled', false).text('Remove');
                            }
                        },
                        error: function(err) {
                            Swal.fire("Error", "Failed to remove rate.", "error");
                            $btn.prop('disabled', false).text('Remove');
                        }
                    });
                }
            });
        });
    });
</script>
