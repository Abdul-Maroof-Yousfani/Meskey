<form action="{{ route('production-voucher.update', $productionVoucher->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Voucher</h6>
            <div class="row">
                <div class="col-md-3">
                    <fieldset>
                        <label>Prod. No:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Prod. No</button>
                            </div>
                            <input type="text" readonly name="prod_no" class="form-control" value="{{ $productionVoucher->prod_no }}">
                        </div>
                    </fieldset>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Prod. Date:</label>
                        <input type="date" name="prod_date" class="form-control" value="{{ $productionVoucher->prod_date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Ord. No:</label>
                        <select name="job_order_id" id="job_order_id" class="form-control select2" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}" {{ $productionVoucher->job_order_id == $jobOrder->id ? 'selected' : '' }}>
                                    {{ $jobOrder->job_order_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" id="location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" {{ $productionVoucher->location_id == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Produced QTY (kg):</label>
                        <input type="number" name="produced_qty_kg" class="form-control" step="0.01" min="0.01" value="{{ $productionVoucher->produced_qty_kg }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supervisor:</label>
                        <select name="supervisor_id" id="supervisor_id" class="form-control select2">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ $productionVoucher->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Labor (per kg):</label>
                        <input type="number" name="labor_cost_per_kg" class="form-control" step="0.0001" min="0" value="{{ $productionVoucher->labor_cost_per_kg }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Overhead (per kg):</label>
                        <input type="number" name="overhead_cost_per_kg" class="form-control" step="0.0001" min="0" value="{{ $productionVoucher->overhead_cost_per_kg }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" id="status" class="form-control select2" required>
                            <option value="draft" {{ $productionVoucher->status == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="completed" {{ $productionVoucher->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="approved" {{ $productionVoucher->status == 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ $productionVoucher->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Input and Output Buttons -->
        <div class="col-md-12 mt-3">
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-success btn-block" onclick="openProductionInputDrawer({{ $productionVoucher->id }})">
                        <i class="ft-plus"></i> Create Production Input
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-info btn-block" onclick="openProductionOutputDrawer({{ $productionVoucher->id }})">
                        <i class="ft-plus"></i> Create Production Output
                    </button>
                </div>
            </div>
        </div>

        <!-- Production Inputs List -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Production Inputs</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Commodity</th>
                            <th>Location</th>
                            <th>Qty (kg)</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productionInputsTable">
                        @foreach($productionVoucher->inputs as $input)
                            <tr data-input-id="{{ $input->id }}">
                                <td>{{ $input->product->name ?? 'N/A' }}</td>
                                <td>{{ $input->location->name ?? 'N/A' }}</td>
                                <td>{{ number_format($input->qty, 2) }}</td>
                                <td>{{ $input->remarks ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="editProductionInput({{ $productionVoucher->id }}, {{ $input->id }})">
                                        <i class="ft-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductionInput({{ $productionVoucher->id }}, {{ $input->id }})">
                                        <i class="ft-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Production Outputs List -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Production Outputs</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Commodity</th>
                            <th>Qty (kg)</th>
                            <th>Storage Location</th>
                            <th>Brand</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productionOutputsTable">
                        @foreach($productionVoucher->outputs as $output)
                            <tr data-output-id="{{ $output->id }}">
                                <td>{{ $output->product->name ?? 'N/A' }}</td>
                                <td>{{ number_format($output->qty, 2) }}</td>
                                <td>{{ $output->storageLocation->name ?? 'N/A' }}</td>
                                <td>{{ $output->brand->name ?? '-' }}</td>
                                <td>{{ $output->remarks ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="editProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                        <i class="ft-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                        <i class="ft-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Production Voucher</button>
        </div>
    </div>
</form>


<script>
    $(document).ready(function () {
        $('.select2').select2();
    });

    function openProductionInputDrawer(voucherId) {
        const url = '{{ route("production-voucher.input.form", ":id") }}'.replace(':id', voucherId);
        openModal(null, url, 'Create Production Input', false, '50%');
    }

    function openProductionOutputDrawer(voucherId) {
        const url = '{{ route("production-voucher.output.form", ":id") }}'.replace(':id', voucherId);
        openModal(null, url, 'Create Production Output', false, '50%');
    }

    function editProductionInput(voucherId, inputId) {
        // Load edit form
        $.get('{{ route("production-voucher.edit", ":id") }}'.replace(':id', voucherId), function(data) {
            // Implementation for edit
        });
    }

    function deleteProductionInput(voucherId, inputId) {
        if (confirm('Are you sure you want to delete this production input?')) {
            $.ajax({
                url: '{{ route("production-voucher.input.destroy", [":id", ":inputId"]) }}'
                    .replace(':id', voucherId)
                    .replace(':inputId', inputId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('tr[data-input-id="' + inputId + '"]').remove();
                    showNotification('success', response.success);
                }
            });
        }
    }

    function editProductionOutput(voucherId, outputId) {
        // Similar to editProductionInput
    }

    function deleteProductionOutput(voucherId, outputId) {
        if (confirm('Are you sure you want to delete this production output?')) {
            $.ajax({
                url: '{{ route("production-voucher.output.destroy", [":id", ":outputId"]) }}'
                    .replace(':id', voucherId)
                    .replace(':outputId', outputId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('tr[data-output-id="' + outputId + '"]').remove();
                    showNotification('success', response.success);
                }
            });
        }
    }
</script>
