<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $DispatchQc->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $DispatchQc->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

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
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Customer:</label>
                                <input type="text" value="{{ $order['customer'] }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Commodity:</label>
                                <input type="text" value="{{ $order['commodity'] }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>EO Qty (MT):</label>
                                <input type="text" value="{{ $order['so_qty'] }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>DO Qty (MT):</label>
                                <input type="text" value="{{ $order['do_qty'] }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Factory:</label>
                                <input type="text" value="{{ implode(', ', $order['factory_names']) }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Gala:</label>
                                <input type="text" value="{{ implode(', ', $order['gala_names']) }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="row">
            <div class="col-12 text-center">No order data found.</div>
        </div>
    @endif

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea class="form-control" readonly>{{ $DispatchQc->qc_remarks }}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <input type="text" value="{{ ucfirst($DispatchQc->status) }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    @if($DispatchQc->attachments->count() > 0)
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Attachments:</label>
                <div class="row">
                    @foreach($DispatchQc->attachments as $attachment)
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body text-center">
                                    @if(Str::contains($attachment->file_type, ['image']))
                                        <img src="{{ asset($attachment->file_path) }}" alt="{{ $attachment->file_name }}" class="img-fluid rounded" style="max-height: 100px;">
                                    @else
                                        <i class="ft-file-text font-large-2"></i>
                                    @endif
                                    <p class="mt-1 mb-1">{{ Str::limit($attachment->file_name, 20) }}</p>
                                    <a href="{{ asset($attachment->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row pt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Outer Items</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Weight (Per Item)</th>
                        <th>Qty</th>
                        <th>Total Weight (KG)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($DispatchQc->loadingProgramItem->outerItems as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ number_format($item->weight, 2) }}</td>
                            <td>{{ number_format($item->qty, 2) }}</td>
                            <td>{{ number_format($item->total_weight, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No outer items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($DispatchQc->loadingProgramItem->outerItems->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                        <td><strong>{{ number_format($DispatchQc->loadingProgramItem->outerItems->sum('total_weight'), 3) }}</strong></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
