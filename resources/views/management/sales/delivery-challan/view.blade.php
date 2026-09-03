<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">General Information</h6>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">DC NO:</label>
                    <input type="text" value="{{ $delivery_challan->dc_no }}" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Date:</label>
                    <input type="date" value="{{ $delivery_challan->dispatch_date }}" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Ticket:</label>
                    @php
                        $firstTicket = $delivery_challan->delivery_challan_data->first();
                        $ticketModel = $firstTicket ? \App\Models\Sales\LoadingProgramItem::find($firstTicket->ticket_id) : null;
                        $ticketDisplay = $ticketModel ? ($ticketModel->transaction_number . ' -- ' . $ticketModel->truck_number) : 'N/A';
                    @endphp
                    <input type="text" class="form-control" value="{{ $ticketDisplay }}" disabled>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Customer:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Customer</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" @selected($delivery_challan->customer_id == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">DO Number:</label>
                    <select class="form-control select2" multiple disabled>
                        <option value="">Select Delivery Order</option>
                        @foreach ($delivery_orders as $delivery_order)
                            <option value="{{ $delivery_order->id }}" @selected(in_array($delivery_order->id, $delivery_challan->delivery_order->pluck('id')->toArray()))>
                                {{ $delivery_order->reference_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Contract Type:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Contract type</option>
                        <option value="pohanch" @selected($delivery_challan->sauda_type == 'pohanch')>Pohanch</option>
                        <option value="x-mill" @selected($delivery_challan->sauda_type == 'x-mill')>X-mill</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 d-none\">
                <div class="form-group">
                    <label class="form-label">Reference Number:</label>
                    <input type="text" value="{{ $delivery_challan->reference_number }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-12 mt-3">
                <h6 class="header-heading-sepration">Location Details</h6>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Locations:</label>
                    <select class="form-control select2" multiple disabled>
                        <option value="">Select Locations</option>
                        @foreach (($locations ?? collect()) as $location)
                            <option value="{{ $location->id }}" @selected(($locationIds ?? collect())->contains($location->id))>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Factory:</label>
                    <select class="form-control select2" multiple disabled>
                        <option value="">Select Factory</option>
                        @foreach (($arrivalLocations ?? collect()) as $location)
                            <option value="{{ $location->id }}" selected>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Gala:</label>
                    <select class="form-control select2" multiple disabled>
                        <option value="">Select Gala</option>
                        @foreach (($sections ?? collect()) as $section)
                            <option value="{{ $section->id }}" selected>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-12 mt-3">
                <h6 class="header-heading-sepration">Service Providers</h6>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Labour:</label>
                    <select class="form-control select2" disabled>
                        @if($delivery_challan->labour)
                            @php $v = \App\Models\Master\Vendor::find($delivery_challan->labour); @endphp
                            @if($v)
                                <option value="{{ $v->id }}" selected>{{ $v->name }}</option>
                            @endif
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Ticket Labour Status:</label>
                    @php
                        $firstTicketData = $delivery_challan->delivery_challan_data->first();
                        $ticketLabour = $delivery_challan->labour_status;
                        if (!$ticketLabour && $firstTicketData && $firstTicketData->ticket_id) {
                            $loadingSlip = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id)?->loadingSlip;
                            $ticketLabour = $loadingSlip?->labour;
                        }
                    @endphp
                    <input type="text" class="form-control"
                        value="{{ $ticketLabour ? ($ticketLabour === 'paid' ? 'Paid' : 'Not Paid') : 'N/A' }}" readonly>
                </div>
            </div>
            @php
                $isXmillNoTransporter = false;
                if (strtolower($delivery_challan->sauda_type) === 'x-mill') {
                    $salesOrder = $delivery_challan->delivery_order->first()?->salesOrder;
                    if ($salesOrder && strtolower($salesOrder->transporter_used) === 'no') {
                        $isXmillNoTransporter = true;
                    }
                }
            @endphp
            @if(!$isXmillNoTransporter)
            @php
                $firstTicketData = $delivery_challan->delivery_challan_data->first();
                $transporterId = $delivery_challan->transporter ?? $delivery_challan->transporter_id;
                if (!$transporterId && $firstTicketData && $firstTicketData->ticket_id) {
                    $ticket = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id);
                    if ($ticket && $ticket->transporter_id) {
                        $transporterId = $ticket->transporter_id;
                    }
                }
            @endphp
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Transporter:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Transporter</option>
                        @foreach (($transporters ?? \App\Models\Master\Transporter::all()) as $transporter)
                            <option value="{{ $transporter->id }}" @selected($transporterId == $transporter->id)>
                                {{ $transporter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            <div class="col-md-4" style="display: none;">
                <div class="form-group">
                    <label class="form-label">In-house Weighbridge:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Weighbridge</option>
                        <option value="1" @selected($delivery_challan->{'inhouse-weighbridge'} == 1)>Weighbridge 1
                        </option>
                        <option value="2" @selected($delivery_challan->{"inhouse-weighbridge"} == 2)>Weighbridge 2
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <!-- <div class="row">
            <div class="col-12 mt-3">
                <h6 class="header-heading-sepration">Financials</h6>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Labour Rate:</label>
                    <input type="text" value="{{ $delivery_challan->labour_rate ?? 'N/A' }}" class="form-control"
                        readonly style="background-color: #f8f9fa;">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Labour Amount:</label>
                    <input type="number" value="{{ $delivery_challan->labour_amount }}" class="form-control" readonly
                        style="background-color: #f8f9fa;">
                    <small class="text-muted">(Rate * Total Bags)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Transporter Amount:</label>
                    <input type="number" value="{{ $delivery_challan->transporter_amount }}" class="form-control"
                        readonly>
                </div>
            </div>
            <div class="col-md-3" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Weighbridge Amount:</label>
                    <input type="number" value="{{ $delivery_challan->{"weighbridge-amount"} }}" class="form-control"
                        readonly>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="form-group">
                    <label class="form-label">Remarks:</label>
                    <textarea class="form-control" readonly rows="3">{{ $delivery_challan->remarks }}</textarea>
                </div>
            </div>
        </div> -->
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
            <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                <thead>
                    <tr>
                        <th>DO No</th>
                        <th>Item</th>
                        <th>Bag Type</th>
                        <th style="width: 250px;">Packing</th>
                        <th>No of Bags</th>
                        <th>Quantity (kg)</th>
                        <!-- <th>Rate per Kg</th>
                        <th>Rate per Mond</th>
                        <th>Amount</th> -->
                        <th>Brand</th>
                        <th>Truck No.</th>
                        <th>Container Number</th>
                        <th>Desc</th>
                    </tr>
                </thead>
                <tbody id="dcTableBody">
                    @foreach ($delivery_challan->delivery_challan_data as $index => $data)
                        @php
                            $index = "TICKET-" . $data->ticket_id;
                        @endphp
                        <tr id="row_{{ $index }}">
                            <td>
                                <input type="text" class="form-control" value="{{ $data->deliveryOrderData?->delivery_order?->reference_no }}" readonly>
                            </td>
                            <td>
                                <input type="text" value="{{ getItem($data->item_id)?->name }}" class="form-control"
                                    readonly>
                            </td>

                            <td>
                                <input type="text" value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}"
                                    class="form-control" readonly>
                            </td>

                            <td>
                                <select class="form-select select2 packing-select" multiple disabled>
                                    @php
                                        $packings = explode(',', $data->bag_size);
                                    @endphp
                                    @foreach($packings as $p)
                                        @if(trim($p))
                                            <option value="{{ trim($p) }}" selected>{{ trim($p) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" value="{{ $data->no_of_bags }}" class="form-control" readonly>
                            </td>
                            <td>
                                <input type="text" value="{{ round($data->qty) }}" class="form-control" readonly>
                            </td>
                            <!-- <td>
                                    <input type="text" value="{{ $data->rate }}" class="form-control" readonly>
                                </td>
                                <td>
                                    <input type="text" value="{{ $data->deliveryOrderData->salesOrderData->rate_per_mond }}"
                                        class="form-control" readonly>
                                </td>
                                <td>
                                    <input type="text" value="{{ round($data->rate * ($data->qty ?? 0)) }}" class="form-control"
                                        readonly>
                                </td> -->
                            <td>
                                <input type="text" value="{{ getBrandById($data->brand_id)?->name }}" class="form-control"
                                    readonly>
                            </td>
                            <td>
                                <input type="text" value="{{ $data->truck_no }}" class="form-control" readonly>
                            </td>
                            <td>
                                <input type="text" value="{{ $data->loadingProgramItem->container_number ?? '' }}"
                                    class="form-control" readonly>
                            </td>
                            <td>
                                <input type="text" value="{{ $data->description }}" class="form-control" readonly>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 text-end">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>

@php
    $dcModule = $delivery_challan->getApprovalModule();
    $dcApprovalLogs = $dcModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $delivery_challan->id)->where('module_id', $dcModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$delivery_challan" :list-refresh="route('sales.get.delivery-challan.list')" />
        </div>
    </div>
</div>

<style>
    .approval-view-wrapper .alert-primary,
    .approval-view-wrapper .alert-warning {
        display: none !important;
    }
    .current-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #047857;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }
    .current-status-dot {
        width: 5px;
        height: 5px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
</style>

@if ($dcApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $dcApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $dcApprovalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 160px; width: 22%;">User</th>
                                <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                <th style="min-width: 300px; width: 40%;">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dcApprovalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        'partial_approved' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                        @if ($log->user_id === auth()->id())
                                            <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                        @if ($loop->first)
                                            <div class="mt-1">
                                                <span class="current-status-tag">
                                                    <span class="current-status-dot"></span> Current
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty(trim($log->comments ?? '')))
                                            <span class="text-dark">{{ $log->comments }}</span>
                                        @else
                                            <span class="text-muted fst-italic">No comments</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="height: 60px; width: 100%; clear: both;"></div>
    </div>
@endif

<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
    });
</script>