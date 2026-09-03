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

<form action="{{ route('sales.sale-order.store') }}" method="POST" id="ajaxSubmit2" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-order.list') }}" />
    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">SO No:</label>
                        <input type="text" name="reference_no" id="reference_no" value="{{ $sale_order->reference_no }}"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" name="order_date" id="order_date" value="{{ $sale_order->order_date }}"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inquiry No:</label>
                        <select name="inquiry_id" id="inquiry_id" onchange="get_inquiry_data()"
                            class="form-control select2" disabled>
                            <option value="">Select Inquiry</option>
                            @foreach ($inquiries ?? [] as $inquiry)
                                <option value="{{ $inquiry->id }}" @selected($inquiry->id == $sale_order->inquiry_id)>
                                    {{ $inquiry->inquiry_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contract Type:</label>
                        <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                            <option value="">Select Contract Type</option>
                            <option value="pohanch" @selected(strtolower($sale_order->sauda_type) == 'pohanch')>Pohanch
                            </option>
                            <option value="x-mill" @selected(strtolower($sale_order->sauda_type) == 'x-mill')>X-mill
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date" value="{{ $sale_order->delivery_date }}"
                            id="delivery_date" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="so_reference_no" id="so_reference_no"
                            value="{{ $sale_order->so_reference_no }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Transporter used:</label>
                        <input type="text" name="transporter_used" id="transporter_used"
                            value="{{ ucfirst($sale_order->transporter_used) }}" class="form-control" readonly>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Customer Details</h6>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <input type="text" value="{{ get_customer_name($sale_order->customer_id) }}"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Sell By:</label>
                        <input type="text" class="form-control" value="{{ $sale_order->parent_user->name ?? 'N/A' }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Contact Person:</label>
                        <input type="text" name="contact_person" id="contact_person"
                            value="{{ $sale_order->contact_person }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Token Money:</label>
                        <input type="number" name="token_money" id="token_money" value="{{ $sale_order->token_money }}"
                            class="form-control" step="0.01" min="0" readonly>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Payment Details</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Pay Type:</label>
                        <input type="text" value="{{ $sale_order->pay_type?->name ?? 'N/A' }}" class="form-control"
                            readonly>
                    </div>
                </div>
                @if($sale_order->payment_term_id)
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Payment Terms:</label>
                            <input type="text" value="{{ get_payment_term($sale_order->payment_term_id)?->desc ?? '' }}"
                                class="form-control" readonly>
                        </div>
                    </div>
                @endif

                @if($sale_order->pay_type_id != 8 && $sale_order->pay_type_id)
                <div class="col-md-6">
                    <div class="form-group mt-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="payment_on_kaanta" name="payment_on_kaanta" value="1" @checked($sale_order->payment_on_kaanta) disabled>
                            <label class="custom-control-label font-weight-bold" for="payment_on_kaanta">Payment on Kaanta</label>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Location Details</h6>
                </div>
                @php
                    $selectedFactories = $sale_order->factories?->pluck('arrival_location_id')->toArray() ?? [];
                    if (empty($selectedFactories) && $sale_order->arrival_location_id) {
                        $selectedFactories = [$sale_order->arrival_location_id];
                    }
                    $selectedSections = $sale_order->sections?->pluck('arrival_sub_location_id')->toArray() ?? [];
                    if (empty($selectedSections) && $sale_order->arrival_sub_location_id) {
                        $selectedSections = [$sale_order->arrival_sub_location_id];
                    }
                @endphp
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="locations[]" id="locations" class="form-control select2" multiple disabled>
                            @foreach(get_locations() as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, $sale_order->locations->pluck("location_id")->toArray()))>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_location_id[]" id="arrival_location_id" class="form-control select2"
                            multiple disabled>
                            @foreach($arrivalLocations as $factory)
                                <option value="{{ $factory->id }}" data-company="{{ $factory->company_location_id ?? '' }}"
                                    @selected(in_array($factory->id, $selectedFactories))>{{ $factory->name }}
                                    ({{ $factory->companyLocation->name }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        @php
                            $selectedFactoryNames = [];
                            if (!empty($selectedFactories)) {
                                $selectedFactoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', $selectedFactories)->pluck('name')->toArray();
                            }
                            $factoryNamesString = !empty($selectedFactoryNames) ? ' (' . implode(', ', $selectedFactoryNames) . ')' : '';
                        @endphp
                        <label class="form-label">Section{{ $factoryNamesString }}:</label>
                        <select name="arrival_sub_location_id[]" id="arrival_sub_location_id"
                            class="form-control select2" multiple disabled>
                            @foreach($arrivalSubLocations as $section)
                                <option value="{{ $section->id }}" data-factory="{{ $section->arrival_location_id }}"
                                    @selected(in_array($section->id, $selectedSections))>{{ $section->name }}
                                    ({{ $section->arrivalLocation->name }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3"
                            readonly>{{ $sale_order->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Token Money:</label>
                <input type="text" value="{{ $sale_order->token_money ?? 'N/A' }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-8">
            <div class="form-group">
                <label class="form-label">Remarks:</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="2"
                    readonly>{{ $sale_order->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <!-- <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div> -->

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Item</th>
                            <th style="min-width: 150px;">Bag Type</th>
                            <th style="min-width: 120px;">Packing</th>
                            <th style="min-width: 120px;">No of Bags</th>
                            <th style="min-width: 150px;">Minimum Qty (kg)</th>
                            <th style="min-width: 150px;">Maximum Qty (kg)</th>
                            <th style="min-width: 150px;">Rate per Kg</th>
                            <th style="min-width: 150px;">Rate per Mond</th>
                            <th style="min-width: 150px;">Amount</th>
                            <th style="min-width: 180px;">Brand</th>
                            <th style="display: none;">Pack Size</th>
                            <th style="min-width: 200px;">Description</th>
                            <th style="min-width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        @foreach($sale_order->sales_order_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <select name="item_id[]" id="item_id_{{ $index }}" class="form-control select2"
                                        readonly>
                                        <option value="">Select Item</option>
                                        @foreach ($items ?? [] as $item)
                                            <option value="{{ $item->id }}" @selected($data->item_id == $item->id)>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="bag_type[]" id="bag_type_{{ $index }}"
                                        value="{{ bag_type_name($data->bag_type) }}" onkeyup="calc(this)"
                                        class="form-control qty" step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="bag_size[]" id="bag_type_{{ $index }}"
                                        value="{{ $data->bag_size }}" onkeyup="calc(this)" class="form-control qty"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ $data->no_of_bags }}" class="form-control no_of_bags" readonly>
                                </td>
                                {{-- <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}" value="{{ round($data->qty) }}"
                                        class="form-control qty" step="0.01" min="0" readonly>
                                </td> --}}
                                <td>
                                    <input type="number" name="minimum_qty[]" id="minimum_qty_{{ $index }}"
                                        value="{{ round($data->minimum_qty) }}" class="form-control minimum_qty"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}"
                                        value="{{ $data->qty }}" class="form-control qty" step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="number" name="rate[]" id="rate_{{ $index }}" value="{{ $data->rate }}"
                                        onkeyup="calc(this)" class="form-control rate rate_per_kg" step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="number" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}"
                                        value="{{ $data->rate_per_mond }}" onkeyup="calc(this)" class="form-control rate rate_per_mond"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ round($data->rate * $data->qty) }}" class="form-control amount" readonly>
                                </td>

                                <td>
                                    <input type="text" name="brand_id[]" id="brand_id{{ $index }}"
                                        value="{{ getBrandById($data->brand_id)?->name }}" class="form-control brand_id"
                                        readonly>
                                </td>

                                <td style="display: none;">
                                    <input type="text" name="pack_size[]" value="0" id="pack_size{{ $index }}"
                                        value="{{ $data->pack_size }}" class="form-control pack_size" readonly>
                                </td>

                                <td>
                                    <input type="text" name="description[]" id="description{{ $index }}"
                                        value="{{ $data->description }}" class="form-control description" readonly>
                                </td>

                                <td>
                                    <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn"
                                        style="width:60px;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>





         <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Broker Details</h6>
                </div>
                        <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Broker:</label>
                        <input type="text" value="{{ $sale_order->broker->name ?? 'N/A' }}" class="form-control"
                            readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission RS per KG:</label>
                        <input type="number" name="commission_per_kg" id="commission_per_kg" class="form-control" step="0.0001" min="0" value="{{ $sale_order->commission_per_kg ?? 0 }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission in % per KG:</label>
                        @php
                            $firstRate = floatval($sale_order->sales_order_data->first()?->rate ?? 0);
                            $commRs = floatval($sale_order->commission_per_kg ?? 0);
                            $initialPercent = ($firstRate > 0) ? number_format(($commRs / $firstRate) * 100, 2, '.', '') : '0';
                        @endphp
                        <input type="number" name="commission_percent_per_kg" id="commission_percent_per_kg"
                            class="form-control" step="0.01" min="0" value="{{ $initialPercent }}" readonly>
                    </div>
                </div>
    </div>


    <input type="hidden" id="rowCount" value="0">


    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            {{-- <button type="submit" class="btn btn-primary submitbutton">Save</button> --}}
        </div>
    </div>
</form>
@php
    $soModule = $sale_order->getApprovalModule();
    $soApprovalLogs = $soModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $sale_order->id)->where('module_id', $soModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$sale_order" :list-refresh="route('sales.get.sales-order.list')" />
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

@if ($soApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $soApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $soApprovalLogs->count()) }}</span>
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
                            @foreach ($soApprovalLogs as $index => $log)
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
    salesInquiryRowIndex = 1;

    $(document).ready(function () {
        $('.select2').select2();
        calculateCommissionFromRs();
    });

    // Run immediately and on slight delay for AJAX modal injection
    calculateCommissionFromRs();
    setTimeout(calculateCommissionFromRs, 150);

    function calculateCommissionFromRs() {
        let commissionInRs = parseFloat($('#commission_per_kg').val()) || 0;
        const ratePerKg = getFirstItemRate();

        if (ratePerKg > 0) {
            let percent = (commissionInRs / ratePerKg) * 100;
            if (percent > 100) {
                percent = 100;
                commissionInRs = (100 / 100) * ratePerKg;
                $('#commission_per_kg').val(commissionInRs.toFixed(4));
            }
            $('#commission_percent_per_kg').val(percent.toFixed(2));
        } else {
            $('#commission_percent_per_kg').val('0');
        }
    }

    function getFirstItemRate() {
        let rate = $('#salesInquiryBody tr:first input[name="rate[]"]').val();
        if (!rate || parseFloat(rate) === 0) {
            rate = '{{ $firstRate }}';
        }
        return parseFloat(rate) || 0;
    }

    function addRow() {
        let index = salesInquiryRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td>
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="qty[]" id="qty_${index}" onkeyup="calc(this)" class="form-control qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="minimum_qty[]" id="minimum_qty_${index}" class="form-control minimum_qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calc(this)" class="form-control rate" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#salesInquiryBody').append(row);
        $(`#item_id_${index}`).select2();
        $('#row_0 .removeRowBtn').prop('disabled', true);
        $('.removeRowBtn').not('#row_0 .removeRowBtn').prop('disabled', false);
    }

    function removeRow(index) {
        $('#row_' + index).remove();
        if ($('#salesInquiryBody tr').length === 1) {
            $('#row_0 .removeRowBtn').prop('disabled', true);
        }
    }

    function calc(el) {
        const element = $(el).closest("tr");

        const rate = parseFloat($(element).find(".rate").val()) || 0;
        const qty = parseFloat($(element).find(".qty").val()) || 0;

        const amount = $(element).find(".amount");

        amount.val(rate * qty);
    }

    function get_inquiries() {
        const customer_id = $("#customer_id").val();
        // get-sale-inquiries-against-customer

        $.ajax({
            url: "{{ route('sales.get-sale-inquiries-against-customer') }}",
            method: "GET",
            data: {
                customer_id: customer_id
            },
            dataType: "json",
            success: function (res) {
                $("#inquiry_id").select2({
                    data: res
                });
            },
            error: function (error) {

            }
        });

        // get-sale-inquiry-data
    }

    function get_inquiry_data() {
        const inquiry_id = $("#inquiry_id").val();

        $.ajax({
            url: "{{ route('sales.get-sale-inquiry-data') }}",
            method: "GET",
            data: {
                inquiry_id: inquiry_id
            },
            dataType: "html",
            success: function (res) {
                console.log("success");
                $("#alesInquiryBody").empty();
                $("#salesInquiryBody").html(res);
            },
            error: function (error) {
                console.log(error);
            }
        });

    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-order.getnumber') }}",
            method: "GET",
            data: {
                contract_date: $("#delivery_date").val()
            },
            dataType: "json",
            success: function (res) {
                $("#reference_no").val(res.so_no)
            },
            error: function (error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>