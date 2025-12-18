<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.sale-order.update', ['sale_order' => $sale_order->id]) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    {{ method_field('PUT') }}
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-order.list') }}" />
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">SO No:</label>
                <input type="text" name="reference_no" id="reference_no" value="{{ $sale_order->reference_no }}"
                    class="form-control" readonly>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
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
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Date:</label>
                <input type="date" name="order_date" id="order_date" value="{{ $sale_order->order_date }}"
                    class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contract Type:</label>
                <select name="sauda_type" id="sauda_type" class="form-control select2">
                    <option value="">Select Contract Type</option>
                    <option value="pohanch" @selected($sale_order->sauda_type == $sale_order->sauda_type)>Pohanch</option>
                    <option value="x-mill" @selected($sale_order->sauda_type == $sale_order->sauda_type)>X-mill</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                <select name="customer_id" id="customer_id" class="form-control select2">
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}" @selected($customer->id == $sale_order->customer_id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contact Person:</label>
                <input type="text" name="contact_person" id="contact_person" value="{{ $sale_order->contact_person }}" class="form-control" @if($sale_order->inquiry_id) readonly @endif>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Delivery Date:</label>
                <input type="date" name="delivery_date" value="{{ $sale_order->delivery_date }}" 
                    id="delivery_date" class="form-control">
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference Number:</label>
                <input type="text" name="so_reference_no" id="so_reference_no" value="{{ $sale_order->so_reference_no }}"
                    class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Pay Type:</label>
                <select name="pay_type_id" id="pay_type_id" class="form-control select2">
                    <option value="">Select Pay Type</option>
                    @foreach ($pay_types as $pay_type)
                        <option value="{{ $pay_type->id }}" @selected($sale_order->pay_type_id == $pay_type->id)>{{ $pay_type->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Payment Terms:</label>
                <select name="payment_term_id" id="payment_term_id" class="form-control select2">
                    <option value="">Select Payment Term</option>
                    @foreach ($payment_terms as $payment_term)
                        <option value="{{ $payment_term->id }}" @selected($payment_term->id == $sale_order->payment_term_id)>{{ $payment_term->desc }}</option>
                    @endforeach
                </select>
            </div>
        </div>
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

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="locations[]" id="locations" class="form-control select2" multiple>
                    <option value="">Select Locations</option>
                    @foreach (get_locations() as $location)
                        <option value="{{ $location->id }}" @selected(in_array($location->id, $sale_order->locations->pluck('location_id')->toArray()))>{{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Factory:</label>
                <select name="arrival_location_id[]" id="arrival_location_id" class="form-control select2" multiple @if($sale_order->inquiry_id) disabled @endif>
                    <option value="">Select Factory</option>
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
                    <option value="">Select Section</option>
                    @foreach ($arrivalSubLocations as $section)
                        <option value="{{ $section->id }}" data-factory="{{ $section->arrival_location_id }}" @selected(in_array($section->id, $oldSections))>{{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Token Money:</label>
                <input type="number" name="token_money" id="token_money" value="{{ $sale_order->token_money }}" class="form-control" step="0.01" min="0">
            </div>
        </div>

        <div class="col-md-8">
            <div class="form-group">
                <label class="form-label">Remarks:</label>
                <textarea name="remarks" id="remarks"  class="form-control">{{ $sale_order->remarks }}</textarea>
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
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (kg)</th>
                            <th>Rate per Kg</th>
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
                                    <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                                        value="{{ $data->bag_size }}" onkeyup="calcBagTypes(this)" class="form-control bag_size"
                                        step="0.01" min="0">
                                    <input type="hidden" name="sales_inquiry_id[]"
                                        id="sales_inquiry_id_{{ $index }}" value="{{ $data->sales_inquiry_id }}"
                                        class="form-control">
                                </td>
                                <td>
                                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ $data->no_of_bags }}" class="form-control no_of_bags" readonly>
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}"
                                        value="{{ $data->qty ?? ($data->no_of_bags * $data->bag_size) }}" class="form-control qty"
                                        step="0.01" min="0" onkeyup="calcBagTypes(this)" onchange="calcBagTypes(this)">
                                </td>
                                <td>
                                    <input type="number" name="rate[]" id="rate_{{ $index }}"
                                        value="{{ $data->rate }}" onkeyup="calc(this)" class="form-control rate"
                                        step="0.01" min="0">
                                </td>

                                <td>
                                    <input type="number" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ $data->rate * $data->qty }}" onkeyup="calc(this)"
                                        class="form-control amount" step="0.01" min="0">
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
    </div>

    <input type="hidden" id="rowCount" value="0">

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
                }

                if (res.contact_person) {
                    $("#contact_person").val(res.contact_person).prop('readonly', true);
                }
                const inquiryFactories = res.arrival_locations || (res.arrival_location_id ? [res.arrival_location_id] : []);
                const inquirySections = res.arrival_sub_locations || (res.arrival_sub_location_id ? [res.arrival_sub_location_id] : []);

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

                // Fill locations
                if (res.locations && res.locations.length > 0) {
                    $("#locations").val(res.locations).trigger('change.select2');
                }

                // Fill token money
                if (res.token_money !== null && res.token_money !== undefined) {
                    $("#token_money").val(res.token_money);
                }

                // Make fields readonly
                disableInquiryFields();
            },
            error: function(error) {
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
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
    $(document).ready(function() {
        $('.select2').select2();

        const factories = @json($arrivalLocations);
        const sections = @json($arrivalSubLocations);
        const initialFactories = @json($oldFactories ?? []);
        const initialSections = @json($oldSections ?? []);
        const inquirySelected = "{{ $sale_order->inquiry_id ? 1 : 0 }}";

        function populateFactories() {
            const selectedLocations = $('#locations').val() || [];
            const currentValues = $('#arrival_location_id').val() || initialFactories;
            $('#arrival_location_id').empty().append('<option value=\"\">Select Factory</option>');

            factories
                .filter(f => selectedLocations.length === 0 || selectedLocations.includes(String(f.company_location_id)))
                .forEach(f => {
                    $('#arrival_location_id').append(`<option value="${f.id}" data-company="${f.company_location_id}">${f.name}</option>`);
                });

            $('#arrival_location_id').val(currentValues).trigger('change.select2');
        }

        function populateSections() {
            const factoryIds = $('#arrival_location_id').val() || initialFactories;
            const currentSections = $('#arrival_sub_location_id').val() || initialSections;
            $('#arrival_sub_location_id').empty().append('<option value=\"\">Select Section</option>');

            sections
                .filter(s => factoryIds.length === 0 || factoryIds.includes(String(s.arrival_location_id)))
                .forEach(s => {
                    $('#arrival_sub_location_id').append(`<option value="${s.id}" data-factory="${s.arrival_location_id}">${s.name}</option>`);
                });

            $('#arrival_sub_location_id').val(currentSections).trigger('change.select2');
        }

        $('#locations').on('change', function() {
            populateFactories();
            populateSections();
        });

        $('#arrival_location_id').on('change', function() {
            populateSections();
        });

        populateFactories();
        populateSections();

        if (inquirySelected === "1") {
            disableInquiryFields();
            disableTableFields();
        }
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
                <input type="text" name="bag_size[]" id="bag_size_${index}" class="form-control bag_size" onkeyup="calcBagTypes(this)" step="0.01" min="0">
                <input type="hidden" name="sales_inquiry_id[]" id="sales_inquiry_id_${index}" value="" class="form-control">
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_${index}" class="form-control no_of_bags" readonly>
            </td>
            <td>
                <input type="number" name="qty[]" id="qty_${index}" class="form-control qty" step="0.01" min="0" onkeyup="calcBagTypes(this)" onchange="calcBagTypes(this)">
            </td>
            <td>
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calc(this)" class="form-control rate" step="0.01" min="0">
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
        $(`#brand_id_${index}`).select2();
    }

    function removeRow(index) {
        $('#row_' + index).remove();
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
        if (!$('#customer_id_hidden').length) {
            $('<input>').attr({
                type: 'hidden',
                name: 'customer_id',
                id: 'customer_id_hidden',
                value: $("#customer_id").val()
            }).appendTo('form');
        } else {
            $('#customer_id_hidden').val($("#customer_id").val());
        }

        if (!$('#sauda_type_hidden').length) {
            $('<input>').attr({
                type: 'hidden',
                name: 'sauda_type',
                id: 'sauda_type_hidden',
                value: $("#sauda_type").val()
            }).appendTo('form');
        } else {
            $('#sauda_type_hidden').val($("#sauda_type").val());
        }

        // Preserve locations (multi)
        $('.locations_hidden').remove();
        const selectedLocations = $("#locations").val() || [];
        selectedLocations.forEach(function(loc) {
            $('<input>', {
                type: 'hidden',
                name: 'locations[]',
                class: 'locations_hidden',
                value: loc
            }).appendTo('form');
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
            }).appendTo('form');
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
            }).appendTo('form');
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
            const itemVal = $row.find('select[name="item_id[]"]').val() || '';
            const brandVal = $row.find('select[name="brand_id[]"]').val() || '';
            const bagTypeVal = $row.find('select[name="bag_type_id[]"], select[name="bag_type[]"]').val() || '';

            $row.find('.hidden_item_id').remove();
            $row.find('.hidden_brand_id').remove();
            $row.find('.hidden_bag_type').remove();

            $('<input>', {
                type: 'hidden',
                name: 'item_id[]',
                class: 'hidden_item_id',
                value: itemVal
            }).appendTo($row);

            $('<input>', {
                type: 'hidden',
                name: 'brand_id[]',
                class: 'hidden_brand_id',
                value: brandVal
            }).appendTo($row);

            $('<input>', {
                type: 'hidden',
                name: 'bag_type[]',
                class: 'hidden_bag_type',
                value: bagTypeVal
            }).appendTo($row);
        });
    }

    function calc(el) {
        const element = $(el).closest("tr");

        const rate = parseFloat($(element).find(".rate").val()) || 0;
        const qty = parseFloat($(element).find(".qty").val()) || 0;

        const amount = $(element).find(".amount");

        amount.val(rate * qty);
    }

    function calcBagTypes(el) {
        const element = $(el).closest("tr");
        const bag_size = parseFloat($(element).find(".bag_size").val());
        const qty = parseFloat($(element).find(".qty").val());
        const no_of_bags = $(element).find(".no_of_bags");
        
        if (isNaN(bag_size) || isNaN(qty)) {
            no_of_bags.val('');
            return;
        }
        
        const result = (qty / bag_size).toFixed();
        
        no_of_bags.val(result);
        calc(el);
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
</script>
