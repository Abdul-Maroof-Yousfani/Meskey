<div id="export_order_details_wrapper" class="col-12">
    <h6 class="header-heading-sepration">Export Order Details</h6>
    <div class="card card-outline-info">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs horizontal-scrollable-tabs" id="eo-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                @foreach($ExportOrders as $index => $eo)
                    <li class="nav-item" style="flex: 0 0 auto;">
                        <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="eo-tab-{{ $eo->id }}" data-toggle="pill" href="#eo-content-{{ $eo->id }}" role="tab" aria-controls="eo-content-{{ $eo->id }}" aria-selected="{{ $index == 0 ? 'true' : 'false' }}">
                            {{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-'.$eo->id }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="eo-details-tabs-content">
                @foreach($ExportOrders as $index => $eo)
                    <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="eo-content-{{ $eo->id }}" role="tabpanel" aria-labelledby="eo-tab-{{ $eo->id }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Buyer:</label>
                                    <input type="text" value="{{ $eo->buyer?->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Commodity:</label>
                                    <input type="text" value="{{ $eo->product?->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>EO Date:</label>
                                    <input type="text" value="{{ optional($eo->voucher_date)->format('Y-m-d') ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Total MT:</label>
                                    <input type="text" value="{{ $eo->packingItems->sum('metric_tons') }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div id="delivery_order_details_wrapper" class="col-12 mt-3" style="display: none;">
    <h6 class="header-heading-sepration">Delivery Order Details</h6>
    <div class="card card-outline-info">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs horizontal-scrollable-tabs" id="do-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                @foreach($DeliveryOrders as $index => $do)
                    <li class="nav-item do-tab-item" data-do-id="{{ $do->id }}" style="flex: 0 0 auto; display: none;">
                        <a class="nav-link" id="do-tab-{{ $do->id }}" data-toggle="pill" href="#do-content-{{ $do->id }}" role="tab" aria-controls="do-content-{{ $do->id }}">
                            {{ $do->reference_no }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="do-details-tabs-content">
                @if($DeliveryOrders->isEmpty())
                    <p class="text-center text-muted">No delivery orders available for selection.</p>
                @endif
                @foreach($DeliveryOrders as $index => $do)
                    <div class="tab-pane fade do-pane" id="do-content-{{ $do->id }}" data-do-id="{{ $do->id }}" role="tabpanel" aria-labelledby="do-tab-{{ $do->id }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Delivery Order No:</label>
                                    <input type="text" value="{{ $do->reference_no }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Buyer:</label>
                                    <input type="text" value="{{ $do->customer?->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Balance MT:</label>
                                    <input type="text" value="{{ number_format(get_second_weighbridge_balance_by_delivery_order($do->id), 3) }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
