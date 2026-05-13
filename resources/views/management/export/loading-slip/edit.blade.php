<form action="{{ route('export-loading-slip.update', $loadingSlip->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-slip') }}" />
    <style>
        .select2-container, .select2-container--default, .select2-selection--single { width: 100% !important; }
        .header-heading-sepration { border-bottom: 2px solid #ebeef3; padding-bottom: 5px; margin-bottom: 10px; }
    </style>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <input type="text" class="form-control" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? '' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? '' }}" readonly>
                <input type="hidden" name="loading_program_item_id" value="{{ $loadingSlip->loading_program_item_id }}">
            </div>
        </div>
    </div>

    <div id="ticketDataContainer" class="w-100">
        @if(count($Orders) > 0)
            <ul class="nav nav-tabs nav-justified" id="orderTabs" role="tablist">
                @foreach($Orders as $index => $order)
                    <li class="nav-item">
                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="order-tab-{{ $index }}" data-toggle="tab" href="#order-content-{{ $index }}" role="tab" aria-controls="order-content-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                            {{ $order['type'] }}: {{ $order['number'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            @php
                $allGalaNames = collect($Orders)->flatMap(fn($o) => $o['gala_names'] ?? [])->filter()->unique()->values();
                $factoryNames = collect($Orders)->flatMap(fn($o) => $o['factory_names'] ?? [])->filter()->unique()->implode(', ');
                $selectedGalaNames = $allGalaNames
                    ->filter(fn($name) => in_array($name, $selectedGalas) || str_contains((string) $loadingSlip->gala, (string) $name))
                    ->values();
            @endphp
            <div class="tab-content pt-1" id="orderTabsContent">
                @foreach($Orders as $index => $order)
                    <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}" role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Customer:</label><input type="text" value="{{ $order['customer'] }}" class="form-control" readonly /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Commodity:</label><input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>EO Qty (MT):</label><input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly step="0.01" /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>DO Qty (MT):</label><input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly step="0.01" /></div></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-12">
                                <div class="form-group">
                                    <label>Factory:</label>
                                    <input type="text" value="{{ implode(', ', $order['factory_names']) ?: 'N/A' }}" class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row pt-2">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Bag Size:</label>
                        <input type="number" name="bag_size_display" value="{{ $loadingSlip->bag_size }}" class="form-control" readonly step="0.01" />
                        <input type="hidden" name="bag_size" value="{{ $loadingSlip->bag_size }}" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>No. of Bags: <span class="text-danger">*</span></label>
                        <input type="number" name="no_of_bags" id="no_of_bags" value="{{ $loadingSlip->no_of_bags }}" class="form-control" min="1" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Empty Bags:</label>
                        <input type="text" name="empty_bags" id="empty_bags" value="{{ $loadingSlip->empty_bags }}" class="form-control" placeholder="Enter Empty Bags">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Gala: <span class="text-danger">*</span></label>
                        <select class="form-control select2-common w-100" name="gala[]" multiple required style="width: 100% !important;">
                            @foreach($allGalaNames as $name)
                                <option value="{{ $name }}" @selected($selectedGalaNames->contains($name))>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Kilogram:</label>
                        <input type="number" name="kilogram" id="kilogram" value="{{ $loadingSlip->kilogram ?? '' }}" class="form-control" readonly step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Metric Tons:</label>
                        <input type="number" id="metric_tons_display" value="{{ number_format(($loadingSlip->kilogram ?? 0) / 1000, 2, '.', '') }}" class="form-control" readonly step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Seal No: <span class="text-danger">*</span></label>
                        <input type="text" name="seal_no" id="seal_no" value="{{ $loadingSlip->seal_no }}" class="form-control" placeholder="Enter Seal No" required />
                    </div>
                </div>
            <input type="hidden" name="customer" value="{{ $loadingSlip->customer }}" />
            <input type="hidden" name="commodity" value="{{ $loadingSlip->commodity }}" />
            <input type="hidden" name="so_qty" value="{{ $loadingSlip->so_qty }}" />
            <input type="hidden" name="do_qty" value="{{ $loadingSlip->do_qty }}" />
            <input type="hidden" name="factory" value="{{ $loadingSlip->factory }}" />
            <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}" />
        @else
            <div class="col-12 text-center">No order data found.</div>
        @endif
    </div>

    <div class="row pt-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="header-heading-sepration mb-0" style="flex-grow: 1;">Stack</h6>
                <button type="button" class="btn btn-sm btn-success" id="add_stack_row"><i class="ft-plus"></i> Add More</button>
            </div>
            <table class="table table-bordered table-striped" id="stacks_table">
                <thead>
                    <tr>
                        <th>Bag Type <span class="text-danger">*</span></th>
                        <th>Packing Size (KG) <span class="text-danger">*</span></th>
                        <th>Input Size (KG)<span class="text-danger">*</span></th>
                        <th width="50px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $displayStacks = $loadingSlip->stacks;
                    @endphp
                    @foreach ($displayStacks as $idx => $stack)
                        @php
                            $currentBagType = is_object($stack) ? $stack->bag_type : ($stack['bag_type'] ?? '');
                            $currentPackingSize = is_object($stack) ? $stack->packing_size : ($stack['packing_size'] ?? '');
                            $currentInputSize = is_object($stack) ? $stack->input_size : ($stack['input_size'] ?? '');
                        @endphp
                        <tr>
                            <td>
                                <select name="stacks[{{ $idx }}][bag_type]" class="form-control select2-dynamic" required style="width: 100%;">
                                    <option value="">Select Bag Type</option>
                                    @foreach($bagTypes as $type)
                                        <option value="{{ $type }}" @selected($currentBagType == $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="stacks[{{ $idx }}][packing_size]" class="form-control select2-dynamic" required style="width: 100%;">
                                    <option value="">Select Packing Size</option>
                                    @foreach($packingSizes as $size)
                                        <option value="{{ $size }}" @selected($currentPackingSize == $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="stacks[{{ $idx }}][input_size]" value="{{ $currentInputSize }}" class="form-control" placeholder="Enter Input Size" required>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove_stack_row"><i class="ft-trash-2"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($rejectedDispatchQc)
    <div class="alert alert-warning">
        <strong>Rejected Dispatch QC:</strong> {{ $rejectedDispatchQc->qc_remarks ?? 'No remarks provided.' }}
    </div>
    @endif
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3">{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
    </div>

    @if($loadingSlip->logs->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card mt-2">
                <div class="card-header">
                    <h4 class="card-title">Loading Slip Edit History</h4>
                </div>
                <div class="card-content">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>No. of Bags</th>
                                    <th>Kilogram</th>
                                    <th>QC Remarks</th>
                                    <th>Edited By</th>
                                    <th>Edited At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loadingSlip->logs as $index => $log)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $log->no_of_bags }}</td>
                                        <td>{{ number_format($log->kilogram, 2) }}</td>
                                        <td>{{ $log->qc_remarks ?? '-' }}</td>
                                        <td>{{ $log->editedBy->name ?? 'N/A' }}</td>
                                        <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $(".select2-common, .select2-dynamic").select2({ width: '100%' });

        $(document).on('select2:select select2:unselect', '.select2-common, .select2-dynamic', function() {
            $(this).next('.select2-container').css('width', '100%');
        });

        // Store bag types and packing sizes for dynamic row addition
        window.availableBagTypes = @json($bagTypes);
        window.availablePackingSizes = @json($packingSizes);

        $('#add_stack_row').on('click', function() {
            addStackRow();
        });

        $(document).off('click', '.remove_stack_row').on('click', '.remove_stack_row', function() {
            if ($('#stacks_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateStackIndices();
            } else {
                Swal.fire("Warning", "At least one stack row is required.", "warning");
            }
        });

        $('#no_of_bags').on('input', function() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
            $('#metric_tons_display').val((kilogram / 1000).toFixed(2));
        });
    });

    function addStackRow() {
        var idx = $('#stacks_table tbody tr').length;
        var bagTypeOptions = window.availableBagTypes.map(type => `<option value="${type}">${type}</option>`).join('');
        var packingSizeOptions = window.availablePackingSizes.map(size => `<option value="${size}">${size}</option>`).join('');

        var row = `
            <tr>
                <td>
                    <select name="stacks[${idx}][bag_type]" class="form-control select2-dynamic" required style="width: 100%;">
                        <option value="">Select Bag Type</option>
                        ${bagTypeOptions}
                    </select>
                </td>
                <td>
                    <select name="stacks[${idx}][packing_size]" class="form-control select2-dynamic" required style="width: 100%;">
                        <option value="">Select Packing Size</option>
                        ${packingSizeOptions}
                    </select>
                </td>
                <td>
                    <input type="text" name="stacks[${idx}][input_size]" class="form-control" placeholder="Enter Input Size" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove_stack_row"><i class="ft-trash-2"></i></button>
                </td>
            </tr>
        `;
        $('#stacks_table tbody').append(row);
        $('#stacks_table tbody tr').last().find('.select2-dynamic').select2({ width: '100%' });
    }

    function updateStackIndices() {
        $('#stacks_table tbody tr').each(function(idx) {
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/stacks\[\d+\]/, `stacks[${idx}]`));
                }
            });
        });
    }

</script>
