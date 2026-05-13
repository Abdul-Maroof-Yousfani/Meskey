<div class="modal-body">
    @php
        $availableGalaNames = collect($Orders)->flatMap(fn($o) => $o['gala_names'] ?? [])->filter()->unique()->values();
        $displayGalas = $availableGalaNames
            ->filter(fn($name) => str_contains((string) $loadingSlip->gala, (string) $name))
            ->values();

        if ($displayGalas->isEmpty()) {
            $displayGalas = collect($selectedGalas);
        }
    @endphp
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
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
            <div class="tab-content pt-1" id="orderTabsContent">
                @foreach($Orders as $index => $order)
                    <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}" role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Customer:</label><input type="text" value="{{ $order['customer'] }}" class="form-control" readonly disabled /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Commodity:</label><input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly disabled /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>EO Qty (MT):</label><input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly disabled /></div></div>
                            <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>DO Qty (MT):</label><input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly disabled /></div></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-12">
                                <div class="form-group">
                                    <label>Factory:</label>
                                    <input type="text" value="{{ implode(', ', $order['factory_names']) ?: 'N/A' }}" class="form-control" readonly disabled />
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
                        <input type="number" value="{{ $loadingSlip->bag_size }}" class="form-control" readonly disabled />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>No. of Bags:</label>
                        <input type="text" value="{{ $loadingSlip->no_of_bags ?? 'N/A' }}" disabled class="form-control" readonly />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Empty Bags:</label>
                        <input type="text" value="{{ $loadingSlip->empty_bags ?? 'N/A' }}" disabled class="form-control" readonly />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Gala:</label>
                        <select class="form-control select2-common w-100" multiple disabled style="width: 100% !important;">
                            @foreach($displayGalas as $name)
                                <option selected>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Kilogram:</label>
                        <input type="text" value="{{ $loadingSlip->kilogram ?? 'N/A' }}" disabled class="form-control" readonly />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Metric Tons:</label>
                        <input type="text" value="{{ number_format(($loadingSlip->kilogram ?? 0) / 1000, 2) }}" disabled class="form-control" readonly />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Seal No:</label>
                        <input type="text" value="{{ $loadingSlip->seal_no ?? 'N/A' }}" disabled class="form-control" readonly />
                    </div>
                </div>
            </div>
        @else
            <div class="col-12 text-center">No order data found.</div>
        @endif
    </div>

    <div class="row pt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Stack</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bag Type</th>
                        <th>Packing Size (KG)</th>
                        <th>Input Size (KG)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loadingSlip->stacks as $stack)
                        <tr>
                            <td>{{ $stack->bag_type }}</td>
                            <td>{{ $stack->packing_size }}</td>
                            <td>{{ $stack->input_size }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea class="form-control" readonly disabled>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
    </div>
</div>
<script>
    $(".select2-common").select2({ dropdownParent: $('.modal-body').first(), width: '100%' });
</script>
