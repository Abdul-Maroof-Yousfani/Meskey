@php
    $param = isset($isRequestApprovalPage) && $isRequestApprovalPage ? 'readonly' : '';
    //  $param0 = isset($isRequestApprovalPage) && $isRequestApprovalPage ? 'disabled' : '';
    $param0 = (isset($paymentRequestData) && $paymentRequestData->payment_to && !isset($isCreateFlow)) ? 'disabled' : '';
    $parampenalty_adjust_to = isset($paymentRequestData) && $paymentRequestData->penalty_adjust_to ? 'disabled' : '';
    $paramlabour_vendor_id = isset($paymentRequestData) && $paymentRequestData->labour_vendor_id ? 'disabled' : '';
    $param0 = (isset($paymentRequestData) && $paymentRequestData->payment_to && !isset($isCreateFlow)) ? 'disabled' : '';
    $paymentRequest = isset($paymentRequest) ? $paymentRequest : null;
    $isUpdated = isset($isUpdated) ? $isUpdated : null;
    $approval = isset($approval) ? $approval : null;

@endphp
<style>
    .hide,
    .togglehistorytable {
        display: none;
    }

    .togglehistory {
        cursor: pointer;
    }
</style>
@php

    // Use paymentRequestData if available, otherwise use existing logic
    $exempt = $paymentRequestData->exempt ?? $freightPaymentRequest?->exempt ?? ($ticket->freight->exempted_weight ?? '0');
    $freight_rs = $paymentRequestData->freight_rs ?? $freightPaymentRequest?->freight_rs ?? ($ticket->freight->freight_written_on_bilty ?? '0');
    $freight_per_ton = $paymentRequestData->freight_per_ton ?? $freightPaymentRequest?->freight_per_ton ?? ($ticket->freight->freight_per_ton ?? '0');
    $loading_kanta = $paymentRequestData->loading_kanta ?? $freightPaymentRequest?->loading_kanta ?? ($ticket->freight->kanta_golarchi_charges ?? '0');
    $arrived_kanta = $paymentRequestData->arrived_kanta ?? $freightPaymentRequest?->arrived_kanta ?? ($ticket->freight->karachi_kanta_charges ?? '0');
    $other_plus_labour = $paymentRequestData->other_plus_labour ?? $freightPaymentRequest?->other_plus_labour ?? ($ticket->freight->other_labour_charges ?? '0');
    $dehari_plus_extra = $paymentRequestData->dehari_plus_extra ?? $freightPaymentRequest?->dehari_plus_extra ?? ($ticket->freight->other_labour_charges ?? '0');
    $market_comm = $paymentRequestData->market_comm ?? $freightPaymentRequest?->market_comm ?? 0;
    $over_weight_ded = $paymentRequestData->over_weight_ded ?? $freightPaymentRequest?->over_weight_ded ?? 0;
    $godown_penalty = $paymentRequestData->godown_penalty ?? $ticket->freight?->other_deduction ?? 0;
    $other_minus_labour = $paymentRequestData->other_minus_labour ?? $freightPaymentRequest?->other_minus_labour ?? ($ticket->freight->unpaid_labor_charges ?? '0');
    $extra_minus_ded = $paymentRequestData->extra_minus_ded ?? $freightPaymentRequest?->extra_minus_ded ?? 0;
    $commission_percent_ded = $paymentRequestData->commission_percent_ded ?? $freightPaymentRequest?->commission_percent_ded ?? 0;
    $commission_amount = $paymentRequestData->commission_amount ?? $freightPaymentRequest?->commission_amount ?? 0;
    $weightDifference = ($ticket->arrived_net_weight ?? 0) - ($ticket->net_weight ?? 0);

@endphp
@php
    $hasContract = isset($purchaseOrder) && !empty($purchaseOrder->id);
    $targetRoute = $isRequestApprovalPage
        ? ($hasContract ? 'raw-material.pohouch-freight-payment-request-approval' : 'raw-material.pohouch-freight-payment-request-approval-wo-contract')
        : 'raw-material.freight-request.store';
