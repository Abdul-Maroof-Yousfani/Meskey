<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
</style>

<form action="{{ route('export-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-order') }}" />

    <div class="row form-mar">
        <div class="col-8">
            <!-- Basic Information -->
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Basic Information</h6>
                <div class="row">
                    <div class="col-md-3">
                        <fieldset>
                            <label>Voucher No#</label>
                            <div class="input-group">
                                {{-- <div class="input-group-prepend">
                                    <button class="btn btn-primary" type="button">Voucher No#</button>
                                </div> --}}
                                <input type="text" readonly name="voucher_no" class="form-control">

                            </div>
                        </fieldset>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contract No#:</label>
                            <input type="text" name="contract_no" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Date:</label>
                            <input type="date" name="voucher_date" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Heading:</label>
                            <input type="text" name="voucher_heading" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name:</label>
                            <select name="buyer_id" class="form-control select2">
                                <option value="">Select Buyer</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Shipment Delivery Date From:</label>
                            <input type="date" name="shipment_delivery_date_from" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Shipment DeliveryDate To:</label>
                            <input type="date" name="shipment_delivery_date_to" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Marking/labeling:</label>
                            <input type="text" name="marking_labeling" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Company Locations:</label>
                            <select name="company_location_ids[]" id="companyLocationSelect"
                                class="form-control select2" multiple>
                                <option value="">Select Location</option>
                                @foreach ($companyLocations as $location)
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Arrival Locations:</label>
                            <select name="arrival_location_ids[]" id="arrivalLocationSelect"
                                class="form-control select2" multiple>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Arrival Sub Locations:</label>
                            <select name="arrival_sub_location_ids[]" id="arrivalSubLocationSelect"
                                class="form-control select2" multiple>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Selection -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>Commodity/Product:</label>
                    <select name="product_id" class="form-control select2" id="productSelect">
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Specifications Section -->
            <div class="col-md-12" id="specificationsSection" style="display: ;">
                <h6 class="header-heading-sepration">Specifications</h6>
                <div id="productSpecs">
                    <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                        <i class="ft-info mr-1"></i>
                        <strong>No specifications found!</strong> Please select a commodity/product first!
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label>Other Specification:</label>
                    <textarea name="other_specifications" class="form-control" rows="4"></textarea>
                </div>
            </div>

            {{-- bank details  --}}
            <div class="row">
                {{-- beneficiary --}}
                <div class="col-md-12">
                    <div class="p-3">
                        <h5 class="mb-3"><strong>Beneficiary Bank Details</strong></h5>
                        <div class="row">
                            {{-- Bank Selector (loaded dynamically based on selected Buyer) --}}
                            <div class="col-md-12 mb-2">
                                <label>Select Bank:</label>
                                <select name="bank_id" id="bankSelect" class="form-control select2">
                                    <option value="">-- Select Bank --</option>
                                </select>
                                <small class="text-muted">Select a Buyer first to see their bank accounts.</small>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account Title:</label>
                                <input type="text" id="acc_title" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Name:</label>
                                <input type="text" id="bank_name" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Name:</label>
                                <input type="text" id="branch_name" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Code:</label>
                                <input type="text" id="branch_code" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="account_no" class="form-control" disabled>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- correspondent --}}
                <div class="col-md-12">
                    <div class="p-3">
                        <h5 class="mb-3"><strong>Correspondent Bank Details</strong></h5>
                        <div class="row">
                            {{-- Correspondent Bank Selector --}}
                            <div class="col-md-12 mb-2">
                                <label>Select Correspondent Bank:</label>
                                <select name="correspondent_bank_id" id="correspondentBankSelect"
                                    class="form-control select2">
                                    <option value="">-- Select Bank --</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->account_title }} -
                                            {{ $bank->bank_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Auto Filled Fields --}}
                            <div class="col-md-6 mt-2">
                                <label>Account Title:</label>
                                <input type="text" id="cor_acc_title" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Name:</label>
                                <input type="text" id="cor_bank_name" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>IBAN:</label>
                                <input type="text" id="cor_iban" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="cor_account_no" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>SWIFT Code:</label>
                                <input type="text" id="cor_swift_code" class="form-control" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Address:</label>
                                <input type="text" id="cor_bank_address" class="form-control" disabled>
                            </div>

                            <div class="col-md-12 mt-2">
                                <label>Description:</label>
                                <textarea id="cor_description" class="form-control" rows="2" disabled></textarea>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- shipping instructions --}}
            <div class="col-md-12 mb-4">
                <label>Shipping Instruction:</label>
                <textarea name="shipping_instructions" id="shipping_instructions" class="form-control"></textarea>
            </div>

            {{-- broker --}}
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <label>Broker:</label>
                    <select name="broker_id" class="form-control select2">
                        <option value="">Select Broker</option>
                        @foreach ($brokers as $broker)
                            <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- doucments to be povided --}}
            <div class="col-md-12 mb-3">
                <label>Documents to be provided:</label>
                <textarea name="documents_to_be_provided" id="documents_to_be_provided" class="form-control"></textarea>
            </div>

            <div class="row p-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Other Condition:</label>
                        <textarea name="other_condition" id="other_condition" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Force Majure:</label>
                        <textarea name="force_majure" id="force_majure" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Application Law:</label>
                        <textarea name="application_law" id="application_law" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4">
            <h6 class="header-heading-sepration">Export</h6>
            <div class="table-responsive">
                <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">INCOTERMS</td>
                        <td style="width: 70%;">
                            <select name="incoterm_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($incoterms as $incoterm)
                                    <option value="{{ $incoterm->id }}">{{ $incoterm->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PACKING TYPE</td>
                        <td style="width: 70%;">
                            <select name="packing_type" class="form-control select2">
                                <option value="">Select</option>
                                <option value="In Conatiner">IN CONTAINER</option>
                                <option value="In Bulk">IN BULK</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TERM</td>
                        <td style="width: 70%;">
                            <select name="mode_of_term_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($modeofterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TRANSPORT</td>
                        <td style="width: 70%;">
                            <select name="mode_of_transport_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($modeoftransport as $transport)
                                    <option value="{{ $transport->id }}">{{ $transport->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">ORIGIN</td>
                        <td style="width: 70%;">
                            <select name="origin_country_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF DISCHARGE</td>
                        <td style="width: 70%;">
                            <select name="port_of_discharge_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}">{{ $port->name }},
                                        {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF LOADING</td>
                        <td style="width: 70%;">
                            <select name="port_of_loading_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}">{{ $port->name }},
                                        {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">HS CODE</td>
                        <td style="width: 70%;">
                            <select name="hs_code_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($hscodes as $hs)
                                    <option value="{{ $hs->id }}">{{ $hs->code }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PARTIAL PAYMENT</td>
                        <td style="width: 70%;">
                            <select name="partial_payment" class="form-control select2">
                                <option value="">Select</option>
                                <option value="Yes">YES</option>
                                <option value="No">NO</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">TRANSHIPMENT</td>
                        <td style="width: 70%;">
                            <select name="transhipment" class="form-control select2">
                                <option value="">Select</option>
                                <option value="shall be permitted">SHALL BE PERMITTED</option>
                                <option value="shall not be permitted">SHALL NOT BE PERMITTED</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PART SHIPMENT</td>
                        <td style="width: 70%;">
                            <select name="part_shipment" class="form-control select2">
                                <option value="">Select</option>
                                <option value="shall be permitted">SHALL BE PERMITTED</option>
                                <option value="shall not be permitted">SHALL NOT BE PERMITTED</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">INSURANCE COVERED BY</td>
                        <td style="width: 70%;">
                            <select name="insurance_covered_by" class="form-control select2">
                                <option value="">Select</option>
                                <option value="Buyer">BUYER</option>
                                <option value="Supplier">SUPPLIER</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">ADVANCE PAYMENT(%)</td>
                        <td style="width: 70%;">
                            <input type="number" name="advance_payment" class="form-control no-spin" max="100"
                                min="0" step="0.01">
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS(no of days)
                        </td>
                        <td style="width: 70%;">
                            <input type="text" name="payment_days" class="form-control">
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                        <td style="width: 70%;">
                            <select name="currency_id" id="currencySelect" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" data-rate="{{ $currency->rate }}">
                                        {{ $currency->currency_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">RATE</td>
                        <td style="width: 70%;">
                            <input type="text" name="currency_rate" id="currencyRate" class="form-control"
                                readonly>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">
                    <i class="ft-plus"></i> Add Item
                </button>
            </h6>

            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="packingTable">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Brand</th>
                            <th style="min-width: 150px;">Bag Type</th>
                            <th style="min-width: 130px;">Packing</th>
                            <th style="min-width: 130px;">Condition</th>
                            <th style="min-width: 110px;">Color</th>
                            <th style="min-width: 100px;">Size (kg)</th>
                            <th style="min-width: 100px;">Qty (MT)</th>
                            <th style="min-width: 100px;">Maunds</th>
                            <th style="min-width: 100px;">Bags</th>
                            <th style="min-width: 110px;">Total KGs</th>
                            <th style="min-width: 120px;">Stuffing (MT)</th>
                            <th style="min-width: 120px;">Stuffing (Mnd)</th>
                            <th style="min-width: 90px;">Containers</th>
                            <th style="min-width: 110px;">Rate/Ton</th>
                            <th style="min-width: 110px;">Rate/Mnd</th>
                            <th style="min-width: 130px;">Amount</th>
                            <th style="min-width: 130px;">Amount (PKR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        <tr class="packing-item">
                            <td class="p-2">
                                <select name="packing_items[0][brand_id]" class="form-control select2">
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select name="packing_items[0][bag_type_id]" class="form-control select2">
                                    <option value="">Select Bag Type</option>
                                    @foreach ($bagTypes as $bagType)
                                        <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select class="form-control select2" name="packing_items[0][bag_packing_id]">
                                    <option value="">Select Packing</option>
                                    @foreach ($bagPackings as $packing)
                                        <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select name="packing_items[0][bag_condition_id]" class="form-control select2">
                                    <option value="">Select Condition</option>
                                    @foreach ($bagConditions as $condition)
                                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select name="packing_items[0][bag_color_id]" class="form-control select2">
                                    <option value="">Select Color</option>
                                    @foreach ($bagColors as $color)
                                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size"
                                    step="0.01" value="0" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][metric_tons]"
                                    class="form-control metric-tons" value="0" step="0.001" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][maunds]"
                                    class="form-control maunds" value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][no_of_bags]" class="form-control no_of_bags"
                                    value="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs"
                                    value="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][stuffing_in_container]"
                                    class="form-control stuffing" value="0" step="0.001" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][stuffing_maunds]"
                                    class="form-control stuffing_maunds" value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][no_of_containers]"
                                    class="form-control containers" value="0" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][rate]" class="form-control rates"
                                    value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][rate_per_maund]" class="form-control rates_mnd"
                                    value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][amount]" class="form-control amount"
                                    value="0" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][amount_pkr]" class="form-control amount_pkr" 
                                    value="0" readonly>
                            </td>
                            <td class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                    <i class="ft-trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Export Order</button>
        </div>
    </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
    $(document).ready(function() {
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law').summernote({
            placeholder: 'Enter details here...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Initialize Summernote
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law').summernote({
            placeholder: 'Enter details here...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        // Initialize Select2
        $('.select2').select2({ width: '100%' });

        // Voucher Number based on date
        $('input[name="voucher_date"]').on('change', function() {
            let selectedDate = $(this).val();
            if (!selectedDate) {
                $('input[name="voucher_no"]').val('');
                return;
            }
            getUniversalNumber({
                table: 'export_orders',
                prefix: 'EXPORT',
                column: 'voucher_no',
                with_date: 1,
                custom_date: selectedDate,
                date_format: 'm-Y',
                serial_at_end: 1,
            }, function(no) {
                $('input[name="voucher_no"]').val(no);
            });
        });

        // Product selection change
        $('#productSelect').on('change', function() {
            var productId = $(this).val();
            if (productId) {
                $.get('{{ route('get.product_specs.export', '') }}/' + productId, function(data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#specificationsSection').hide();
            }
        });

        // Add more packing items
        $('#addPackingItem').click(function() {
            addNewPackingItem();
        });

        function addNewPackingItem() {
            var firstRow = $('#packingItems tr.packing-item').first();
            var newRow = firstRow.clone();
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            newRow.find('input').val(0);
            newRow.find('input[readonly]').val(0);
            newRow.find('select').val('').trigger('change');
            $('#packingItems').append(newRow);
            $('.select2').select2({ width: '100%' });
            reindexPackingItems();
        }

        $(document).on('click', '.remove-packing-item', function() {
            if ($('#packingItems tr.packing-item').length > 1) {
                $(this).closest('tr').remove();
                reindexPackingItems();
            } else {
                alert('At least one packing item is required.');
            }
        });

        // Conversion & Calculation Logic
        $(document).on('input', '.metric-tons', function() {
            let row = $(this).closest('tr');
            let mt = parseFloat($(this).val()) || 0;
            row.find('.maunds').val((mt * 25).toFixed(2));
            calculateTotals(row);
        });

        $(document).on('input', '.maunds', function() {
            let row = $(this).closest('tr');
            let mnd = parseFloat($(this).val()) || 0;
            row.find('.metric-tons').val((mnd / 25).toFixed(3));
            calculateTotals(row);
        });

        $(document).on('input', '.stuffing', function() {
            let row = $(this).closest('tr');
            let mt = parseFloat($(this).val()) || 0;
            row.find('.stuffing_maunds').val((mt * 25).toFixed(2));
            calculateContainers(row);
        });

        $(document).on('input', '.stuffing_maunds', function() {
            let row = $(this).closest('tr');
            let mnd = parseFloat($(this).val()) || 0;
            row.find('.stuffing').val((mnd / 25).toFixed(3));
            calculateContainers(row);
        });

        $(document).on('input', '.rates', function() {
            let row = $(this).closest('tr');
            let rateTon = parseFloat($(this).val()) || 0;
            row.find('.rates_mnd').val((rateTon / 25).toFixed(2));
            calculateAmount(row);
        });

        $(document).on('input', '.rates_mnd', function() {
            let row = $(this).closest('tr');
            let rateMnd = parseFloat($(this).val()) || 0;
            row.find('.rates').val((rateMnd * 25).toFixed(2));
            calculateAmount(row);
        });

        $(document).on('input', '.bag-size', function() {
            calculateTotals($(this).closest('tr'));
        });

        $(document).on('input', '.containers', function() {
            calculateStuffing($(this).closest('tr'));
        });

        function calculateStuffing(row) {
            var mt = parseFloat(row.find('.metric-tons').val()) || 0;
            var containers = parseInt(row.find('.containers').val()) || 0;
            if (containers > 0) {
                var stuffingMT = mt / containers;
                row.find('.stuffing').val(stuffingMT.toFixed(3));
                row.find('.stuffing_maunds').val((stuffingMT * 25).toFixed(2));
            }
        }

        function calculateContainers(row) {
            var mt = parseFloat(row.find('.metric-tons').val()) || 0;
            var stuffing = parseFloat(row.find('.stuffing').val()) || 0;
            if (stuffing > 0) {
                var containers = Math.ceil(mt / stuffing);
                row.find('.containers').val(containers);
            }
        }

        function calculateTotals(row) {
            let bagSize = parseFloat(row.find('.bag-size').val()) || 0;
            let mt = parseFloat(row.find('.metric-tons').val()) || 0;
            let totalKgs = mt * 1000;
            row.find('.total-kgs').val(totalKgs.toFixed(2));
            if (bagSize > 0) {
                row.find('.no_of_bags').val((totalKgs / bagSize).toFixed(0));
            } else {
                row.find('.no_of_bags').val(0);
            }
            calculateAmount(row);
        }

        function calculateAmount(row) {
            let rate = parseFloat(row.find('.rates').val()) || 0;
            let mt = parseFloat(row.find('.metric-tons').val()) || 0;
            let amount = rate * mt;
            row.find('.amount').val(amount.toFixed(2));
            let currencyRate = parseFloat($('#currencyRate').val()) || 1;
            row.find('.amount_pkr').val((amount * currencyRate).toFixed(2));
        }

        function reindexPackingItems() {
            $('#packingItems tr.packing-item').each(function(index) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }

        // Currency Rate Change
        $('#currencySelect').on('change', function() {
            let rate = $(this).find(':selected').data('rate') || '';
            $('#currencyRate').val(rate);
            $('.packing-item').each(function() {
                calculateAmount($(this));
            });
        });

        // Bank Details Section
        $('select[name="buyer_id"]').on('change', function() {
            let customerId = $(this).val();
            $('#bankSelect').html('<option value="">-- Select Bank --</option>').trigger('change');
            $('#acc_title, #bank_name, #branch_name, #branch_code, #account_no').val('');
            if (!customerId) return;
            $.get('{{ route('export-order.customer-banks', '') }}/' + customerId, function(response) {
                let options = '<option value="">-- Select Bank --</option>';
                response.forEach(function(bank) {
                    options += `<option value="${bank.id}" 
                        data-title="${bank.account_title}"
                        data-bank="${bank.bank_name}"
                        data-branch="${bank.branch_name}"
                        data-branch-code="${bank.branch_code}"
                        data-account="${bank.account_number}">
                        [${bank.type}] ${bank.account_title} - ${bank.bank_name}
                    </option>`;
                });
                $('#bankSelect').html(options).trigger('change');
            });
        });

        $('#bankSelect').on('change', function() {
            let selected = $(this).find(':selected');
            if (!selected.val()) {
                $('#acc_title, #bank_name, #branch_name, #branch_code, #account_no').val('');
                return;
            }
            $('#acc_title').val(selected.data('title') || '');
            $('#bank_name').val(selected.data('bank') || '');
            $('#branch_name').val(selected.data('branch') || '');
            $('#branch_code').val(selected.data('branch-code') || '');
            $('#account_no').val(selected.data('account') || '');
        });

        $('#correspondentBankSelect').on('change', function() {
            let bankId = $(this).val();
            if (!bankId) {
                $('#cor_acc_title, #cor_bank_name, #cor_iban, #cor_account_no, #cor_swift_code, #cor_bank_address, #cor_description').val('');
                return;
            }
            $.get('/export/get-bank-details/' + bankId, function(bank) {
                $('#cor_acc_title').val(bank.account_title);
                $('#cor_bank_name').val(bank.bank_name);
                $('#cor_iban').val(bank.iban);
                $('#cor_account_no').val(bank.account_no);
                $('#cor_swift_code').val(bank.swift_code);
                $('#cor_bank_address').val(bank.bank_address);
                $('#cor_description').val(bank.description);
            });
        });

        // Arrival Locations Logic
        $('#companyLocationSelect').on('change', function() {
            let companyLocationIds = $(this).val();
            $('#arrivalLocationSelect').empty().trigger('change');
            if (!companyLocationIds || companyLocationIds.length === 0) return;
            $.post('/export/get-arrival-locations', {
                company_location_ids: companyLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(location) {
                    options += `<option value="${location.id}">${location.name}</option>`;
                });
                $('#arrivalLocationSelect').html(options).trigger('change');
            });
        });

        $('#arrivalLocationSelect').on('change', function() {
            let arrivalLocationIds = $(this).val();
            $('#arrivalSubLocationSelect').empty().trigger('change');
            if (!arrivalLocationIds || arrivalLocationIds.length === 0) return;
            $.post('/export/get-arrival-sub-locations', {
                arrival_location_ids: arrivalLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(sublocation) {
                    options += `<option value="${sublocation.id}">${sublocation.name}</option>`;
                });
                $('#arrivalSubLocationSelect').html(options).trigger('change');
            });
        });

    });
</script>

