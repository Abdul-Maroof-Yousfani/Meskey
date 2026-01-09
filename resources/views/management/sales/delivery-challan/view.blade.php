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
    <!-- Left side fields (2 columns) -->
    <div class="col-md-12">
        <!-- Row 1: DC NO, Date, Contract Types -->
        <div class="row" style="margin-top: 10px">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">DC NO:</label>
                    <input type="text" value="{{ $delivery_challan->dc_no }}" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Date:</label>
                    <input type="date" value="{{ $delivery_challan->dispatch_date }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Contract Types:</label>
                <select class="form-control select2" disabled>
                    <option value="">Select Contract type</option>
                    <option value="pohanch" @selected($delivery_challan->sauda_type == 'pohanch')>Pohanch</option>
                    <option value="x-mill" @selected($delivery_challan->sauda_type == 'x-mill')>X-mill</option>
                </select>
            </div>
        </div>

        <!-- Row 2: Ticket (display only), Customer, DO Number -->
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Ticket:</label>
                @php
                    $firstTicket = $delivery_challan->delivery_challan_data->first();
                    $ticketModel = $firstTicket ? \App\Models\Sales\LoadingProgramItem::find($firstTicket->ticket_id) : null;
                    $ticketDisplay = $ticketModel ? ($ticketModel->transaction_number . ' -- ' . $ticketModel->truck_number) : 'N/A';
                @endphp
                <input type="text" class="form-control" value="{{ $ticketDisplay }}" disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label">Customer:</label>
                <select class="form-control select2" disabled>
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}" @selected($delivery_challan->customer_id == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">DO Number:</label>
                <select class="form-control select2" disabled>
                    <option value="">Select Delivery Order</option>
                    @foreach ($delivery_orders as $delivery_order)
                        <option value="{{ $delivery_order->id }}" @selected(in_array($delivery_order->id, $delivery_challan->delivery_order->pluck('id')->toArray()))>
                            {{ $delivery_order->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Row 3: Reference Number -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Reference Number:</label>
                    <input type="text" value="{{ $delivery_challan->reference_number }}" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Ticket Labour:</label>
                    @php
                        $firstTicketData = $delivery_challan->delivery_challan_data->first();
                        $ticketLabour = null;
                        if ($firstTicketData && $firstTicketData->ticket_id) {
                            $loadingSlip = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id)?->loadingSlip;
                            $ticketLabour = $loadingSlip?->labour;
                        }
                    @endphp
                    <input type="text" class="form-control" value="{{ $ticketLabour ? ($ticketLabour === 'paid' ? 'Paid' : 'Not Paid') : 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <!-- Row 4: Locations, Factory, Gala -->
        <div class="row">
            <div class="col-md-4">
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

            <div class="col-md-4">
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

            <div class="col-md-4">
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

        <!-- Row 5: Ticket Labour, Labour, Transporter -->
        <div class="row">
            

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Labour:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Labours</option>
                        <option value="1" @selected($delivery_challan->labour == 1)>Labour 1</option>
                        <option value="2" @selected($delivery_challan->labour == 2)>Labour 2</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Transporter:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Transporter</option>
                        <option value="1" @selected($delivery_challan->transporter == 1)>Transporter 1</option>
                        <option value="2" @selected($delivery_challan->transporter == 2)>Transporter 2</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">In-house Weighbridge:</label>
                    <select class="form-control select2" disabled>
                        <option value="">Select Weighbridge</option>
                        <option value="1" @selected($delivery_challan->{'inhouse-weighbridge'} == 1)>Weighbridge 1</option>
                        <option value="2" @selected($delivery_challan->{"inhouse-weighbridge"} == 2)>Weighbridge 2</option>
                    </select>
                </div>
            </div>
        </div>


        <!-- Row 6: Labour Amount, Transporter Amount, Weighbridge Amount -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Labour Amount:</label>
                    <input type="number" value="{{ $delivery_challan->labour_amount }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Transporter Amount:</label>
                    <input type="number" value="{{ $delivery_challan->transporter_amount }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Weighbridge Amount:</label>
                    <input type="number" value="{{ $delivery_challan->{"weighbridge-amount"} }}" class="form-control" readonly>
                </div>
            </div>
        </div>

        <!-- Row 7: Remarks -->
        <div class="row">
            <div class="col-md-12">
                <label class="form-label">Remarks:</label>
                <textarea class="form-control" readonly>{{ $delivery_challan->remarks }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
            <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>No of Bags</th>
                        <th>Quantity (kg)</th>
                        <th>Rate per Kg</th>
                        <th>Amount</th>
                        <th>Brand</th>
                        <th>Truck No.</th>
                        <th>Bilty No.</th>
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
                            <input type="text" value="{{ getItem($data->item_id)?->name }}"
                                class="form-control" readonly>
                        </td>
                        
                        <td>
                            <input type="text" value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}"
                                class="form-control" readonly>
                        </td>
                      
                        <td>
                            <input type="text" value="{{ $data->bag_size }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->no_of_bags }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->qty }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->rate }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->rate * ($data->qty ?? 0) }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ getBrandById($data->brand_id)?->name }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->truck_no }}" class="form-control" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $data->bilty_no }}" class="form-control" readonly>
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
        <a type="button"
            class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <x-approval-status :model="$delivery_challan" />
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
