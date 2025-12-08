<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.sales-inquiry.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-inquiry.list') }}" />
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Inquiry Number:</label>
                <input type="text" name="reference_no" id="reference_no" class="form-control" readonly>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference Number:</label>
                <input type="text" name="reference_number" id="reference_number" class="form-control">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Date:</label>
                <input type="date" name="inquiry_date" onchange="getNumber()" id="inquiry_date" class="form-control">
            </div>
        </div>
    </div>
    <div class="row">

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Required Date:</label>
                <input type="date" name="required_date" id="required_date" class="form-control">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                <select name="customer" id="customer" class="form-control select2">
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contract Type:</label>
                <select name="contract_type" id="contract_type" class="form-control select2">
                    <option value="">Select Contract Type</option>
                    <option value="thadda">Thadda</option>
                    <option value="pohanch">Pohanch</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mt-3">
            <div class="form-group">
                <label class="form-label">Contact Person:</label>
                <input type="text" name="contact_person" id="contact_person" class="form-control">
            </div>
        </div>

        <div class="col-md-4 mt-3">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="locations[]" id="locations" class="form-control select2" multiple>
                    @foreach (get_locations() as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks:</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>

    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn">
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
                            <th>Quantity (Kg)</th>
                            <th>Rate</th>
                            <th>Brands</th>
                            <th style="display: none">Pack Size</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        <tr id="row_0">
                            <td>
                                <select name="item_id[]" id="item_id_0" class="form-control select2">
                                    <option value="">Select Item</option>
                                    @foreach ($items ?? [] as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="bag_type[]" id="bag_type_0" class="form-control select2">
                                    <option value="">Select Bag Type</option>
                                    @foreach ($bag_types ?? [] as $bag_type)
                                        <option value="{{ $bag_type->id }}">{{ $bag_type->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="bag_size[]" id="bag_size_0"
                                    class="form-control bag_size" onkeyup="calc(this)" step="0.01"
                                    min="0">
                            </td>
                            <td>
                                <input type="text" name="no_of_bags[]" id="no_of_bags_0"
                                    class="form-control no_of_bags" onkeyup="calc(this)" step="0.01"
                                    min="0">
                            </td>
                            <td>
                                <input type="number" name="qty[]" id="qty_0" class="form-control qty"
                                    step="0.01" min="0" readonly>
                            </td>
                            <td>
                                <input type="number" name="rate[]" id="rate_0" class="form-control"
                                    step="0.01" min="0">
                            </td>
                            <td>
                                <select name="brand_id[]" id="brand_id_0" class="form-control select2">
                                    <option value="">Select Brand</option>
                                    @foreach (getAllBrands() ?? [] as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="display: none;">
                                <input type="text" value="0" name="pack_size[]" id="pack_size_0"
                                    class="form-control" step="0.01" min="0">
                            </td>
                            <td>
                                <input type="text" name="desc[]" id="desc_0" class="form-control">
                            </td>
                            <td>
                                <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn"
                                    style="width:60px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
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
    salesInquiryRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
    });

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");

        if (!(bag_size.val() && no_of_bags.val())) return;

        const result = parseFloat(bag_size.val()) * parseFloat(no_of_bags.val());

        qty.val(result);
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
                <input type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="rate[]" id="rate_${index}" class="form-control" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="desc[]" id="desc_${index}" class="form-control">
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


    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-number') }}",
            method: "GET",
            data: {
                contract_date: $("#inquiry_date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#reference_no").val(res.inquiry_no)
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>
