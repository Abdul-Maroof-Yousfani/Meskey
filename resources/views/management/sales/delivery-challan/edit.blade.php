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

    .packing-select+.select2-container .select2-selection--multiple {
        min-width: 130px !important;
        width: 130px !important;
    }

    #salesInquiryTable td {
        padding: 5px 10px !important;
    }
</style>

<form action="{{ route('sales.delivery-challan.update', ['delivery_challan' => $delivery_challan->id]) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field("PUT") }}
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-challan.list') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">DC NO:</label>
                        <input type="text" name="dc_no" value="{{ $delivery_challan->dc_no }}" id="dc_no"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" readonly name="date" onchange="getNumber()"
                            value="{{ $delivery_challan->dispatch_date }}" id="date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Select Ticket: <span class="text-danger">*</span></label>
                        <select name="initial_ticket_id" id="initial_ticket_id" onchange="onInitialTicketSelect(this)"
                            class="form-control select2">
                            <option value="">Select Ticket</option>
                            @php
                                $firstTicketData = $delivery_challan->delivery_challan_data->first();
                                $currentTicketId = $firstTicketData ? $firstTicketData->ticket_id : null;
                                $currentTicket = $currentTicketId ? \App\Models\Sales\LoadingProgramItem::find($currentTicketId) : null;
                            @endphp
                            @if($currentTicket)
                                <option value="{{ $currentTicket->id }}" selected>
                                    {{ $currentTicket->transaction_number . ' -- ' . $currentTicket->truck_number }}
                                </option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <select id="customer_id_display" class="form-control select2" disabled>
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}"
                                    @selected($delivery_challan->customer_id == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="customer_id" id="customer_id"
                            value="{{ $delivery_challan->customer_id }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">DO Number:</label>
                        <select name="do_no[]" id="do_no" class="form-control select2" multiple disabled>
                            <option value="">Select Delivery Order</option>
                            @foreach ($delivery_orders as $delivery_order)
                                <option value="{{ $delivery_order->id }}" @selected(in_array($delivery_order->id, $delivery_challan->delivery_order->pluck('id')->toArray()))>
                                    {{ $delivery_order->reference_no }}
                                </option>
                            @endforeach
                        </select>
                        <input type='hidden' name="delivery_order_id" id="delivery_order_id"
                            value="{{ implode(',', $delivery_challan->delivery_order->pluck('id')->toArray()) }}" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contract Type:</label>
                        <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                            <option value="">Select Contract type</option>
                            <option value="pohanch" @selected($delivery_challan->sauda_type == 'pohanch')>Pohanch</option>
                            <option value="x-mill" @selected($delivery_challan->sauda_type == 'x-mill')>X-mill</option>
                        </select>
                        <input type="hidden" name="sauda_type" id="sauda_type_hidden"
                            value="{{ $delivery_challan->sauda_type }}">
                    </div>
                </div>
                <div class="col-md-6 d-none">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number"
                            value="{{ $delivery_challan->reference_number }}" class="form-control">
                    </div>
                </div>


                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Location Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="locations[]" id="locations" class="form-control select2" disabled>
                            <option value="">Select Locations</option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}"
                                    @selected($location->id == $delivery_challan->location_id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="locations_hidden"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_locations[]" id="arrivals" class="form-control select2" multiple disabled>
                            <option value="">Select Factory</option>
                            @foreach (($arrivalLocations ?? collect()) as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, explode(",", $delivery_challan->arrival_id)))>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="arrivals_hidden">
                            <input type="hidden" name="arrival_location_csv" id="arrival_location_csv"
                                value="{{ $delivery_challan->arrival_id }}" />
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Gala:</label>
                        <select name="storage_id[]" id="storages" class="form-control select2" multiple disabled>
                            <option value="">Select Gala</option>
                            @foreach (($sections ?? collect()) as $section)
                                <option value="{{ $section->id }}" @selected(in_array($section->id, explode(",", $delivery_challan->section_id)))>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="storages_hidden">
                            <input type="hidden" name="storage_location_csv" id="storage_location_csv"
                                value="{{ $delivery_challan->section_id }}" />
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Service Providers</h6>
                </div>
                @php
                    $firstTicketData = $delivery_challan->delivery_challan_data->first();
                    $hasTicketTransporter = false;
                    if ($firstTicketData && $firstTicketData->ticket_id) {
                        $ticket = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id);
                        if ($ticket && $ticket->transporter_id) {
                            $hasTicketTransporter = true;
                        }
                    }
                    $transporterId = $delivery_challan->transporter;
                @endphp
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour:</label>
                        <select name="labour" id="labour" class="form-control select2">
                            <option value="">Select Labour</option>
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
                        <label class="form-label">Labour Status:</label>
                        @php
                            $firstTicketData = $delivery_challan->delivery_challan_data->first();
                            $ticketLabour = $delivery_challan->labour_status;
                            if (!$ticketLabour && $firstTicketData && $firstTicketData->ticket_id) {
                                $loadingSlip = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id)?->loadingSlip;
                                $ticketLabour = $loadingSlip?->labour;
                            }
                        @endphp
                        <select name="labour_status" id="labour_status" class="form-control select2">
                            <option value="">Select Labour status</option>
                            <option value="paid" @selected($ticketLabour == 'paid')>Paid</option>
                            <option value="not_paid" @selected($ticketLabour == 'not_paid')>Not Paid</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4" id="transporter_col">
                    <div class="form-group">
                        <label class="form-label">Transporter:</label>
                        <select id="transporter_display" class="form-control select2"
                            onchange="$('#transporter').val(this.value)" {{ $hasTicketTransporter ? 'disabled' : '' }}>
                            <option value="">Select Transporter</option>
                            @foreach ($transporters ?? [] as $transporter)
                                <option value="{{ $transporter->id }}" @selected($transporterId == $transporter->id)>
                                    {{ $transporter->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="transporter" id="transporter" value="{{ $transporterId }}">
                    </div>
                </div>
                <div class="col-md-4" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">In-house Weighbridge:</label>
                        <select name="weighbridge" id="weighbridge" class="form-control select2">
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
                        <input type="text" name="labour_rate" id="standard_labour_rate" class="form-control"
                            value="{{ $delivery_challan->labour_rate ?? 'N/A' }}" readonly
                            style="background-color: #f8f9fa;">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour Amount:</label>
                        <input type="number" name="labour_amount" value="{{ $delivery_challan->labour_amount }}"
                            id="labour_amount" class="form-control" readonly style="background-color: #f8f9fa;">
                        <small class="text-muted">(Rate * Total Bags)</small>
                    </div>
                </div>
                <div class="col-md-4" id="transporter_amount_col">
                    <div class="form-group">
                        <label class="form-label">Transporter Amount:</label>
                        <input type="number" name="transporter_amount"
                            value="{{ $delivery_challan->transporter_amount }}" id="transporter_amount"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-3" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Weighbridge Amount:</label>
                        <input type="number" name="weighbridge_amount"
                            value="{{ $delivery_challan->{"weighbridge-amount"} }}" id="weighbridge_amount"
                            class="form-control">
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <div class="form-group">
                        <label class="form-label">Remarks:</label>
                        <textarea name="remarks" id="remarks" class="form-control"
                            rows="3">{{ $delivery_challan->remarks }}</textarea>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <!-- <div class="row mt-4" id="addTicketContainer" style="display: none;">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Add More Tickets:</label>
                    <select id="add_ticket_id" class="form-control select2">
                        <option value="">Select Ticket to Add</option>
                    </select>
                    <small class="text-muted">Only tickets from the selected Delivery Orders can be added.</small>
                </div>
            </div> -->
    </div>

    <div class="row mt-4">
        <div class="col-12 d-flex justify-content-between align-items-center mb-2">
            <h6 class="m-0 font-weight-bold color-dark">Item Details</h6>
            <!-- <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()" id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button> -->
        </div>
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="salesInquiryTable" style="min-width:2000px;">
                    <thead class="bg-light">
                        <tr>
                            <th>DO No</th>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th style="min-width: 130px; width: 130px;">Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (kg)</th>
                            <!-- <th>Rate (Kg)</th>
                            <th>Rate (Mond)</th>
                            <th>Amount</th> -->
                            <th>Brand</th>
                            <th>Truck No.</th>
                            <th>Container Number</th>
                            <th>Desc</th>
                            <th style="display: none">Packing</th>
                            <th>Action</th>
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
                                    <input type="text" name="" id="item_id_read_only{{ $index }}"
                                        value="{{ getItem($data->item_id)?->name }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0" readonly>
                                    <input type="hidden" name="item_id[]" id="item_id_{{ $index }}"
                                        value="{{ $data->item_id }}" class="item_id">
                                    <input type="hidden" name="ticket_id[]" id="ticket_id_{{ $index }}"
                                        value="{{ $data->ticket_id }}" class="ticket_id">
                                    <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}"
                                        value="{{ $data->do_data_id }}" class="do_data_id">
                                </td>
                                <td>
                                    <input type="text" name="" id="bag_type_display_{{ $index }}"
                                        value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}"
                                        class="form-control" readonly>
                                    <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}"
                                        value="{{ $data->bag_type }}">
                                    <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                                        value="{{ $data->id }}">
                                </td>
                                <td>
                                    <input type="hidden" name="bag_size[]" id="bag_size_{{ $index }}"
                                        value="{{ $data->bag_size }}" class="form-control bag_size">
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
                                    <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ $data->loadingProgramItem->loadingSlip->no_of_bags ?? 0 }}"
                                        class="form-control no_of_bags" readonly>
                                </td>
                                <td>
                                    <input type="text" name="qty[]" id="qty_{{ $index }}" value="{{ round($data->qty) }}"
                                        class="form-control qty" oninput="calc(this)" readonly>
                                </td>
                                <td class="d-none">
                                    <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $data->rate }}"
                                        class="form-control rate" readonly>
                                </td>
                                <td class="d-none">
                                    <input type="text" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}"
                                        value="{{ $data->deliveryOrderData->salesOrderData->rate_per_mond ?? '' }}"
                                        class="form-control rate" readonly>
                                </td>
                                <td class="d-none">
                                    <input type="text" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ round($data->rate * ($data->qty ?? 0)) }}" class="form-control amount"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" name="" id="brand_id_display_{{ $index }}"
                                        value="{{ getBrandById($data->brand_id)?->name }}" class="form-control" readonly>
                                    <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}"
                                        value="{{ $data->brand_id }}">
                                </td>
                                <td>
                                    <input type="text" name="truck_no[]" id="truck_no_{{ $index }}"
                                        class="form-control truck_no" value="{{ $data->truck_no }}" readonly>
                                </td>
                                <td>
                                    <input type="text" name="container_number[]" id="container_number_{{ $index }}"
                                        value="{{ $data->loadingProgramItem->container_number ?? '' }}"
                                        class="form-control container_number" readonly>
                                </td>
                                <td>
                                    <input type="text" name="desc[]" id="desc_{{ $index }}" class="form-control"
                                        value="{{ $data->description }}">
                                </td>
                                <td>
                                    @php
                                        $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                                        $ticketText = $ticket ? ($ticket->transaction_number . ' -- ' . $ticket->truck_number) : '';
                                    @endphp
                                    <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                        data-ticket-id="{{ $data->ticket_id }}" data-ticket-text="{{ $ticketText }}"
                                        onclick="removeTicketRow(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    <div class="row my-2" id="labour_slab_error_row" style="display: none;">
        <div class="col-12">
            <div class="alert alert-danger border-start border-danger border-3 mb-0" role="alert">
                <div class="d-flex align-items-center">
                    <div>
                        <strong class="text-white" style="font-size: 14px;">Labour Rate Slab Not Found!</strong><br>
                        <span class="text-white">No matching labour rate slab was found. Labour Amount cannot be 0. Please configure the labour rate slab.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $dcModule = $delivery_challan->getApprovalModule();
        $dcApprovalLogs = $dcModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $delivery_challan->id)->where('module_id', $dcModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
    @endphp

    @if ($dcApprovalLogs->isNotEmpty())
        <style>
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
        <div class="approval-table-wrapper mx-2" style="margin-top: 15px; margin-bottom: 25px;">
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
        </div>
    @endif

    <div class="row bottom-button-bar text-right">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Delivery Challan</button>
        </div>
    </div>
    </div>
</form>

<script>
    // Track which tickets have been added
    addedTicketIds = @json($delivery_challan->delivery_challan_data->pluck('ticket_id')->filter()->unique()->map(fn($id) => (int) $id)->values());
    doMeta = {};
    initialTicketId = "{{ $currentTicketId }}";

    $(document).ready(function () {
        $('.select2').select2();

        // Load tickets with accepted Dispatch QC on page load
        loadTicketsWithDispatchQc();

        // Load additional tickets if DOs are already selected
        const selectedDos = $("#do_no").val();
        if (selectedDos && selectedDos.length > 0) {
            loadAdditionalTickets(Array.isArray(selectedDos) ? selectedDos : [selectedDos]);
        }

        // Initialize labour population based on existing arrival locations
        const arrivalIds = $("#arrival_location_csv").val();
        if (arrivalIds) {
            updateLabourVendors(arrivalIds.split(','));
        }

        // Calculate labour amount on load
        calculateLabourAmount();
    });

    // Load tickets with accepted Dispatch QC
    function loadTicketsWithDispatchQc() {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-tickets-with-dispatch-qc') }}",
            method: "GET",
            data: { delivery_challan_id: {{ $delivery_challan->id }} },
            dataType: "json",
            success: function (response) {
                const select = $("#initial_ticket_id");
                const currentValue = select.val() || initialTicketId;

                select.empty().append('<option value="">Select Ticket</option>');

                if (response.tickets && response.tickets.length > 0) {
                    response.tickets.forEach(function (ticket) {
                        select.append(`<option value="${ticket.id}" ${ticket.id == currentValue ? 'selected' : ''}>${ticket.text}</option>`);
                    });
                }

                select.select2();
            },
            error: function (error) {
                console.error('Error loading tickets:', error);
            }
        });
    }

    // Handle initial ticket selection - auto-fill form fields
    function onInitialTicketSelect(el) {
        const ticketId = $(el).val();
        if (!ticketId) {
            resetFormFields();
            return;
        }

        $.ajax({
            url: "{{ route('sales.delivery-challan.get-ticket-data') }}",
            method: "GET",
            data: { ticket_id: ticketId },
            dataType: "json",
            beforeSend: function () {
                Swal.fire({
                    title: "Loading...",
                    text: "Fetching ticket data",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (response) {
                Swal.close();

                if (response.success) {
                    // Update Ticket Labour Status
                    if(response.loading_slip_labour) {
                        $("#labour_status").val(response.loading_slip_labour).trigger('change');
                    }

                    // Set Labour Rate
                    $("#standard_labour_rate").val(response.rate || 'N/A');

                    // Set Sauda Type
                    $("#sauda_type").val(response.delivery_order.sauda_type).trigger('change');
                    $("#sauda_type_hidden").val(response.delivery_order.sauda_type);

                    // Set Customer
                    $("#customer_id_display").val(response.customer.id).trigger('change');
                    $("#customer_id").val(response.customer.id);

                    // Set DO Number
                    const doSelect = $("#do_no");
                    doSelect.empty();
                    
                    const doIds = [];
                    if (response.delivery_orders && response.delivery_orders.length > 0) {
                        response.delivery_orders.forEach(function(d) {
                            doSelect.append(`<option value="${d.id}" selected>${d.reference_no}</option>`);
                            doIds.push(d.id);
                        });
                    } else if (response.delivery_order) {
                        doSelect.append(`<option value="${response.delivery_order.id}" selected>${response.delivery_order.reference_no}</option>`);
                        doIds.push(response.delivery_order.id);
                    }
                    $("#delivery_order_id").val(doIds.join(','));
                    doSelect.trigger('change');

                    // Set Locations
                    const locSelect = $("#locations");
                    locSelect.empty();
                    response.locations.company_locations.forEach(loc => {
                        locSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    locSelect.trigger('change');
                    setHidden("locations", response.locations.company_location_ids);

                    // Set Factory
                    const arrSelect = $("#arrivals");
                    arrSelect.empty();
                    response.locations.arrival_locations.forEach(loc => {
                        arrSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    arrSelect.trigger('change');
                    $("#arrival_location_csv").val(response.locations.arrival_location_ids.join(','));

                    // Fetch and populate labours based on factories
                    updateLabourVendors(response.locations.arrival_location_ids);

                    // Set Gala
                    const secSelect = $("#storages");
                    secSelect.empty();
                    response.locations.sub_arrival_locations.forEach(loc => {
                        secSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    secSelect.trigger('change');
                    $("#storage_location_csv").val(response.locations.sub_arrival_location_ids.join(','));

                    // Set Transporter
                    const transSelect = $("#transporter_display");

                    let isXmillNoTransporter = false;
                    if (response.delivery_order && response.delivery_order.sauda_type === 'x-mill') {
                        if (response.delivery_order.transporter_used === 'no') {
                            isXmillNoTransporter = true;
                        }
                    }

                    if (isXmillNoTransporter) {
                        transSelect.val('').trigger('change');
                        transSelect.prop('disabled', true);
                        $("#transporter").val('');
                        $("#transporter_col").hide();
                        $("#transporter_amount_col").hide();
                    } else {
                        if (response.transporter && response.transporter.id) {
                            transSelect.val(response.transporter.id).trigger('change');
                            transSelect.prop('disabled', true);
                            $("#transporter").val(response.transporter.id);
                            $("#transporter_col").show();
                            $("#transporter_amount_col").show();
                        } else {
                            transSelect.val('').trigger('change');
                            transSelect.prop('disabled', false);
                            $("#transporter").val('');
                            $("#transporter_col").show();
                            $("#transporter_amount_col").show();
                        }
                    }

                    // Set Remarks
                    $("#remarks").val(response.delivery_order.remarks || '');

                    // Load Item Details for the initial ticket
                    loadInitialTicketItems(ticketId);

                    // Load additional tickets for the same DO
                    loadAdditionalTickets([response.delivery_order.id]);
                }
            },
            error: function (error) {
                Swal.close();
                console.error('Error fetching ticket data:', error);
            }
        });
    }

    function loadInitialTicketItems(ticketId) {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-ticket-items') }}",
            method: "GET",
            data: { ticket_id: ticketId },
            dataType: "html",
            success: function (res) {
                $("#dcTableBody").empty().append(res);
                addedTicketIds = [parseInt(ticketId)];
                calculateLabourAmount();
            }
        });
    }

    function resetFormFields() {
        $("#labour_status").val('').trigger('change');
        $("#standard_labour_rate, #customer_id, #delivery_order_id, #arrival_location_csv, #storage_location_csv").val('');
        $("#customer_id_display, #do_no, #sauda_type, #locations, #arrivals, #storages").val('').trigger('change');
        $("#transporter_display").empty().append('<option value="">Select Transporter</option>').trigger('change');
        $("#transporter").val('');
        $("#dcTableBody").empty();
        addedTicketIds = [];
    }

    // Load additional tickets for the same delivery order
    function loadAdditionalTickets(deliveryOrderIds) {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-tickets') }}",
            method: "GET",
            data: {
                delivery_order_ids: Array.isArray(deliveryOrderIds) ? deliveryOrderIds : [deliveryOrderIds],
                delivery_challan_id: {{ $delivery_challan->id }}
            },
            success: function (response) {
                const select = $("#add_ticket_id");
                select.empty().append('<option value="">Select Ticket to Add</option>');

                if (response.tickets && response.tickets.length > 0) {
                    let hasOptions = false;
                    response.tickets.forEach(function (ticket) {
                        // Exclude already added tickets
                        if (!addedTicketIds.includes(ticket.id)) {
                            select.append(`<option value="${ticket.id}">${ticket.text}</option>`);
                            hasOptions = true;
                        }
                    });

                    if (hasOptions) {
                        $("#addTicketContainer").show();
                    } else {
                        $("#addTicketContainer").hide();
                    }
                } else {
                    $("#addTicketContainer").hide();
                }

                select.select2();
            },
            error: function (error) {
                console.error('Error loading additional tickets:', error);
            }
        });
    }

    // Handle adding more tickets
    $("#add_ticket_id").on("change", function () {
        const ticketId = $(this).val();

        if (!ticketId) return;

        // Check if already added
        if (addedTicketIds.includes(parseInt(ticketId))) {
            Swal.fire("Warning", "This ticket has already been added", "warning");
            $(this).val('').trigger('change');
            return;
        }

        // Load the ticket item row and append to table
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-ticket-items') }}",
            method: "GET",
            data: { ticket_id: ticketId },
            dataType: "html",
            success: function (res) {
                $("#dcTableBody").append(res);
                $(".select2").select2({ width: '100%' });

                // Track this ticket as added
                addedTicketIds.push(parseInt(ticketId));

                // Remove this ticket from the dropdown
                $("#add_ticket_id option[value='" + ticketId + "']").remove();
                $("#add_ticket_id").val('').trigger('change');

                calculateLabourAmount();

                // Hide the dropdown if no more tickets available
                if ($("#add_ticket_id option").length <= 1) {
                    $("#addTicketContainer").hide();
                }
            },
            error: function (error) {
                console.error('Error loading ticket items:', error);
                Swal.fire("Error", "Failed to load ticket data", "error");
            }
        });
    });

    // Remove ticket row from table
    function removeTicketRow(btn) {
        const ticketId = $(btn).data('ticket-id');
        const ticketText = $(btn).data('ticket-text');

        // Check if this is the last row
        if ($("#dcTableBody tr").length <= 1) {
            Swal.fire("Warning", "Cannot remove the last ticket. At least one ticket is required.", "warning");
            return;
        }

        // Remove from table
        $(btn).closest('tr').remove();

        // Remove from tracked IDs
        addedTicketIds = addedTicketIds.filter(id => id !== parseInt(ticketId));

        // Add back to dropdown
        const select = $("#add_ticket_id");
        select.append(`<option value="${ticketId}">${ticketText}</option>`);
        select.select2();

        // Show the dropdown if it was hidden
        $("#addTicketContainer").show();

        calculateLabourAmount();
    }

    sum = 0;
    so_amount = 0;
    remaining_amount = 0;

    function setHidden(name, values) {
        const container = $(`#${name}_hidden`);
        container.empty();
        (values || []).forEach(v => {
            container.append(`<input type="hidden" name="${name}[]" value="${v}">`);
        });
    }

    function calcAmount(el) {
        const element = $(el).closest("tr");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        if (!qty.val() || !rate.val()) {
            amount.val("");
            return;
        }
        const result = parseFloat(qty.val()) * parseFloat(rate.val());
        amount.val(result);
    }

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");

        const bagSizeVal = parseFloat(bag_size.val());
        const qtyVal = parseFloat(qty.val());

        if (!bagSizeVal || !qtyVal) {
            no_of_bags.val("");
            calcAmount(el);
            return;
        }

        const bagsResult = (qtyVal / bagSizeVal).toFixed();

        no_of_bags.val(bagsResult);
        calcAmount(el);
    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.delivery-challan.getNumber') }}",
            method: "GET",
            data: {
                contract_date: $("#date").val()
            },
            dataType: "json",
            success: function (res) {
                $("#dc_no").val(res.dc_no)
            },
            error: function (error) {
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    $(".select2").select2({ width: '100%' });
    function calculateLabourAmount() {
        let totalBags = 0;
        $(".no_of_bags").each(function () {
            let bags = parseFloat($(this).val()) || 0;
            totalBags += bags;
        });

        let rateVal = $("#standard_labour_rate").val();
        let rate = (rateVal === 'N/A' || !rateVal) ? 0 : (parseFloat(rateVal) || 0);
        let amount = totalBags * rate;
        $("#labour_amount").val(amount.toFixed(2));
        validateLabourSlab();
    }

    function validateLabourSlab() {
        const labourVal = $("#labour").val();
        const labourRateVal = $("#standard_labour_rate").val();
        const labourRate = (labourRateVal === 'N/A' || !labourRateVal) ? 0 : (parseFloat(labourRateVal) || 0);
        const labourAmount = parseFloat($("#labour_amount").val()) || 0;
        const isRateNA = (labourRateVal === 'N/A' || !labourRateVal || labourRate <= 0);
        const hasItems = $("#dcTableBody tr").length > 0;

        if (labourVal && hasItems && (isRateNA || labourAmount <= 0)) {
            $("#labour_slab_error_row").slideDown(200);
            $(".submitbutton").prop("disabled", true).addClass("disabled");
            return false;
        } else {
            $("#labour_slab_error_row").slideUp(200);
            $(".submitbutton").prop("disabled", false).removeClass("disabled");
            return true;
        }
    }

    // Override the existing calc function or ensure it calls calculateLabourAmount
    originalCalc = calc;
    calc = function (el) {
        originalCalc(el);
        calculateLabourAmount();
    };

    originalCalcAmount = calcAmount;
    calcAmount = function (el) {
        originalCalcAmount(el);
        calculateLabourAmount();
    };
    function updateLabourVendors(arrivalLocationIds) {
        if (!arrivalLocationIds || arrivalLocationIds.length === 0) {
            $("#labour").empty().trigger('change.select2');
            return;
        }

        $.ajax({
            url: "{{ route('vendor.get-vendors-by-locations') }}",
            method: "GET",
            data: { arrival_location_ids: arrivalLocationIds },
            dataType: "json",
            success: function (vendors) {
                const labourSelect = $("#labour");
                const currentVal = labourSelect.val();
                labourSelect.empty();
                vendors.forEach(function (vendor) {
                    labourSelect.append(`<option value="${vendor.id}" ${vendor.id == currentVal ? 'selected' : ''}>${vendor.name}</option>`);
                });
                if (vendors.length === 1 && !currentVal) {
                    labourSelect.val(vendors[0].id).trigger('change');
                }
                labourSelect.trigger('change.select2');
                validateLabourSlab();
            },
            error: function (error) {
                console.error('Error fetching vendors by locations:', error);
            }
        });
    }

    $(document).on("input change", ".no_of_bags, #standard_labour_rate, .qty, #labour", function () {
        calculateLabourAmount();
    });

    $("form").on("submit", function (e) {
        calculateLabourAmount();
        if (!validateLabourSlab()) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Labour Rate Slab Not Found',
                text: 'No matching labour rate slab was found for the selected Factory, Category and Bag Packing. Labour Amount cannot be 0.',
            });
            return false;
        }
    });
</script>