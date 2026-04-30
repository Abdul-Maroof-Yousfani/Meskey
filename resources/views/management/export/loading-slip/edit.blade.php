<form action="{{ route('export-loading-slip.update', $loadingSlip->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-slip') }}" />

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
                $selectedGalas = array_map('trim', explode(',', $loadingSlip->gala));
                $allGalaNames = collect($Orders)->pluck('gala_names')->flatten()->filter()->unique()->values();
                $factoryNames = collect($Orders)->pluck('factory_names')->flatten()->filter()->unique()->implode(', ');
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
                        <input type="number" value="{{ $loadingSlip->bag_size }}" class="form-control" readonly step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Gala: <span class="text-danger">*</span></label>
                        <select class="form-control select2 w-100" name="gala[]" multiple required style="width: 100% !important;" {{ (isset($canEdit) && !$canEdit) ? 'disabled' : '' }}>
                            @foreach($allGalaNames as $name)
                                <option value="{{ $name }}" @selected(in_array($name, $selectedGalas))>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <input type="hidden" name="customer" value="{{ $loadingSlip->customer }}" />
            <input type="hidden" name="commodity" value="{{ $loadingSlip->commodity }}" />
            <input type="hidden" name="so_qty" value="{{ $loadingSlip->so_qty }}" />
            <input type="hidden" name="do_qty" value="{{ $loadingSlip->do_qty }}" />
            <input type="hidden" name="factory" value="{{ $loadingSlip->factory }}" />
            <input type="hidden" name="bag_size" value="{{ $loadingSlip->bag_size }}" />
        @else
            <div class="col-12 text-center">No order data found.</div>
        @endif
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>No. of Bags: <span class="text-danger">*</span></label>
                <input type="number" name="no_of_bags" id="no_of_bags" value="{{ $loadingSlip->no_of_bags }}" class="form-control" min="1" required {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Labour</label>
                <select name='labour' id='labour' class='form-control select2' {{ (isset($canEdit) && !$canEdit) ? 'disabled' : '' }}>
                    <option value='paid' @selected($loadingSlip->labour == 'paid')>Paid</option>
                    <option value='not_paid' @selected($loadingSlip->labour == 'not_paid')>Not Paid</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="number" name="kilogram" id="kilogram" value="{{ $loadingSlip->kilogram ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Metric Tons:</label>
                <input type="number" id="metric_tons_display" value="{{ number_format(($loadingSlip->kilogram ?? 0) / 1000, 2, '.', '') }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="form-group">
                <label>Seal No:</label>
                <input type="text" name="seal_no" id="seal_no" value="{{ $loadingSlip->seal_no }}" class="form-control" placeholder="Enter Seal No" {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }} />
            </div>
        </div>
    </div>

    <div class="row pt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Stack</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bag Type</th>
                        <th>Packing Size</th>
                        <th>Input Size <span class="text-danger">*</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loadingSlip->stacks as $idx => $stack)
                        <tr>
                            <td>
                                {{ $stack->bag_type }}
                                <input type="hidden" name="stacks[{{ $idx }}][bag_type]" value="{{ $stack->bag_type }}">
                            </td>
                            <td>
                                {{ $stack->packing_size }}
                                <input type="hidden" name="stacks[{{ $idx }}][packing_size]" value="{{ $stack->packing_size }}">
                            </td>
                            <td>
                                <input type="text" name="stacks[{{ $idx }}][input_size]" value="{{ $stack->input_size }}" class="form-control" placeholder="Enter Input Size" required {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>
                            </td>
                        </tr>
                    @empty
                        @foreach ($fallbackStacks as $idx => $item)
                            <tr>
                                <td>
                                    {{ $item['bag_type'] }}
                                    <input type="hidden" name="stacks[{{ $idx }}][bag_type]" value="{{ $item['bag_type'] }}">
                                </td>
                                <td>
                                    {{ $item['packing_size'] }}
                                    <input type="hidden" name="stacks[{{ $idx }}][packing_size]" value="{{ $item['packing_size'] }}">
                                </td>
                                <td>
                                    <input type="text" name="stacks[{{ $idx }}][input_size]" class="form-control" placeholder="Enter Input Size" required {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    @endforelse
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
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3" {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>{{ $loadingSlip->remarks }}</textarea>
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
            @if(!isset($canEdit) || $canEdit)
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
            @endif
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $(".select2").select2({ dropdownParent: $('#modal-sidebar') });
        $('#no_of_bags').on('input', function() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
            $('#metric_tons_display').val((kilogram / 1000).toFixed(2));
        });
    });
</script>