@endphp
<form action="{{ route($targetRoute) }}" method="POST" id="ajaxSubmit" class="needs-validation" novalidate>
    @csrf
    <input type="hidden" name="arrival_slip_no" value="{{ $ticket->arrivalSlip->unique_no ?? '' }}">
    <input type="hidden" name="arrival_slip_id" value="{{ $ticket->arrivalSlip->id ?? '' }}">
    <input type="hidden" name="purchase_order_id" value="{{ $ticket->arrival_purchase_order_id ?? '' }}">
    <input type="hidden" name="ticket_id" value="{{ $ticket->id ?? '' }}">
    <input type="hidden" name="arrival_ticket_id" value="{{ $ticket->id ?? '' }}">
    <input type="hidden" name="ticket_type" value="{{ $ticketType ?? '' }}">
    <input type="hidden" name="payment_request_id" value="{{ $paymentRequest?->id ?? null }}">
    <input type="hidden" name="is_without_contract" value="{{ !$ticket->purchaseOrder?->contract_no }}">

    @if (isset($isRequestApprovalPage) && $isRequestApprovalPage)
        <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request-approval') }}" />
    @else
        <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.freight-request') }}" />
    @endif

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Information
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Ticket #</label>
                <input type="text" class="form-control bg-light" value="#{{ $ticket->unique_no }}" readonly
                    placeholder="Ticket #">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->created_at->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Truck No.</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->truck_no ?? ($ticket->purchaseFreight->truck_no ?? 'N/A') }}" readonly
                    placeholder="Truck No.">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Bill/T</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->bilty_no ?? 'N/A' }}" readonly
                    placeholder="Bill/T">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">GRN No.</label>
                <input type="text" class="form-control bg-light" value="#{{ $ticket->arrivalSlip->unique_no ?? 'N/A' }}"
                    readonly placeholder="GRN No.">
            </div>
        </div>
        @if($ticket->purchaseOrder?->contract_no)
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Contract No.</label>
                    <input type="text" class="form-control bg-light"
                        value="{{ $ticket->purchaseOrder->contract_no ?? 'N/A' }}" readonly placeholder="Contract No.">
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Party Details
            </h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Supplier Name</label>
                @if (isset($isCreateFlow))
                    <select class="form-control select2" name="supplier_name" required>
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->name }}" @selected((isset($ticket->accountsOf->name) && $ticket->accountsOf->name == $supplier->name) || (isset($ticket->accounts_of_name) && $ticket->accounts_of_name == $supplier->name))>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control bg-light" name="supplier_name"
                        value="{{ $paymentRequestData->supplier_name ?? ($ticket->accountsOf->name ?? ($ticket->accounts_of_name ?? 'N/A')) }}"
                        readonly placeholder="Supplier Name">
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Broker Name</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->broker_name ?? ($ticket->broker->name ?? ($ticket->purchaseOrder->broker->name ?? 'N/A')) }}"
                    readonly placeholder="Broker Name">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Freight Party</label>
                <select class="form-control editable-field select2" name="vendor_id" @disabled($param0)>
                    <option value="">Select Freight Party</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((isset($paymentRequestData) && $paymentRequestData->payment_to == $vendor->id) || (isset($suggested_vendor_id) && $suggested_vendor_id == $vendor->id))>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                @if (isset($isRequestApprovalPage, $paymentRequestData->payment_to))
                    <input type="hidden" name="vendor_id" value="{{ $paymentRequestData->payment_to }}" readonly>
                @endif
            </div>
        </div>
    </div>

    @php
        $has_pendings = isset($paymentRequests)
            ? $paymentRequests->where("module_type", "freight_payment")->whereIn('status', ['pending', 'approved'])->count()
            : 0;
    @endphp
    @if($ticket->saudaType?->name == 'Pohanch')
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" onchange="isPaidBySupplier()" class="custom-control-input"
                            id="paid_by_supplier" name="is_paid_by_supplier" @checked(isset($paymentRequestData) && $paymentRequestData->is_paid_by_supplier == 1) @disabled($has_pendings > 0) value="1">
                        <label class="custom-control-label font-weight-bold" for="paid_by_supplier">Paid By Supplier</label>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(isset($paymentRequests) && count($paymentRequests) != 0)
        <div class="row">
            <div class="col-md-12">
                <h6 class="header-heading-sepration togglehistory">
                    Request History ({{ count($paymentRequests) }})
                </h6>
                <table class="table m-0 togglehistorytable">
                    <thead>
                        <tr>
                            <th>Request Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paymentRequests as $paymentRequest)
                            <tr>
                                <td>{{ $paymentRequest->created_at }}</td>
                                <td>
                                    @if($paymentRequest->is_without_contract)
                                        <span class="badge badge-danger">Without Contract</span>
                                    @else
                                        <span class="badge badge-success">With Contract</span>
                                    @endif
                                </td>
                                <td>{{ number_format($paymentRequest->amount, 2) }}</td>
                                <td>{{ $paymentRequest->approval->remarks ?? 'N/A' }}</td>
                                <td>
                                    @if($paymentRequest->status == 'pending')
                                        <label class="badge badge-warning">Pending</label>
                                    @elseif($paymentRequest->status == 'approved')
                                        <label class="badge badge-success">Approved</label>
                                    @elseif($paymentRequest->status == 'rejected')
                                        <label class="badge badge-danger">Rejected</label>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif


    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Commodity Details
            </h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Commodity</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->product->name ?? ($ticket->qcProduct->name ?? 'N/A') }}" readonly
                    placeholder="Commodity">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Godown</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->location->name ?? 'N/A' }}" readonly
                    placeholder="Godown">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Station</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->station_name ?? ($ticket->station->name ?? ($ticket->purchaseOrder->station_name ?? 'N/A')) }}"
                    readonly placeholder="Station">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Sauda Term</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->saudaType->name ?? ($ticket->purchaseOrder->saudaType->name ?? 'N/A') }}"
                    readonly placeholder="Sauda Term">
            </div>
        </div>
        @if($ticket->purchaseOrder?->contract_no)
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Contract Rate</label>
                    <input type="text" class="form-control editable-field contract-rate" name="contract_rate"
                        value="{{ $ticket->purchaseOrder->rate_per_kg ?? '0' }}" placeholder="Contract Rate" readonly>
                </div>
            </div>
        @endif
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Deduction Rate</label>
                <input type="text" class="form-control editable-field deduction-rate"
                    name="deduction_contract_rate_for_freight" id="deduction_rate" {{ $isRequestApprovalPage ? 'readonly' : '' }}
                    value="{{ $paymentRequestData->deduction_contract_rate_for_freight ?? ($ticket->freight->po_rate ?? ($ticket->purchaseOrder->rate_per_kg ?? '0')) }}"
                    placeholder="Contract Rate">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Information
            </h6>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Loading Weight</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->net_weight ?? '0' }}" readonly
                    placeholder="Loading Weight">
            </div>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Arrival Weight</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->arrived_net_weight }}" readonly
                    placeholder="Arrival Weight">
            </div>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Difference Weight</label>
                <input type="text" class="form-control bg-light" id="differenceWeight"
                    value="{{ ($ticket->arrived_net_weight ?? 0) - ($ticket->net_weight ?? 0) }}" readonly
                    placeholder="Difference Weight">
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label class="font-weight-bold">Exempt</label>
                <input type="text" class="form-control editable-field" name="exempt" id="exemptedWeight"
                    value="{{ $exempt }}" {{ $param }} {{ $weightDifference > 0 ? 'readonly' : '' }}
                    placeholder="Exempt">
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label class="form-label">Net Shortage</label>
                <input type="number" class="form-control bg-light" name="net_shortage" id="netShortage"
                    value="{{$weightDifference - $exempt }}" readonly>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Freight & Charges
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight (Rs)</label>
                <input type="text" class="form-control editable-field freight-rs" name="freight_rs"
                    value="{{ $freight_rs }}" {{ $param }} placeholder="Freight (Rs)">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight Per Ton</label>
                <input type="text" class="form-control editable-field freight-per-ton" name="freight_per_ton"
                    value="{{ $freight_per_ton }}" {{ $param }} placeholder="Freight Per Ton">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Loading Kanta</label>
                <input type="text" class="form-control editable-field" name="loading_kanta" value="{{ $loading_kanta }}"
                    {{ $param }} placeholder="Loading Kanta">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Arrived Kanta</label>
                <input type="text" class="form-control editable-field" name="arrived_kanta" value="{{ $arrived_kanta }}"
                    {{ $param }} placeholder="Arrived Kanta">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Additions
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other(+)/Labour</label>
                <input type="text" class="form-control editable-field" name="other_plus_labour"
                    value="{{ $other_plus_labour }}" {{ $param }} placeholder="Other(+)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Dehari(+)/Extra</label>
                <input type="text" class="form-control editable-field" name="dehari_plus_extra"
                    value="{{ $dehari_plus_extra }}" {{ $param }} placeholder="Dehari(+)/Extra">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Market Comm</label>
                <input type="text" class="form-control editable-field" name="market_comm" value="{{ $market_comm }}" {{ $param }} placeholder="Market Comm">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Deductions
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Short Weight Ded</label>
                <input type="text" class="form-control  bg-light" name="over_weight_ded" id="over_weight_ded"
                    value="{{ $over_weight_ded }}" {{ $param }} placeholder="Over Weight Ded" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Godown Penalty</label>
                <input type="text" class="form-control editable-field" name="godown_penalty"
                    value="{{ $godown_penalty }}" {{ $param }} placeholder="Godown Penalty">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other(-)/Labour</label>
                <input type="text" class="form-control editable-field" name="other_minus_labour"
                    value="{{ $other_minus_labour }}" {{ $param }} placeholder="Other(-)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Extra(-) Ded</label>
                <input type="text" class="form-control editable-field" name="extra_minus_ded"
                    value="{{ $extra_minus_ded }}" {{ $param }} placeholder="Extra(-) Ded">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Commission % Ded</label>
                <input type="text" class="form-control editable-field commission-percent percentage-input-field"
                    name="commission_percent_ded" value="{{ $commission_percent_ded }}" {{ $param }}
                    placeholder="Commission % Ded">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Commission Amount</label>
                <input type="text" class="form-control bg-light commission-amount" name="commission_amount"
                    value="{{ $commission_amount }}" readonly placeholder="Commission Amount">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Sub Total
            </h6>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Total Deductions</label>
                <input type="text" class="form-control bg-light" name="total_deductions" value="" readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Gross Amount</label>
                <input type="text" data-toggle="tooltip" title="Gross = Total Freight + Additions - Total Deduction"
                    class="form-control bg-light" name="gross_amount" value="0" readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Penalty</label>
                <input type="text" class="form-control bg-light" name="penalty" value="0" readonly>
            </div>
            @if (isset($isRequestApprovalPage) && $isRequestApprovalPage && $godown_penalty > 0)

                <div class="form-group">
                    <label class="font-weight-bold">Penalty Adjust To</label>
                    <select name="penalty_adjust_to" class="form-control select2" @disabled($parampenalty_adjust_to)
                        required>
                        <option value="">Select Penalty Adjust To</option>
                        <option {{ getAccountDetailsByHierarchyPath('4-1-2')->id == $paymentRequestData->penalty_adjust_to ? 'selected' : '' }} value="{{ getAccountDetailsByHierarchyPath('4-1-2')->id }}">
                            {{ getAccountDetailsByHierarchyPath('4-1-2')->name }}
                        </option>
                        <option {{ getAccountDetailsByHierarchyPath('5-1-1')->id == $paymentRequestData->penalty_adjust_to ? 'selected' : '' }} value="{{ getAccountDetailsByHierarchyPath('5-1-1')->id }}">
                            {{ getAccountDetailsByHierarchyPath('5-1-1')->name }}
                        </option>
                    </select>
                </div>
            @endif
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour</label>
                <input type="text" class="form-control bg-light" name="total_labour" value="" readonly>
            </div>
            @if (isset($isRequestApprovalPage) && $isRequestApprovalPage && $other_minus_labour > 0)
                <div class="form-group">
                    <label class="font-weight-bold">Labour Party</label>
                    <select class="form-control editable-field select2" required name="labour_vendor_id"
                        @disabled($paramlabour_vendor_id)>
                        <option value="">Select Labour Party</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(isset($paymentRequestData) && $paymentRequestData->labour_vendor_id == $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                    @if (isset($isRequestApprovalPage, $paymentRequestData->labour_vendor_id))
                        <input type="hidden" name="labour_vendor_id" value="{{ $paymentRequestData->labour_vendor_id }}"
                            readonly>
                    @endif
                </div>
            @endif
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Total Commision</label>
                <input type="text" class="form-control bg-light total_commision" name="total_commision" value=""
                    readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="font-weight-bold">Net Amount</label>
                <input data-toggle="tooltip" title="Gross - Penalty - Commision - Labour = Payable of Freight Vendor"
                    type="text" class="form-control bg-light font-weight-bold" name="net_amount"
                    value="{{ $paymentRequestData->net_amount ?? '0' }}" readonly>
            </div>
        </div>

    </div>

    <div
        class="row @if($paymentRequestData?->is_paid_by_supplier && isset($isRequestApprovalPage) && $isRequestApprovalPage) hide @endif">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Payment Summary
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Requested Amount</label>
                <input type="number" step="0.01" class="form-control bg-light" name="paid_amount"
                    value="{{ $requestedAmount ?? 0 }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Remaining Amount</label>
                <input type="number" step="0.01" class="form-control bg-light" name="remaining_amount" value="0"
                    readonly>
            </div>
        </div>

        @if (isset($isRequestApprovalPage) && !$isRequestApprovalPage)
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Percentage</label>
                    <input type="number" min="0" max="100" step="0.01" class="form-control percentage-input"
                        value="{{ isset($isCreateFlow) && $isCreateFlow ? 100 : 0 }}" placeholder="Enter percentage">
                </div>
            </div>
        @endif
        @if (isset($isRequestApprovalPage) && !$isRequestApprovalPage)
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Request Amount</label>
                    <input type="number" step="0.01" class="form-control" name="request_amount" value="0" min="0" required>
                </div>
            </div>
        @endif
    </div>

    @if (isset($isRequestApprovalPage) && $isRequestApprovalPage)
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Payment Request Approval
                </h6>
            </div>
        </div>

        <div class="row">
            <div class="{{ 'col-md-6' }} @if($paymentRequestData?->is_paid_by_supplier) hide @endif">
                <div class="form-group">
                    <label>Contract No</label>
                    <input type="text" class="form-control "
                        value="#{{ $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="{{ 'col-md-6' }} @if($paymentRequestData?->is_paid_by_supplier) hide @endif">
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" class="form-control"
                        value="{{ $paymentRequest->paymentRequestData->supplier_name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3 @if($paymentRequestData?->is_paid_by_supplier) hide @endif">
                <div class="form-group">
                    <label>Request Type</label>
                    <input type="text" class="form-control"
                        value="{{ isset($paymentRequest) ? formatEnumValue($paymentRequest->request_type) : '' }}" readonly>
                </div>
            </div>
            <div class="col-md-3 @if($paymentRequestData?->is_paid_by_supplier) hide @endif">
                <div class="form-group">
                    <label>Original Amount</label>
                    <input type="text" class="form-control" name="payment_request_amount" readonly
                        value="{{ isset($paymentRequest) ? $paymentRequest->amount : '' }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" id="approvalStatus" class="form-control select2" {{ $isUpdated ? 'disabled' : '' }}>
                        <option value="">Select Status</option>
                        <option value="approved" {{ $approval && $approval->status == 'approved' ? 'selected' : '' }}>
                            Approved</option>
                        <option value="rejected" {{ $approval && $approval->status == 'rejected' ? 'selected' : '' }}>
                            Rejected</option>
                    </select>
                    @if ($isUpdated)
                        <input type="hidden" name="status" value="{{ $approval->status ?? '' }}">
                    @endif
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea id="approvalRemarks" name="remarks" class="form-control" rows="3" {{ $isUpdated ? 'readonly' : '' }}>{{ $approval->remarks ?? '' }}</textarea>
                </div>
            </div>
        </div>
    @endif

    @php
        $is_pending = isset($isRequestApprovalPage) && $isRequestApprovalPage && $paymentRequest && $paymentRequest->status == "pending";
       
    @endphp

    @if ($is_pending || !isset($isRequestApprovalPage) || !$isRequestApprovalPage)
        <div class="row bottom-button-bar">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
            </div>
        </div>
    @endif
</form>
<script>


    $(document).ready(function () {
        window.isPaidBySupplier = function () {
            const isChecked = $('#paid_by_supplier').is(':checked');
            const percentageInput = $('.percentage-input');
            const requestAmountInput = $('[name="request_amount"]');

            if (isChecked) {
                percentageInput.val(100).prop('readonly', true).addClass('bg-light');
                requestAmountInput.prop('readonly', true).addClass('bg-light');
                // Force a calculation update
                if (typeof CalPercentageINput === 'function') {
                    CalPercentageINput();
                }
            } else {
                percentageInput.prop('readonly', false).removeClass('bg-light');
                requestAmountInput.prop('readonly', false).removeClass('bg-light');
            }
        };

        // Initialize state
        isPaidBySupplier();

        let canSubmitt = true;
        function updateButtonVisibility() {
            // Check the balance available (Net - Already Paid)
            const netAmount = parseFloat($('[name="net_amount"]').val()) || 0;
            const paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            const availableBalance = netAmount - paidAmount;

            if (availableBalance > 0) {
                $('.bottom-button-bar').show();
                canSubmitt = true;
            } else {
                $('.bottom-button-bar').hide();
                canSubmitt = false;
            }
        }

        $('#ajaxSubmit').on('keydown', function (e) {
            if (e.keyCode === 13) {
                if (!canSubmitt) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        calculateNetShortageDeduction()
        $('.editable-field').on('input', calculatePaymentSummary);

        // Freight calculations
        $('.freight-rs').on('input', function () {
            calculateFreightFromRs();
            calculateCommission();
        });

        $('.freight-per-ton').on('input', function () {
            calculateFreightFromTon();
            calculateCommission();
        });

        // Commission calculation
        $('.commission-percent').on('input', calculateCommission);

        function calculateNetShortagebkl() {
            const differenceWeight = Math.abs(parseFloat(document.getElementById('differenceWeight').value)) || 0;
            const exemptedWeight = parseFloat(document.getElementById('exemptedWeight').value) || 0;
            const netShortage = differenceWeight - exemptedWeight;
            document.getElementById('netShortage').value = netShortage > 0 ? netShortage : 0;
        }


        function calculateNetShortage() {
            const differenceWeight = parseFloat(document.getElementById('differenceWeight').value) || 0;
            const exemptedWeight = parseFloat(document.getElementById('exemptedWeight').value) || 0;

            let netShortage;

            if (differenceWeight < 0) {
                // agar differenceWeight minus me hai
                netShortage = Math.abs(differenceWeight) - exemptedWeight;
            } else {
                // agar differenceWeight plus me hai
                netShortage = 0;
            }

            document.getElementById('netShortage').value = netShortage;
        }

        function calculateNetShortageDeduction() {
            const netShortage = parseFloat(document.getElementById('netShortage').value) || 0;
            const deductionRate = parseFloat(document.getElementById('deduction_rate').value) || 0;

            // Calculate and round properly to 2 decimals
            const netDeduction = (deductionRate * netShortage).toFixed(2);

            document.getElementById('over_weight_ded').value = netDeduction;
        }





        function calculateFreightFromRs() {
            const freightRs = parseFloat($('.freight-rs').val()) || 0;
            const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;

            if (loadingWeight > 0) {
                const freightPerTon = (freightRs / loadingWeight) * 1000;
                $('.freight-per-ton').val(freightPerTon.toFixed(2));
            }

            calculatePaymentSummary();
        }

        function calculateFreightFromTon() {
            const freightPerTon = parseFloat($('.freight-per-ton').val()) || 0;
            const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;

            if (loadingWeight > 0) {
                const freightRs = (freightPerTon * loadingWeight) / 1000;
                $('.freight-rs').val(freightRs.toFixed(2));
            }

            calculatePaymentSummary();
        }

        function calculateCommission() {




            calculatePaymentSummary();
        }

        function calculatePaymentSummary() {

            let freightRs = parseFloat($('[name="freight_rs"]').val()) || 0;
            let loadingKanta = parseFloat($('[name="loading_kanta"]').val()) || 0;
            let arrivedKanta = parseFloat($('[name="arrived_kanta"]').val()) || 0;
            let otherPlusLabour = parseFloat($('[name="other_plus_labour"]').val()) || 0;
            let dehariPlusExtra = parseFloat($('[name="dehari_plus_extra"]').val()) || 0;
            let marketComm = parseFloat($('[name="market_comm"]').val()) || 0;

            let overWeightDed = parseFloat($('[name="over_weight_ded"]').val()) || 0;
            let godownPenalty = parseFloat($('[name="godown_penalty"]').val()) || 0;
            let otherMinusLabour = parseFloat($('[name="other_minus_labour"]').val()) || 0;
            let extraMinusDed = parseFloat($('[name="extra_minus_ded"]').val()) || 0;
            let commissionAmount = parseFloat($('[name="commission_amount"]').val()) || 0;
            let totalComission = parseFloat($('[name="total_commision"]').val()) || 0;

            let netShortage = parseFloat(document.getElementById('netShortage').value) || 0;


            // let totalDeductions = overWeightDed + godownPenalty + otherMinusLabour + extraMinusDed + commissionAmount + netShortageDeduction;
            let totalDeductions =
                parseFloat(overWeightDed || 0) +
                //  parseFloat(otherMinusLabour || 0) +
                parseFloat(extraMinusDed || 0);
            //  parseFloat(commissionAmount || 0);

            let grossAmount = freightRs + loadingKanta + arrivedKanta + otherPlusLabour + dehariPlusExtra + marketComm - totalDeductions;

            let totalLabour = parseFloat(otherMinusLabour || 0);
            let totalCommision = parseFloat(commissionAmount || 0);



            const commissionPercent = parseFloat($('.commission-percent').val()) || 0;
            //  const freightRs = parseFloat($('.freight-rs').val()) || 0;
            let grossAfterDeductAmount = grossAmount - godownPenalty - totalLabour;

            if (commissionPercent > 0) {
                const commissionAmount = (grossAfterDeductAmount * commissionPercent) / 100;
                $('.commission-amount').val(commissionAmount.toFixed(2));
                $(".total_commision").val(commissionAmount.toFixed(2));

            } else {
                $('.commission-amount').val('0');
                $(".total_commision").val('0');

            }
            var commissionAmountttt = (grossAfterDeductAmount * commissionPercent) / 100;
            let netAmount = grossAmount - godownPenalty - commissionAmountttt - totalLabour;
            console.log(grossAmount, godownPenalty, commissionAmountttt, totalLabour, netAmount);

            $('[name="gross_amount"]').val(grossAmount.toFixed(2));
            $('[name="total_deductions"]').val(totalDeductions.toFixed(2));
            $('[name="total_labour"]').val(totalLabour.toFixed(2));
            // $('[name="total_commision"]').val(totalCommision.toFixed(2));
            $('[name="net_amount"]').val(netAmount.toFixed(2));
            $('[name="penalty"]').val(godownPenalty.toFixed(2));

            let paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            let requestAmount = parseFloat($('[name="request_amount"]').val()) || 0;
            let remainingAmount = netAmount - paidAmount - requestAmount;
            $('[name="remaining_amount"]').val(remainingAmount.toFixed(2));

            // Set maximum limit for request amount
            $('[name="request_amount"]').attr('max', Math.max(0, netAmount - paidAmount).toFixed(2));
            CalPercentageINput();
        }


        $('.percentage-input').on('input', function () {
            CalPercentageINput();
        });

        function CalPercentageINput() {
            let percentage = parseFloat($('.percentage-input').val()) || 0;
            if (percentage > 100) {
                percentage = 100;
                $(this).val(100);
            }

            const netAmount = parseFloat($('[name="net_amount"]').val()) || 0;
            const paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            const remainingAmount = netAmount - paidAmount;
            const amount = (remainingAmount * percentage) / 100;

            $('[name="request_amount"]').val(amount.toFixed(2));

            // Update remaining amount
            const finalRemaining = netAmount - (paidAmount + amount);
            $('[name="remaining_amount"]').val(finalRemaining.toFixed(2));
        }

        // Percentage input handler for multiple requests


        // Request amount input handler
        $('[name="request_amount"]').on('input', function () {
            const netAmount = parseFloat($('[name="net_amount"]').val()) || 0;
            const paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            const newRequested = parseFloat($(this).val()) || 0;
            const remainingAmount = netAmount - paidAmount;

            // Ensure payment request doesn't exceed remaining amount
            if (newRequested > remainingAmount) {
                $(this).val(remainingAmount.toFixed(2));
            }

            // Update percentage
            const percentageInput = $('.percentage-input');
            const finalRequested = parseFloat($(this).val()) || 0;
            const percentage = remainingAmount > 0 ? (finalRequested / remainingAmount) * 100 : 0;
            percentageInput.val(percentage.toFixed(2));

            // Update remaining amount display
            const finalRemaining = netAmount - (paidAmount + finalRequested);
            $('[name="remaining_amount"]').val(finalRemaining.toFixed(2));
        });

        document.getElementById('exemptedWeight').addEventListener('input', function () {
            calculateNetShortage();
            calculateNetShortageDeduction();
            calculatePaymentSummary();
        });
        document.getElementById('deduction_rate').addEventListener('input', function () {
            calculateNetShortageDeduction();
            calculatePaymentSummary();
        });

        // Initialize calculations on page load
        function initializeCalculations() {
            calculateNetShortage();
            calculateNetShortageDeduction();

            // Calculate freight per ton if freight Rs has value
            const initialFreightRs = parseFloat($('.freight-rs').val()) || 0;
            if (initialFreightRs > 0) {
                calculateFreightFromRs();
            } else {
                // If freight per ton has value but freight Rs doesn't, calculate from ton
                const initialFreightPerTon = parseFloat($('.freight-per-ton').val()) || 0;
                if (initialFreightPerTon > 0) {
                    calculateFreightFromTon();
                } else {
                    calculatePaymentSummary();
                }
            }

            calculateCommission();
            updateButtonVisibility();
        }

        // Request History Toggle
        $(document).on('click', '.togglehistory', function () {
            $('.togglehistorytable').toggle();
        });

        // Run initialization
        initializeCalculations();
    });
</script>