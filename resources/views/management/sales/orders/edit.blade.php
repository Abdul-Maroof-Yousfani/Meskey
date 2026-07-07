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

<form action="{{ route('sales.sale-order.update', ['sale_order' => $sale_order->id]) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    {{ method_field('PUT') }}
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
                        <label class="form-label">Entry Date:</label>
                        <input type="date" name="order_date" id="order_date" value="{{ $sale_order->order_date }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date" value="{{ $sale_order->delivery_date }}" 
                            id="delivery_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inquiry No:</label>
                        <select name="inquiry_id" id="inquiry_id" onchange="get_inquiry_data()" class="form-control select2">
                            <option value="">Select Inquiry</option>
                            @foreach ($inquiries ?? [] as $inquiry)
                                <option value="{{ $inquiry->id }}" @selected($inquiry->id == $sale_order->inquiry_id)>{{ $inquiry->inquiry_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- @dd($sale_order) --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contract Type:</label>
                        <select @if(!$sale_order->inquiry_id) name="sauda_type" @endif id="sauda_type" class="form-control select2">
                            <option value="">Select Contract Type</option>
                            <option value="pohanch" @selected(strtolower($sale_order->sauda_type) == 'pohanch')>Pohanch</option>
                            <option value="x-mill" @selected( strtolower($sale_order->sauda_type) == 'x-mill')>X-mill</option>
                        </select>
                        <input type="hidden" @if($sale_order->inquiry_id) name="sauda_type" @endif value="{{ strtolower($sale_order->sauda_type) }}" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="so_reference_no" id="so_reference_no" value="{{ $sale_order->so_reference_no }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Transporter used:</label>
                        <select name="transporter_used" id="transporter_used" class="form-control select2">
                            <option value="no" @selected($sale_order->transporter_used == 'no')>No</option>
                            <option value="yes" @selected($sale_order->transporter_used == 'yes')>Yes</option>
                        </select>
                    </div>
                </div>



                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Customer Details</h6>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <select @if(!$sale_order->inquiry_id) name="customer_id" @endif id="customer_id" class="form-control select2">
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" @selected($customer->id == $sale_order->customer_id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" @if($sale_order->inquiry_id) name="customer_id" @endif value="{{ $sale_order->customer_id }}" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Sell By:</label>
                        <input type="text" class="form-control" value="{{ $sale_order->parent_user->name ?? 'N/A' }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Broker:</label>
                        <select name="broker_id" id="broker_id" class="form-control select2">
                            <option value="">Select Broker</option>
                            @foreach ($brokers ?? [] as $broker)
                                <option value="{{ $broker->id }}" @selected($broker->id == $sale_order->broker_id)>{{ $broker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission RS per KG:</label>
                        <input type="number" name="commission_per_kg" id="commission_per_kg" class="form-control" step="0.0001" min="0" value="{{ $sale_order->commission_per_kg ?? 0 }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission in % per KG:</label>
                        <input type="number" name="commission_percent_per_kg" id="commission_percent_per_kg"
                            class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Contact Person:</label>
                        <input type="text" name="contact_person" id="contact_person" value="{{ $sale_order->contact_person }}" class="form-control" @if($sale_order->inquiry_id) readonly @endif>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Token Money:</label>
                        <input type="number" name="token_money" id="token_money" value="{{ $sale_order->token_money }}" class="form-control" step="0.01" min="0">
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Payment Details</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Pay Type:</label>
                        <select name="pay_type_id" id="pay_type_id" class="form-control select2" onchange="is_type_credit(this)">
                            <option value="">Select Pay Type</option>
                            @foreach ($pay_types as $pay_type)
                                <option value="{{ $pay_type->id }}" @selected($sale_order->pay_type_id == $pay_type->id)>{{ $pay_type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Payment Terms:</label>
                        <select name="payment_term_id" id="payment_term_id" class="form-control select2 credit" @disabled($sale_order->pay_type_id != 8)>
                            <option value="">Select Payment Term</option>
                            @foreach ($payment_terms as $payment_term)
                                <option value="{{ $payment_term->id }}" @selected($payment_term->id == $sale_order->payment_term_id)>{{ $payment_term->desc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6 {{ $sale_order->pay_type_id == 10 ? '' : 'd-none' }}" id="unallocated_rv_container">
                    <div class="form-group">
                        <label class="form-label d-block">Unallocated Receipt Vouchers:</label>
                        <select name="receipt_voucher_item_ids[]" id="receipt_voucher_item_ids" class="form-control select2" multiple style="width: 100%">
                            @php
                                $linkedRvs = App\Models\ReceiptVoucherItem::where('reference_type', 'sale_order')
                                    ->where('reference_id', $sale_order->id)
                                    ->get();
                            @endphp
                            @foreach($linkedRvs as $rv)
                                <option value="{{ $rv->id }}" selected>RV Item #{{ $rv->id }} - Amount: {{ $rv->net_amount }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

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
                    $oldFactories = old('arrival_location_id', $selectedFactories);
                    $oldSections = old('arrival_sub_location_id', $selectedSections);
                @endphp
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="locations[]" id="locations" class="form-control select2" multiple>
                            @php
                                $selectedLocations = $sale_order->locations->pluck('location_id')->map(fn($id) => (int)$id)->toArray() ?? [];
                                $customerLocations = [];
                                if ($sale_order->customer_id) {
                                    $customer = \App\Models\Master\Customer::find($sale_order->customer_id);
                                    if ($customer && !empty($customer->company_location_ids)) {
                                        $customerLocations = array_map('intval', $customer->company_location_ids);
                                    }
                                }
                                
                                // Merge selected with customer locations
                                $allVisibleIds = array_unique(array_merge($customerLocations, $selectedLocations));
                                $visibleLocations = \App\Models\Master\CompanyLocation::whereIn('id', $allVisibleIds)->get();
                            @endphp
                            @foreach ($visibleLocations as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedLocations))>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_location_id[]" id="arrival_location_id" class="form-control select2" multiple @if($sale_order->inquiry_id) disabled @endif>
                            @foreach ($arrivalLocations as $factory)
                                <option value="{{ $factory->id }}" data-company="{{ $factory->company_location_id }}" @selected(in_array($factory->id, $oldFactories))>{{ $factory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section:</label>
                        <select name="arrival_sub_location_id[]" id="arrival_sub_location_id" class="form-control select2" multiple @if($sale_order->inquiry_id) disabled @endif>
                            @foreach ($arrivalSubLocations as $section)
                                <option value="{{ $section->id }}" data-factory="{{ $section->arrival_location_id }}" @selected(in_array($section->id, $oldSections))>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $sale_order->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        {{-- <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()" id="addRowBtn"
                >
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div> --}}

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th class="col-3">Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Minimum Qty (kg)</th>
                            <th>Maximum Qty (kg)</th>
                            <th>Rate per Kg</th>
                            <th>Rate per Mond</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th style="display: none;">Pack Size</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        @foreach ($sale_order->sales_order_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <select name="item_id[]" id="item_id_{{ $index }}"
                                        class="form-control select2">
                                        <option value="">Select Item</option>
                                        @foreach ($items ?? [] as $item)
                                            <option value="{{ $item->id }}" @selected($data->item_id == $item->id)>
                                                {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="bag_type[]" id="bag_type_id_{{ $index }}"
                                        class="form-control select2">
                                        <option value="">Select Bag Type</option>
                                        @foreach ($bag_types ?? [] as $bag_type)
                                            <option value="{{ $bag_type->id }}" @selected($bag_type->id == $data->bag_type)>
                                                {{ $bag_type->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="bag_size[]" id="bag_size_{{ $index }}" class="form-control bag_size select2" onchange="calcBagTypes(this)">
                                        <option value="">Select Packing</option>
                                        @foreach ($packings as $packing)
                                            <option value="{{ $packing }}" @selected($data->bag_size == $packing)>{{ $packing }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="sales_inquiry_id[]"
                                        id="sales_inquiry_id_{{ $index }}" value="{{ $data->sales_inquiry_id }}"
                                        class="form-control">
                                </td>
                                <td>
                                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ $data->no_of_bags }}" class="form-control no_of_bags" readonly>
                                </td>
                                <td>
                                    <input type="number" name="minimum_qty[]" id="minimum_qty_{{ $index }}"
                                        value="{{ round($data->minimum_qty) }}" class="form-control minimum_qty" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}"
                                        value="{{ round($data->qty ?? ($data->no_of_bags * $data->bag_size) ) }}" class="form-control qty"
                                        step="0.01" min="0" onkeyup="calcBagTypes(this)" onchange="calcBagTypes(this)">
                                </td>
                                <td>
                                    <input type="number" name="rate[]" id="rate_{{ $index }}"
                                        value="{{ $data->rate }}" onkeyup="calculateRates(this)" class="form-control rate rate_per_kg"
                                        step="0.01" min="0">
                                </td>

                                  <td>
                                    <input type="number" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}"
                                        value="{{ $data->rate_per_mond }}" onkeyup="calculateRates(this)" class="form-control rate rate_per_mond"
                                        step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="number" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ round($data->rate * $data->qty) }}" onkeyup="calc(this)"
                                        class="form-control amount" step="1" min="0">
                                </td>
                                <td>
                                    <select name="brand_id[]" id="brand_id_{{ $index }}"
                                        class="form-control select2">
                                        <option value="">Select Brands</option>
                                        @foreach (getAllBrands() ?? [] as $brand)
                                            <option value="{{ $brand->id }}" @selected($data->brand_id == $brand->id)>
                                                {{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="display: none;">
                                    <input type="text" value="0" name="pack_size[]" id="pack_size_{{ $index }}"
                                        value="{{ $data->pack_size }}" class="form-control pack-size">
                                </td>
                                <td>
                                    <input type="text" name="description[]" id="description{{ $index }}"
                                        value="{{ $data->description }}" class="form-control pack-size">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm removeRowBtn"
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
                        <select name="broker_id" id="broker_id" class="form-control select2">
                            <option value="">Select Broker</option>
                            @foreach ($brokers ?? [] as $broker)
                                <option value="{{ $broker->id }}" @selected($broker->id == $sale_order->broker_id)>{{ $broker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission RS per KG:</label>
                        <input type="number" name="commission_per_kg" id="commission_per_kg" class="form-control" step="0.0001" min="0" value="{{ $sale_order->commission_per_kg ?? 0 }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Comission in % per KG:</label>
                        <input type="number" name="commission_percent_per_kg" id="commission_percent_per_kg"
                            class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    @if ($sale_order->am_approval_status === 'reverted' || $sale_order->am_change_made == 0)
        <div class="alert alert-primary border-start border-primary border-3 mb-4 mx-2">
            <div class="d-flex align-items-center">
                <i class="fa fa-exclamation-triangle me-3 text-primary" style="font-size: 20px;"></i>
                <div>
                    <strong>Approval Authority Comments</strong><br>
                    @if($latestLog)
                        <div class="small mb-1">
                            <strong>{{ $latestLog->user->name ?? 'N/A' }}</strong>
                            <span class="">({{ $latestLog->role->name ?? 'Role N/A' }})</span>
                        </div>
                        {{ $latestLog->comments ?? 'No comments available' }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    salesInquiryRowIndex = {{ count($sale_order->sales_order_data) }};

    function get_customer_related_data() {
        // Only if not disabled (i.e. not locked by inquiry)
        if ($("#customer_id").is(":disabled")) return;
        
        get_inquiries();
        getCustomerLocations();
        get_unallocated_rvs();
    }

    function get_inquiries() {
        const customer_id = $("#customer_id").val();
        if (!customer_id) return;

        $.ajax({
            url: "{{ route('sales.get-sale-inquiries-against-customer') }}",
            method: "GET",
            data: { customer_id: customer_id },
            dataType: "json",
            success: function(res) {
                $("#inquiry_id").select2({
                    data: res
                });
            }
        });
    }

    function getCustomerLocations() {
        const customer_id = $("#customer_id").val();
        if (!customer_id) {
            $("#locations").empty().trigger('change');
            return;
        }

        $("#locations, #arrival_location_id, #arrival_sub_location_id").prop('disabled', true);

        $.ajax({
            url: "{{ route('sales.get-customer-locations') }}",
            method: "GET",
            data: { customer_id: customer_id },
            dataType: "json",
            success: function(res) {
                $("#locations").empty();
                if (res && res.length > 0) {
                    res.forEach(loc => {
                        $("#locations").append(new Option(loc.name, loc.id));
                    });
                }
                $("#locations").prop('disabled', false).trigger('change');
                $("#arrival_location_id, #arrival_sub_location_id").prop('disabled', false);
            },
            error: function() {
                $("#locations, #arrival_location_id, #arrival_sub_location_id").prop('disabled', false);
            }
        });
    }


    function calculateForRatePerKg(mond) {
        return mond / 40;
    }

    function calculateForRatePerMond(kg) {
        return kg * 40;
    }

    function calculateRates(el) {
        if(!$(el).val()) {
            return;
        }

        const tr = $(el).closest("tr");
        if($(el).hasClass("rate_per_kg")) {
            tr.find(".rate_per_mond").val(calculateForRatePerMond($(el).val()));
        } else {
            tr.find(".rate_per_kg").val(calculateForRatePerKg($(el).val()));
        }

        calc(el);
    }
    function validateExpiry() {
        const inquiryId = $('#inquiry_id').val();
        const orderDate = $('#order_date').val();
        const deliveryDate = $('#delivery_date').val();

        // Only validate if dates are fully formed (length 10)
        const isOrderDateComplete = orderDate && orderDate.length === 10;
        const isDeliveryDateComplete = deliveryDate && deliveryDate.length === 10;

        if (isOrderDateComplete && isDeliveryDateComplete) {
            if (orderDate > deliveryDate) {
                // $('#delivery_date').val('');
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date!',
                    text: 'Order date cannot be greater than delivery date.',
                    confirmButtonText: 'OK'
                });
            }
        }
    }

    $(document).on('change blur', '#order_date', function() {
        const val = this.value;
        if (val && val.length === 10) {
            const year = parseInt(val.split('-')[0]);
            if (year >= 2000) {
                validateExpiry();
            }
        }
    });

    $(document).on('change blur', '#delivery_date', function() {
        const val = this.value;
        if (val && val.length === 10) {
            const year = parseInt(val.split('-')[0]);
            if (year >= 2000) {
                validateExpiry();
            }
        }
    });
    

    function is_type_credit(el) {
        const type = $(el).val();
       

        if(type == 8) {
            $(".credit").prop('disabled', false);
        } else {
            $(".credit").prop("disabled", true);
        }

        if(type == 10) { // Advanced
            $("#unallocated_rv_container").removeClass("d-none");
            get_unallocated_rvs();
        } else {
            $("#unallocated_rv_container").addClass("d-none");
            $("#receipt_voucher_item_ids").val([]).trigger('change');
        }
    }

    function get_unallocated_rvs() {
        const customer_id = $("#customer_id").val() || "{{ $sale_order->customer_id }}";
        const pay_type_id = $("#pay_type_id").val();
        
        if (!customer_id || pay_type_id != 10) {
            return;
        }

        // Get currently selected IDs to preserve them
        const currentlySelected = $("#receipt_voucher_item_ids").val() || [];

        $.ajax({
            url: "{{ route('sales.get-unallocated-receipt-vouchers') }}",
            method: "GET",
            data: { 
                customer_id: customer_id,
                sale_order_id: "{{ $sale_order->id }}" // Pass this to include already linked ones
            },
            success: function(res) {
                let options = '';
                res.forEach(item => {
                    const isSelected = currentlySelected.includes(String(item.id)) ? 'selected' : '';
                    options += `<option value="${item.id}" ${isSelected}>RV Item #${item.id} - Amount: ${item.net_amount} (Ref: ${item.line_desc || 'No Description'})</option>`;
                });
                $("#receipt_voucher_item_ids").html(options).trigger('change');
            },
            error: function(err) {
                console.error("Error fetching unallocated RVs:", err);
            }
        });
    }

    function enableInquiryFields() {
        // Enable fields when no inquiry selected
        $("#delivery_date").prop('readonly', false);
        $("#customer_id").prop('disabled', false);
        $("#sauda_type").prop('disabled', false);
        $("#locations").prop('disabled', false);
        $("#token_money").prop('readonly', false);
        $("#contact_person").prop('readonly', false).val('');
        $("#arrival_location_id").prop('disabled', false).val('').trigger('change.select2');
        $("#arrival_sub_location_id").prop('disabled', false).val('').trigger('change.select2');
        $("#token_money").val(''); // Clear token money when no inquiry

        // Restore name attributes and remove hidden inputs
        $("#customer_id").attr('name', 'customer_id');
        $("#sauda_type").attr('name', 'sauda_type');
        $("#locations").attr('name', 'locations[]');
        $("#arrival_location_id").attr('name', 'arrival_location_id[]');
        $("#arrival_sub_location_id").attr('name', 'arrival_sub_location_id[]');
        
        $('#customer_id_hidden').remove();
        $('#sauda_type_hidden').remove();
        $('.locations_hidden').remove();
        $('.arrival_location_hidden').remove();
        $('.arrival_sub_location_hidden').remove();
    }
    function get_inquiry_data() {
        
        const inquiry_id = $("#inquiry_id").val();

        if (!inquiry_id) {
            // If no inquiry selected, make fields editable
            enableInquiryFields();
            return;
        }

        // First, get the inquiry details
        $.ajax({
            url: "{{ route('sales.get-sale-inquiry-data') }}",
            method: "GET",
            data: {
                inquiry_id: inquiry_id,
                get_details: true
            },
            dataType: "json",
            success: function(res) {
                // Fill delivery date with required_date
                if (res.required_date) {
                    $("#delivery_date").val(res.required_date);
                    getNumber(); // Generate SO number based on date
                }

                // Fill customer
                if (res.customer_id) {
                    $("#customer_id").val(res.customer_id).trigger('change.select2');
                }

                // Fill contract type (sauda_type)
                if (res.contract_type) {
                    $("#sauda_type").val(res.contract_type).trigger('change.select2');
                    if (res.contract_type == 'x-mill') {
                        $('#transporter_used').val('no').trigger('change.select2');
                    }
                    else {
                        $('#transporter_used').val('yes').trigger('change.select2');
                    }
                }

                if (res.contact_person) {
                    $("#contact_person").val(res.contact_person).prop('readonly', true);
                }
                const inquiryFactories = res.arrival_locations || (res.arrival_location_id ? [res.arrival_location_id] : []);
                const inquirySections = res.arrival_sub_locations || (res.arrival_sub_location_id ? [res.arrival_sub_location_id] : []);



                 // clear old options
                $('#locations').empty();

                // append + select all
                res.locations.forEach(item => {
                    let option = new Option(item.text, item.id, true, true); // selected = true
                    $('#locations').append(option);
                });

                // notify select2
                $('#locations').trigger('change');

                $('#arrival_location_id').empty();

                // append + select all
                res.arrival_locations.forEach(item => {
                    let option = new Option(item.text, item.id, true, true); // selected = true
                    $('#arrival_location_id').append(option);
                    
                });

                // notify select2
                $('#arrival_location_id').trigger('change');

               
                $('#arrival_sub_location_id').empty();

                // append + select all
                res.arrival_sub_locations.forEach(item => {
                    let option = new Option(item.text, item.id, true, true); // selected = true
                    $('#arrival_sub_location_id').append(option);
                });

                // notify select2
                $('#arrival_sub_location_id').trigger('change');

               

                // Fill token money
                if (res.token_money !== null && res.token_money !== undefined) {
                    $("#token_money").val(res.token_money);
                }

                if (res.remarks !== null && res.remarks !== undefined) {
                    $("#remarks").val(res.remarks);
                }

                // Make fields readonly
                disableInquiryFields();
            },
            error: function(error) {
                alert("err")
                console.log(error);
            }
        });

        // Then, get the line items
        $.ajax({
            url: "{{ route('sales.get-sale-inquiry-data') }}",
            method: "GET",
            data: {
                inquiry_id: inquiry_id
            },
            dataType: "html",
            success: function(res) {
                $("#salesInquiryBody").html(res);
                $('#salesInquiryBody').find('.select2').select2();
                if ($("#inquiry_id").val()) {
                    disableTableFields();
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
    allLocations = @json(get_locations());
    factories = @json($arrivalLocations);
    sections = @json($arrivalSubLocations);

    $(document).ready(function() {
        $('.select2').select2();

        $('#customer_id').on('change', function() {
            get_customer_related_data();
        });

        const initialFactories = @json($oldFactories ?? []);
        const initialSections = @json($oldSections ?? []);
        const inquirySelected = "{{ $sale_order->inquiry_id ? 1 : 0 }}";
        let isInitializing = true;

        function populateFactories() {
            const customer_id = $('#customer_id').val();
            if (!customer_id) {
                $('#arrival_location_id').empty().trigger('change.select2');
                return;
            }
            const selectedLocations = $('#locations').val() || [];
            const currentValues = $('#arrival_location_id').val() || initialFactories;
            $('#arrival_location_id').empty();

            factories
                .filter(f => selectedLocations.length === 0 || selectedLocations.includes(String(f.company_location_id)))
                .forEach(f => {
                    $('#arrival_location_id').append(`<option value="${f.id}" data-company="${f.company_location_id}">${f.name} (${f.company_location.name})</option>`);
                });

            $('#arrival_location_id').val(currentValues).trigger('change.select2');
        }

        function populateSections() {
            const customer_id = $('#customer_id').val();
            if (!customer_id) {
                $('#arrival_sub_location_id').empty().trigger('change.select2');
                return;
            }
            const factoryIds = $('#arrival_location_id').val() || initialFactories;
            const currentSections = $('#arrival_sub_location_id').val() || initialSections;
            $('#arrival_sub_location_id').empty();

            sections
                .filter(s => factoryIds.length === 0 || factoryIds.includes(String(s.arrival_location_id)))
                .forEach(s => {
                    $('#arrival_sub_location_id').append(`<option value="${s.id}" data-factory="${s.arrival_location_id}">${s.name} (${s.arrival_location.name})</option>`);
                });

            $('#arrival_sub_location_id').val(currentSections).trigger('change.select2');
        }

        $('#locations').on('change', function() {
            if (!isInitializing) {
                populateFactories();
                populateSections();
            }
        });

        $('#arrival_location_id').on('change', function() {
            if (!isInitializing) {
                populateSections();
            }
        });

        window.populateFactories = populateFactories;
        window.populateSections = populateSections;

        populateFactories();
        populateSections();
        isInitializing = false;

        if (inquirySelected === "1") {
            disableInquiryFields();
            disableTableFields();
        }
        if ("{{ $sale_order->pay_type_id }}" == "10") {
            get_unallocated_rvs();
        }
        validateExpiry();
    });

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
                <select name="bag_type_id[]" id="bag_type_id_${index}" class="form-control select2">
                    <option value="">Select Bag Type</option>
                    @foreach ($bag_types ?? [] as $bag_type)
                        <option value="{{ $bag_type->id }}">{{ $bag_type->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="bag_size[]" id="bag_size_${index}" class="form-control bag_size select2" onchange="calcBagTypes(this)">
                    <option value="">Select Packing</option>
                    @foreach ($packings as $packing)
                        <option value="{{ $packing }}">{{ $packing }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="sales_inquiry_id[]" id="sales_inquiry_id_${index}" value="" class="form-control">
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_${index}" class="form-control no_of_bags" readonly>
            </td>
            <td>
                <input type="number" name="minimum_qty[]" id="minimum_qty_${index}" class="form-control minimum_qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="qty[]" id="qty_${index}" class="form-control qty" step="0.01" min="0" onkeyup="calcBagTypes(this)" onchange="calcBagTypes(this)">
            </td>
            <td>
                <input onkeyup="calculateRates(this)" type="number" name="rate[]" id="rate_${index}" class="form-control rate rate_per_kg" step="0.01" min="0">
            </td>
            <td>
                <input onkeyup="calculateRates(this)" type="number" name="rate_per_mond[]" id="rate_per_mond_${index}" class="form-control rate_per_mond" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td>
                <select name="brand_id[]" id="brand_id_${index}" class="form-control select2">
                    <option value="">Select Brands</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>
            <td style="display: none;">
                <input type="text" name="pack_size[]" id="pack_size_${index}" value="0" class="form-control pack-size">
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
        $(`#bag_type_id_${index}`).select2();
        $(`#bag_size_${index}`).select2();
        $(`#brand_id_${index}`).select2();

        // Enable remove buttons if more than one row
        if ($('#salesInquiryBody tr').length > 1) {
            $('.removeRowBtn').prop('disabled', false);
        }
    }

    function removeRow(index) {
        $('#row_' + index).remove();
        // If only one row left, disable its remove button
        if ($('#salesInquiryBody tr').length === 1) {
            $('#salesInquiryBody tr .removeRowBtn').prop('disabled', true);
        }
    }

    function disableInquiryFields() {
        $("#delivery_date").prop('readonly', true);
        $("#customer_id").prop('disabled', true);
        $("#sauda_type").prop('disabled', true);
        $("#locations").prop('disabled', true);
        $("#token_money").prop('readonly', true);
        $("#contact_person").prop('readonly', true);
        $("#arrival_location_id").prop('disabled', true);
        $("#arrival_sub_location_id").prop('disabled', true);

        // Preserve disabled values for submit
        $('#customer_id_hidden').remove();
        $('<input>').attr({
            type: 'hidden',
            name: 'customer_id',
            id: 'customer_id_hidden',
            value: $("#customer_id").val()
        }).appendTo('#ajaxSubmit');

        $('#sauda_type_hidden').remove();
        $('<input>').attr({
            type: 'hidden',
            name: 'sauda_type',
            id: 'sauda_type_hidden',
            value: $("#sauda_type").val()
        }).appendTo('#ajaxSubmit');

        // Preserve locations (multi)
        $('.locations_hidden').remove();
        const selectedLocations = $("#locations").val() || [];
        selectedLocations.forEach(function(loc) {
            $('<input>', {
                type: 'hidden',
                name: 'locations[]',
                class: 'locations_hidden',
                value: loc
            }).appendTo('#ajaxSubmit');
        });

        // Preserve factories (multi)
        $('.arrival_location_hidden').remove();
        const selectedFactories = $("#arrival_location_id").val() || [];
        selectedFactories.forEach(function(id) {
            $('<input>', {
                type: 'hidden',
                name: 'arrival_location_id[]',
                class: 'arrival_location_hidden',
                value: id
            }).appendTo('#ajaxSubmit');
        });

        // Preserve sections (multi)
        $('.arrival_sub_location_hidden').remove();
        const selectedSections = $("#arrival_sub_location_id").val() || [];
        selectedSections.forEach(function(id) {
            $('<input>', {
                type: 'hidden',
                name: 'arrival_sub_location_id[]',
                class: 'arrival_sub_location_hidden',
                value: id
            }).appendTo('#ajaxSubmit');
        });
    }

    function disableTableFields() {
        // Disable UI controls
        $('#salesInquiryTable').find('input, select, textarea, button').each(function() {
            const $el = $(this);
            if ($el.is('select')) {
                $el.prop('disabled', true);
            } else if ($el.is('button')) {
                $el.prop('disabled', true);
            } else {
                $el.prop('readonly', true);
            }
        });

        // Ensure disabled values for item_id, brand_id, and bag_type are posted
        $('#salesInquiryTable tbody tr').each(function() {
            const $row = $(this);
            const itemVal = $row.find('select[name="item_id[]"], input[name="item_id[]"]').val() || '';
            const brandVal = $row.find('select[name="brand_id[]"], input[name="brand_id[]"]').val() || '';
            const bagTypeVal = $row.find('select[name="bag_type_id[]"], select[name="bag_type[]"], input[name="bag_type[]"]').val() || '';
            const bagSizeVal = $row.find('select[name="bag_size[]"], input[name="bag_size[]"]').val() || '';

            $row.find('.hidden_item_id').remove();
            $row.find('.hidden_brand_id').remove();
            $row.find('.hidden_bag_type').remove();
            $row.find('.hidden_bag_size').remove();

            if (itemVal) {
                $('<input>', {
                    type: 'hidden',
                    name: 'item_id[]',
                    class: 'hidden_item_id',
                    value: itemVal
                }).appendTo($row);
            }

            if (brandVal) {
                $('<input>', {
                    type: 'hidden',
                    name: 'brand_id[]',
                    class: 'hidden_brand_id',
                    value: brandVal
                }).appendTo($row);
            }

            if (bagTypeVal) {
                $('<input>', {
                    type: 'hidden',
                    name: 'bag_type[]',
                    class: 'hidden_bag_type',
                    value: bagTypeVal
                }).appendTo($row);
            }

            if (bagSizeVal) {
                $('<input>', {
                    type: 'hidden',
                    name: 'bag_size[]',
                    class: 'hidden_bag_size',
                    value: bagSizeVal
                }).appendTo($row);
            }
        });
    }

    function enableTableFields() {
        $('#salesInquiryTable').find('input, select, textarea, button').each(function() {
            const $el = $(this);
            if ($el.is('select')) {
                $el.prop('disabled', false);
            } else if ($el.is('button')) {
                $el.prop('disabled', false);
            } else {
                $el.prop('readonly', false);
            }
        });

        $('#salesInquiryTable tbody tr').each(function() {
            const $row = $(this);
            $row.find('.hidden_item_id').remove();
            $row.find('.hidden_brand_id').remove();
            $row.find('.hidden_bag_type').remove();
            $row.find('.hidden_bag_size').remove();
        });
    }

    function enableInquiryFields() {
        // Enable fields and reset values when no inquiry selected
        $("#delivery_date").prop('readonly', false).val('');
        $("#customer_id").prop('disabled', false).val('').trigger('change.select2');
        $("#sauda_type").prop('disabled', false).val('').trigger('change.select2');
        $("#locations").empty();
        $("#locations").prop('disabled', false).removeAttr('disabled').val([]).trigger('change');
        $("#token_money").prop('readonly', false).removeAttr('readonly').val('');
        $("#contact_person").prop('readonly', false).removeAttr('readonly').val('');
        $("#arrival_location_id").prop('disabled', false).removeAttr('disabled').val([]).trigger('change');
        $("#arrival_sub_location_id").prop('disabled', false).removeAttr('disabled').val([]).trigger('change');
        $("#remarks").val('');
        $("#so_reference_no").val('');
        $("#reference_no").val("{{ $sale_order->reference_no }}");
        $("#order_date").val("{{ $sale_order->order_date }}");

        if(window.populateFactories) window.populateFactories();
        if(window.populateSections) window.populateSections();

        // Restore name attributes and remove hidden inputs
        $("#customer_id").attr('name', 'customer_id');
        $("#sauda_type").attr('name', 'sauda_type');
        $("#locations").attr('name', 'locations[]');
        $("#arrival_location_id").attr('name', 'arrival_location_id[]');
        $("#arrival_sub_location_id").attr('name', 'arrival_sub_location_id[]');

        $('#customer_id_hidden').remove();
        $('#sauda_type_hidden').remove();
        $('.locations_hidden').remove();
        $('.arrival_location_hidden').remove();
        $('.arrival_sub_location_hidden').remove();
        
        // Refresh line items
        $("#salesInquiryBody").empty();
        salesInquiryRowIndex = 0;
        addRow();
        // Disable remove button for the first row
        $('#salesInquiryBody tr:first .removeRowBtn').prop('disabled', true);

        enableTableFields();
    }

    function calc(el) {
        const element = $(el).closest("tr");

        const rate = parseFloat($(element).find(".rate_per_kg").val()) || 0;
        const qty = parseFloat($(element).find(".qty").val()) || 0;

        const amount = $(element).find(".amount");
      
        amount.val((rate * qty).toFixed(0));
    }

    function calcBagTypes(el) {
        const element = $(el).closest("tr");
        const bag_size = parseFloat($(element).find(".bag_size").val());
        const qty = parseFloat($(element).find(".qty").val());
        const no_of_bags = $(element).find(".no_of_bags");
        
        // Calculate amount regardless of bag size
        calc(el);

        if (isNaN(bag_size) || isNaN(qty)) {
            no_of_bags.val('');
            return;
        }
        
        const result = (qty / bag_size).toFixed();
        
        no_of_bags.val(result);
    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-order.getnumber') }}",
            method: "GET",
            data: {
                contract_date: $("#delivery_date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#reference_no").val(res.so_no)
            },
            error: function(error) {
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
    // Commission conversion functions
    function calculateCommissionFromPercent() {
        const percent = parseFloat($('#commission_percent_per_kg').val()) || 0;
        const ratePerKg = getFirstItemRate();
        const qty = getFirstItemQty();

        if (ratePerKg > 0 && qty > 0) {
            const commissionInRs = (percent / 100) * (ratePerKg * qty);
            $('#commission_per_kg').val(commissionInRs.toFixed(4));
        } else {
            $('#commission_per_kg').val('0');
        }
    }

    function calculateCommissionFromRs() {
        const commissionInRs = parseFloat($('#commission_per_kg').val()) || 0;
        const ratePerKg = getFirstItemRate();
        const qty = getFirstItemQty();

        // if (ratePerKg > 0 && qty > 0) {
        if (ratePerKg > 0) {
            const percent = (commissionInRs / (ratePerKg)) * 100;
            $('#commission_percent_per_kg').val(percent.toFixed(2));
        } else {
            $('#commission_percent_per_kg').val('0');
        }
    }

    function getFirstItemRate() {
        const rate = $('#salesInquiryBody tr:first input[name="rate[]"]').val();
        return parseFloat(rate) || 0;
    }

    function getFirstItemQty() {
        const qty = $('#salesInquiryBody tr:first input[name="qty[]"]').val();
        return parseFloat(qty) || 0;
    }

    // Update commission when rate changes
    function updateCommissionFromRate() {
        if (window.lastCommissionInputType === 'percent') {
            calculateCommissionFromPercent();
        } else {
            calculateCommissionFromRs();
        }
    }

    $(document).ready(function () {
        // Calculate initial % if RS is present
        if ($('#commission_per_kg').val() && parseFloat($('#commission_per_kg').val()) > 0) {
            window.lastCommissionInputType = 'rs';
            calculateCommissionFromRs();
        } else {
            window.lastCommissionInputType = 'percent';
        }

        // When percentage field changes, calculate RS
        $('#commission_percent_per_kg').on('keyup change', function () {
            window.lastCommissionInputType = 'percent';
            calculateCommissionFromPercent();
        });

        // When RS field changes, calculate percentage
        $('#commission_per_kg').on('keyup change', function () {
            window.lastCommissionInputType = 'rs';
            calculateCommissionFromRs();
        });

        // When rate or qty changes in any row, update commission
        $(document).on('keyup change', '.rate_per_kg, .qty', function () {
            updateCommissionFromRate();
        });
    });
</script>
