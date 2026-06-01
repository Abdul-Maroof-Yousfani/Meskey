@php
    $isExport = ($logistics->type ?? 'sale_order') === 'export_order';
    $documentLabel = $isExport ? 'EO #' : 'SO #';
    $requestLabel = $isExport ? 'Loading Request (Export Order)' : 'Loading Request (Sale Order)';
    $qtyLabel = $isExport ? 'Export Order Qty (MT)' : 'Sales Order Qty (kg)';
    $tradeTermLabel = $isExport ? 'Inco Term EO' : 'Sauda Type';
    $partnerLabel = 'Transporter';
    $fromLocation = is_numeric($logistics->location ?? null)
        ? \App\Models\Master\CompanyLocation::find($logistics->location)?->name
        : $logistics->location;
    $toLocation = $isExport
        ? (\App\Models\Master\Port::find($logistics->to_location)?->name ?? $logistics->to_location)
        : (\App\Models\Master\CompanyLocation::find($logistics->to_location)?->name ?? $logistics->to_location);
@endphp
<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Selection Detail</h6>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Type</label>
                    <input type="text" class="form-control" value="{{ str_replace('_', ' ', ucwords($logistics->type ?? 'sale_order', '_')) }}" readonly>
                </div>
            </div>
        </div>

        <div class="row pt-2">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Document Information</h6>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $requestLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->loading_request }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Date</label>
                    <input type="text" class="form-control" value="{{ $logistics->date }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $documentLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->so_no }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $qtyLabel }}</label>
                    <input type="text" class="form-control" value="{{ number_format($logistics->so_qty, 2) }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Commodity</label>
                    <input type="text" class="form-control" value="{{ $logistics->commodity }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $tradeTermLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->sauda_type }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">From Location</label>
                    <input type="text" class="form-control" value="{{ $fromLocation ?: 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Factory</label>
                    <input type="text" class="form-control" value="{{ $logistics->factory ?: 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $isExport ? 'Port of Loading' : 'To Location' }}</label>
                    <input type="text" class="form-control" value="{{ $toLocation ?: 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Customer</label>
                    <input type="text" class="form-control" value="{{ $logistics->customer }}" readonly>
                </div>
            </div>

            @if($isExport)
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Job Order</label>
                    <input type="text" class="form-control" value="{{ $logistics->job_order }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Return Port</label>
                    <input type="text" class="form-control" value="{{ $logistics->return_port }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Booking No</label>
                    <input type="text" class="form-control" value="{{ $logistics->booking_no }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Shipping Line</label>
                    <input type="text" class="form-control" value="{{ $logistics->shipping_line }}" readonly>
                </div>
            </div>
            @endif
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Logistics Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr class="text-uppercase">
                                <th>Rate Type</th>
                                <th>Rate</th>
                                <th>{{ $partnerLabel }}</th>
                                <th>{{ $isExport ? 'No. of containers' : 'Qty' }}</th>
                                @if($isExport)
                                <th>Brand</th>
                                <th>Packing Size</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logistics->items as $item)
                                <tr>
                                    <td>{{ $item->rate_type }}</td>
                                    <td>{{ number_format($item->rate, 2) }}</td>
                                    <td>{{ $item->transporter_name ?? $item->transporter?->company_name ?? $item->transporter?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($item->qty, 2) }}</td>
                                    @if($isExport)
                                    <td>{{ $item->brand }}</td>
                                    <td>{{ $item->packing_size }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<x-approval-status :model="$logistics" />

<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
