<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.sale-order.store') }}" method="POST" id="ajaxSubmit2" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-order.list') }}" />
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">SO No:</label>
                <input type="text" name="reference_no" id="reference_no" value="{{ $sale_order->reference_no }}" class="form-control" readonly>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Inquiry No:</label>
                <select name="inquiry_id" id="inquiry_id" onchange="get_inquiry_data()" class="form-control select2" disabled>
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
                <input type="date" name="order_date" id="order_date" value="{{ $sale_order->order_date }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contract Type:</label>
                <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                    <option value="">Select Contract Type</option>
                    <option value="pohanch" @selected(strtolower($sale_order->sauda_type) == 'pohanch')>Pohanch</option>
                    <option value="x-mill" @selected(strtolower($sale_order->sauda_type) == 'x-mill')>X-mill</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                <input type="text" value="{{ get_customer_name($sale_order->customer_id) }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contact Person:</label>
                <input type="text" name="contact_person" id="contact_person" value="{{ $sale_order->contact_person }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Delivery Date:</label>
                <input type="date" name="delivery_date" value="{{ $sale_order->delivery_date }}" id="delivery_date" class="form-control" readonly>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference Number:</label>
                <input type="text" name="so_reference_no" id="so_reference_no" value="{{ $sale_order->so_reference_no }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Pay Type:</label>
                <input type="text" value="{{ $sale_order->pay_type?->name ?? 'N/A' }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Payment Terms:</label>
                <input type="text" value="{{ get_payment_term($sale_order->payment_term_id)?->desc ?? '' }}" class="form-control" readonly>
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
    @endphp

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="locations[]" id="locations" class="form-control select2" multiple disabled>
                    <option value="">Select Locations</option>
                    @foreach(get_locations() as $location)
                        <option value="{{ $location->id }}" @selected(in_array($location->id, $sale_order->locations->pluck("location_id")->toArray()))>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Factory:</label>
                <select name="arrival_location_id[]" id="arrival_location_id" class="form-control select2" multiple disabled>
                    <option value="">Select Factory</option>
                    @foreach($arrivalLocations as $factory)
                        <option value="{{ $factory->id }}" data-company="{{ $factory->company_location_id ?? '' }}" @selected(in_array($factory->id, $selectedFactories))>{{ $factory->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Section:</label>
                <select name="arrival_sub_location_id[]" id="arrival_sub_location_id" class="form-control select2" multiple disabled>
                    <option value="">Select Section</option>
                    @foreach($arrivalSubLocations as $section)
                        <option value="{{ $section->id }}" data-factory="{{ $section->arrival_location_id }}" @selected(in_array($section->id, $selectedSections))>{{ $section->name }}</option>
                    @endforeach
                </select>
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
                <textarea name="remarks" id="remarks" class="form-control" rows="2" readonly>{{ $sale_order->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        @foreach($sale_order->sales_order_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <select name="item_id[]" id="item_id_{{ $index }}" class="form-control select2" readonly>
                                        <option value="">Select Item</option>
                                        @foreach ($items ?? [] as $item)
                                            <option value="{{ $item->id }}" @selected($data->item_id == $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ bag_type_name($data->bag_type) }}" onkeyup="calc(this)" class="form-control qty" step="0.01"
                                        min="0" readonly>
                                </td>
                                  <td>
                                    <input type="text" name="bag_size[]" id="bag_type_{{ $index }}" value="{{ $data->bag_size }}" onkeyup="calc(this)" class="form-control qty" step="0.01"
                                        min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}" value="{{ $data->no_of_bags }}" class="form-control no_of_bags" readonly>
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}" value="{{ $data->qty }}" class="form-control qty" step="0.01"
                                        min="0" readonly>
                                </td>
                                <td>
                                    <input type="number" name="rate[]" id="rate_{{ $index }}" value="{{ $data->rate }}" onkeyup="calc(this)" class="form-control rate" step="0.01"
                                        min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="amount[]" id="amount_{{ $index }}" value="{{ $data->rate * $data->qty }}" class="form-control amount" readonly>
                                </td>

                                <td>
                                    <input type="text" name="brand_id[]" id="brand_id{{ $index }}" value="{{ getBrandById($data->brand_id)?->name }}" class="form-control brand_id" readonly>
                                </td>

                                <td style="display: none;">
                                    <input type="text" name="pack_size[]" value="0" id="pack_size{{ $index }}" value="{{ $data->pack_size }}" class="form-control pack_size" readonly>
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
<div class="row">
    <div class="col-12">
        <x-approval-status :model="$sale_order" />
    </div>
</div>

<script>
    salesInquiryRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
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
                <input type="number" name="qty[]" id="qty_${index}" onkeyup="calc(this)" class="form-control qty" step="0.01" min="0">
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
        success: function(res) {
            $("#inquiry_id").select2({
                data: res
            });
        },
        error: function(error) {
        
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
        success: function(res) {
            console.log("success");
            $("#alesInquiryBody").empty();
            $("#salesInquiryBody").html(res);
        },
        error: function(error) {
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
            success: function(res) {
                $("#reference_no").val(res.so_no)
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>
