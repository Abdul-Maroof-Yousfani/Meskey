@php
    $firstTicketId = $delivery_challan->delivery_challan_data->pluck('ticket_id')->filter()->first();
    $firstTicket = $firstTicketId ? \App\Models\Sales\LoadingProgramItem::find($firstTicketId) : null;
    $secondWeighbridgeQtyMt = 0;
    if ($firstTicket?->exportLoadingSlip?->secondWeighbridge) {
        $secondWeighbridgeQtyMt = round(((float) $firstTicket->exportLoadingSlip->secondWeighbridge->net_weight) / 1000, 3);
    }
    $totalQtyMt = round((float) $delivery_challan->delivery_challan_data->sum('qty'), 3);

    $locationIds = $firstTicket?->exportLoadingProgram?->company_locations ?? [];
    $locations = !empty($locationIds) ? \App\Models\Master\CompanyLocation::whereIn('id', $locationIds)->get() : collect();
    
    $arrivalLocations = collect();
    $factoryNamesStr = $firstTicket?->exportLoadingSlip?->factory ?? '';
    if ($factoryNamesStr) {
        $factoryNames = array_map('trim', explode(',', $factoryNamesStr));
        $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('name', $factoryNames)->get();
    }
    
    $sections = collect();
    $galaValue = $firstTicket?->exportLoadingSlip?->gala ?? '';
    $decodedGala = json_decode((string) $galaValue, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedGala)) {
        $galaNames = $decodedGala;
    } else {
        $galaNames = array_map('trim', explode(',', (string) $galaValue));
    }
    
    if (!empty($galaNames)) {
        $sections = \App\Models\Master\ArrivalSubLocation::whereIn('name', $galaNames)->get();
    }
@endphp

<style>
    html, body { overflow-x: hidden; }
    .info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px 14px;
        background: #f8fafc;
        height: 100%;
    }
    .info-box .label {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .info-box .value {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
</style>

<div class="row form-mar">
    <div class="col-12"><h6 class="header-heading-sepration">General Information</h6></div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">DC NO:</label>
            <input type="text" class="form-control" value="{{ $delivery_challan->dc_no }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Date:</label>
            <input type="date" class="form-control" value="{{ $delivery_challan->dispatch_date }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Ticket:</label>
            <input type="text" class="form-control" value="{{ $firstTicket ? ($firstTicket->transaction_number . ' -- ' . $firstTicket->truck_number) : 'N/A' }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Customer:</label>
            <input type="text" class="form-control" value="{{ $delivery_challan->customer?->name ?? 'N/A' }}" readonly>
        </div>
    </div>
    <div class="col-md-6 d-none">
        <div class="form-group">
            <label class="form-label">DO Number:</label>
            <input type="text" class="form-control" value="{{ $delivery_challan->delivery_order->pluck('reference_no')->implode(', ') }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Reference Number:</label>
            <input type="text" class="form-control" value="{{ $delivery_challan->reference_number }}" readonly>
        </div>
    </div>
    <div class="col-12 mt-3"><h6 class="header-heading-sepration">Location Details</h6></div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Locations:</label>
            <select class="form-control select2" multiple disabled>
                @foreach (($locations ?? collect()) as $location)
                    <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Factory:</label>
            <select class="form-control select2" multiple disabled>
                @foreach (($arrivalLocations ?? collect()) as $location)
                    <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Gala:</label>
            <select class="form-control select2" multiple disabled>
                @foreach (($sections ?? collect()) as $section)
                    <option value="{{ $section->id }}" selected>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-12 mt-3"><h6 class="header-heading-sepration">Service Providers</h6></div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Labour:</label>
            <input type="text" class="form-control" value="{{ $delivery_challan->labour ? (\App\Models\Master\Vendor::find($delivery_challan->labour)?->name ?? 'N/A') : 'N/A' }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Transporter:</label>
            <select class="form-control select2" disabled>
                @foreach ($Transporters ?? [] as $transporter)
                    <option value="{{ $transporter->id }}" @selected($delivery_challan->transporter == $transporter->id)>{{ $transporter->name }}</option>
                @endforeach
            </select>
        </div>
    </div>


    <div class="col-12 mt-3">
        <div class="form-group">
            <label class="form-label">Remarks:</label>
            <textarea class="form-control" rows="3" readonly>{{ $delivery_challan->remarks }}</textarea>
        </div>
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-4">
        <div class="info-box">
            <span class="label">Second Weighbridge Qty (MT)</span>
            <span class="value">{{ number_format($secondWeighbridgeQtyMt, 3) }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="label">Total Line Items Qty (MT)</span>
            <span class="value">{{ number_format($totalQtyMt, 3) }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="label">Remaining Qty (MT)</span>
            <span class="value">{{ number_format(max($secondWeighbridgeQtyMt - $totalQtyMt, 0), 3) }}</span>
        </div>
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="table-responsive" style="overflow-x:auto; white-space:nowrap;">
            <table class="table table-bordered" style="min-width:1600px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>No. of Bags</th>
                        <th>Quantity (MT)</th>
                        <th>Rate per MT</th>
                        <th>Amount</th>
                        <th>Labour Rate</th>
                        <th>Labour Amount</th>
                        <th>Brand</th>
                        <th>Truck No.</th>
                        <th>Container No.</th>
                        <th>Desc</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($delivery_challan->delivery_challan_data as $data)
                        <tr>
                            <td><input type="text" class="form-control" value="{{ getItem($data->item_id)?->name ?? 'N/A' }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->bag_size ? rtrim(rtrim(number_format((float) $data->bag_size, 3, '.', ''), '0'), '.') . ' KG' : '-' }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->no_of_bags }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->qty }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->rate }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ number_format($data->rate * $data->qty, 2) }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->labour_rate ?? 0 }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->labour_amount ?? 0 }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ getBrandById($data->brand_id)?->name ?? '-' }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->truck_no }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->container_number ?: ($data->loadingProgramItem->container_number ?? '') }}" readonly></td>
                            <td><input type="text" class="form-control" value="{{ $data->description }}" readonly></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 text-end">
        <a type="button" class="btn btn-danger modal-sidebar-close closebutton me-2">Close</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <x-approval-status :model="$delivery_challan" />
    </div>
</div>

<script>
    $('.select2').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }
    });
    $('.select2').select2({ width: '100%' });
</script>
