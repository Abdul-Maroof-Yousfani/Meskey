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

<form action="{{ route('proforma.store', $exportOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.proforma') }}" />

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
                                <input type="text" name="voucher_no" class="form-control"
                                    value="{{ old('voucher_no', $exportOrder->voucher_no) }}" disabled>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contract No#:</label>
                            <input type="text" name="contract_no" class="form-control"
                                value="{{ old('contract_no', $exportOrder->contract_no) }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Date:</label>
                            <input type="date" name="voucher_date" class="form-control"
                                value="{{ old('contract_no', $exportOrder->voucher_date) }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Heading:</label>
                            <input type="text" name="voucher_heading" class="form-control"
                                value="{{ old('voucher_heading', $exportOrder->voucher_heading) }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name:</label>
                            <select name="buyer_id" class="form-control select2" disabled>
                                <option value="">Select Buyer</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('buyer_id', $exportOrder->buyer_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Shipment Delivery Date From:</label>
                            <input type="date" name="shipment_delivery_date_from" class="form-control"
                                value="{{ old('shipment_delivery_date_from', $exportOrder->shipment_delivery_date_from) }}"
                                disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Shipment DeliveryDate To:</label>
                            <input type="date" name="shipment_delivery_date_to" class="form-control"
                                value="{{ old('shipment_delivery_date_to', $exportOrder->shipment_delivery_date_to) }}"
                                disabled>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Marking/labeling:</label>
                            <input type="text" name="marking_labeling" class="form-control"
                                value="{{ old('marking_labeling', $exportOrder->marking_labeling) }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Company Locations:</label>
                            <select name="company_location_ids[]" id="companyLocationSelect"
                                class="form-control select2" multiple disabled>
                                @foreach ($companyLocations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ in_array($location->id, $exportOrder->company_location_ids ?? []) ? 'selected' : '' }}>
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
                                class="form-control select2" multiple disabled>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Arrival Sub Locations:</label>
                            <select name="arrival_sub_location_ids[]" id="arrivalSubLocationSelect"
                                class="form-control select2" multiple disabled>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Selection -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>Commodity/Product:</label>
                    <select name="product_id" class="form-control select2" id="productSelect" disabled>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                {{ old('product_id', $exportOrder->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Specifications Section -->
            <div class="col-md-12" id="specificationsSection"
                style="display: {{ $exportOrder->specifications->count() ? 'block' : 'none' }};">
                <h6 class="header-heading-sepration">Specifications</h6>
                <div id="productSpecs">
                    @if ($exportOrder->specifications->count())
                        <div class="specifications-table">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="40%">Specification Name</th>
                                            <th width="30%">Value</th>
                                            <th width="30%">UOM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($exportOrder->specifications as $index => $spec)
                                            <tr>
                                                <td>
                                                    <strong>{{ $spec->spec_name }}</strong>
                                                    <input type="hidden"
                                                        name="specifications[{{ $index }}][product_slab_type_id]"
                                                        value="{{ $spec->product_slab_type_id }}">
                                                    <input type="hidden"
                                                        name="specifications[{{ $index }}][spec_name]"
                                                        value="{{ $spec->spec_name }}">
                                                    <input type="hidden"
                                                        name="specifications[{{ $index }}][uom]"
                                                        value="{{ $spec->uom }}">
                                                </td>
                                                <td>
                                                    <fieldset>
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="specifications[{{ $index }}][spec_value]"
                                                                value="{{ $spec->spec_value ?? 0 }}"
                                                                class="form-control form-control-sm spec-value-input"
                                                                placeholder="Enter value" disabled>
                                                            <div class="input-group-prepend">
                                                                <button class="btn btn-secondary"
                                                                    type="button">{{ $spec->productSlabType->qc_symbol ?? 'N/A' }}</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </td>
                                                <td>
                                                    <select name="specifications[{{ $index }}][value_type]"
                                                        class="form-control" disabled>
                                                        <option {{ $spec->value_type == 'min' ? 'selected' : '' }}
                                                            value="min">Minimum</option>
                                                        <option {{ $spec->value_type == 'max' ? 'selected' : '' }}
                                                            value="max">Maximum</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                            <i class="ft-info mr-1"></i>
                            <strong>No specifications found!</strong> Please select a commodity first!
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label>Other Specification:</label>
                    <textarea name="other_specifications" class="form-control" rows="4" disabled>{{ old('other_specifications', $exportOrder->other_specifications) }}</textarea>
                </div>
            </div>

            {{-- bank details  --}}
            <div class="row">
                {{-- beneficiary --}}
                <div class="col-md-12">
                    <div class="p-3">
                        <h5 class="mb-3"><strong>Beneficiary Bank Details</strong></h5>
                        <div class="row">
                            {{-- Bank Display --}}
                            <div class="col-md-12 mb-2">
                                <label>Beneficiary Bank:</label>
                                <input type="text" class="form-control" value="{{ $exportOrder->customerBank ? $exportOrder->customerBank->bank_name . ' (' . ($exportOrder->customer_bank_type == 'owner' ? 'Owner' : 'Company') . ')' : 'N/A' }}" disabled>
                                <input type="hidden" name="customer_bank_id" value="{{ $exportOrder->customer_bank_id }}">
                                <input type="hidden" name="customer_bank_type" value="{{ $exportOrder->customer_bank_type }}">
                            </div>

                            {{-- Auto Filled Fields --}}
                            <div class="col-md-6 mt-2">
                                <label>Account Title:</label>
                                <input type="text" id="acc_title" class="form-control" value="{{ $exportOrder->customerBank->account_title ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Name:</label>
                                <input type="text" id="bank_name" class="form-control" value="{{ $exportOrder->customerBank->bank_name ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Name:</label>
                                <input type="text" id="branch_name" class="form-control" value="{{ $exportOrder->customerBank->branch_name ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="account_no" class="form-control" value="{{ $exportOrder->customerBank->account_number ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Code:</label>
                                <input type="text" id="branch_code" class="form-control" value="{{ $exportOrder->customerBank->branch_code ?? '' }}" disabled>
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
                                    class="form-control select2" disabled>
                                    <option value="">-- Select Bank --</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}"
                                            {{ old('correspondent_bank_id', $exportOrder->correspondent_bank_id) == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->account_title }} - {{ $bank->bank_name }}
                                        </option>
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

            {{-- consignee details  --}}
            <div class="col-md-12">
                <div class="form-group">
                    <label>Consignee Details:</label>
                    <textarea name="consigned_details" class="form-control" rows="4"></textarea>
                </div>
            </div>

            {{-- shipping instructions --}}
            <div class="col-md-12 mb-4">
                <label>Shipping Instruction:</label>
                <textarea name="shipping_instructions" id="shipping_instructions" class="form-control">{{ old('shipping_instructions', $exportOrder->shipping_instructions) }}</textarea>
            </div>

            {{-- broker --}}
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <label>Broker:</label>
                    <select name="broker_id" class="form-control select2" disabled>
                        <option value="">Select Broker</option>
                        @foreach ($brokers as $broker)
                            <option value="{{ $broker->id }}"
                                {{ old('broker_id', $exportOrder->broker_id) == $broker->id ? 'selected' : '' }}>
                                {{ $broker->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- doucments to be povided --}}
            <div class="col-md-12 mb-3">
                <label>Documents to be provided:</label>
                <textarea name="documents_to_be_provided" id="documents_to_be_provided" class="form-control">{{ old('documents_to_be_provided', $exportOrder->documents_to_be_provided) }}</textarea>
            </div>

            <div class="row p-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Other Condition:</label>
                        <textarea name="other_condition" class="form-control" rows="3" disabled>{{ old('other_condition', $exportOrder->other_condition) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Force Majure:</label>
                        <textarea name="force_majure" class="form-control" rows="3" disabled>{{ old('force_majure', $exportOrder->force_majure) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Application Law:</label>
                        <textarea name="application_law" class="form-control" rows="3" disabled>{{ old('application_law', $exportOrder->application_law) }}</textarea>
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
                            <select name="incoterm_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($incoterms as $incoterm)
                                    <option value="{{ $incoterm->id }}"
                                        {{ old('incoterm_id', $exportOrder->incoterm_id) == $incoterm->id ? 'selected' : '' }}>
                                        {{ $incoterm->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PACKING TYPE</td>
                        <td style="width: 70%;">
                            <select name="packing_type" class="form-control select2" disabled>
                                <option value="">Select</option>
                                <option value="In Conatiner"
                                    {{ old('packing_type', $exportOrder->packing_type) == 'In Conatiner' ? 'selected' : '' }}>
                                    IN CONTAINER</option>
                                <option value="In Bulk"
                                    {{ old('packing_type', $exportOrder->packing_type) == 'In Bulk' ? 'selected' : '' }}>
                                    IN BULK</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TERM</td>
                        <td style="width: 70%;">
                            <select name="mode_of_term_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($modeofterms as $term)
                                    <option value="{{ $term->id }}"
                                        {{ old('mode_of_term_id', $exportOrder->mode_of_term_id) == $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TRANSPORT</td>
                        <td style="width: 70%;">
                            <select name="mode_of_transport_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($modeoftransport as $transport)
                                    <option value="{{ $transport->id }}"
                                        {{ old('mode_of_transport_id', $exportOrder->mode_of_transport_id) == $transport->id ? 'selected' : '' }}>
                                        {{ $transport->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">ORIGIN</td>
                        <td style="width: 70%;">
                            <select name="origin_country_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}"
                                        {{ old('origin_country_id', $exportOrder->origin_country_id) == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF DISCHARGE</td>
                        <td style="width: 70%;">
                            <select name="port_of_discharge_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}"
                                        {{ old('port_of_discharge_id', $exportOrder->port_of_discharge_id) == $port->id ? 'selected' : '' }}>
                                        {{ $port->name }},
                                        {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF LOADING</td>
                        <td style="width: 70%;">
                            <select name="port_of_loading_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}"
                                        {{ old('port_of_loading_id', $exportOrder->port_of_loading_id) == $port->id ? 'selected' : '' }}>
                                        {{ $port->name }},
                                        {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">HS CODE</td>
                        <td style="width: 70%;">
                            <select name="hs_code_id" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($hscodes as $hs)
                                    <option value="{{ $hs->id }}"
                                        {{ old('hs_code_id', $exportOrder->hs_code_id) == $hs->id ? 'selected' : '' }}>
                                        {{ $hs->code }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PARTIAL PAYMENT</td>
                        <td style="width: 70%;">
                            <select name="partial_payment" class="form-control select2" disabled>
                                <option value="">Select</option>
                                <option value="Yes"
                                    {{ old('partial_payment', $exportOrder->partial_payment) == 'Yes' ? 'selected' : '' }}>
                                    YES</option>
                                <option value="No"
                                    {{ old('partial_payment', $exportOrder->partial_payment) == 'No' ? 'selected' : '' }}>
                                    NO</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">TRANSHIPMENT</td>
                        <td style="width: 70%;">
                            <select name="transhipment" class="form-control select2" disabled>
                                <option value="">Select</option>
                                <option value="shall be permitted"
                                    {{ old('transhipment', $exportOrder->transhipment) == 'shall be permitted' ? 'selected' : '' }}>
                                    SHALL BE PERMITTED</option>
                                <option value="shall not be permitted"
                                    {{ old('transhipment', $exportOrder->transhipment) == 'shall not be permitted' ? 'selected' : '' }}>
                                    SHALL NOT BE PERMITTED</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PART SHIPMENT</td>
                        <td style="width: 70%;">
                            <select name="part_shipment" class="form-control select2" disabled>
                                <option value="">Select</option>
                                <option value="shall be permitted"
                                    {{ old('part_shipment', $exportOrder->part_shipment) == 'shall be permitted' ? 'selected' : '' }}>
                                    SHALL BE PERMITTED</option>
                                <option value="shall not be permitted"
                                    {{ old('part_shipment', $exportOrder->part_shipment) == 'shall not be permitted' ? 'selected' : '' }}>
                                    SHALL NOT BE PERMITTED</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">INSURANCE COVERED BY</td>
                        <td style="width: 70%;">
                            <select name="insurance_covered_by" class="form-control select2" disabled>
                                <option value="">Select</option>
                                <option value="Buyer"
                                    {{ old('insurance_covered_by', $exportOrder->insurance_covered_by) == 'Buyer' ? 'selected' : '' }}>
                                    BUYER</option>
                                <option value="Supplier"
                                    {{ old('insurance_covered_by', $exportOrder->insurance_covered_by) == 'Supplier' ? 'selected' : '' }}>
                                    SUPPLIER</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">ADVANCE PAYMENT(%)</td>
                        <td style="width: 70%;">
                            <input type="number" name="advance_payment" class="form-control no-spin" max="100"
                                min="0" step="0.01"
                                value="{{ old('advance_payment', $exportOrder->advance_payment) }}" disabled>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS(no of days)
                        </td>
                        <td style="width: 70%;">
                            <input type="text" name="payment_days" class="form-control"
                                value="{{ old('payment_days', $exportOrder->payment_days) }}" disabled>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                        <td style="width: 70%;">
                            <select name="currency_id" id="currencySelect" class="form-control select2" disabled>
                                <option value="">Select</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" data-rate="{{ $currency->rate }}"
                                        {{ old('currency_id', $exportOrder->currency_id) == $currency->id ? 'selected' : '' }}>
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
                                disabled value="{{ old('currency_rate', $exportOrder->currency_rate) }}" disabled>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                {{-- <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button> --}}
            </h6>

            <div class="table-responsive">
                <table class="table table-bordered">
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
                            {{-- <th>Action</th> --}}
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        @foreach ($exportOrder->packingItems as $index => $item)
                        <tr class="packing-item">
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][brand_id]" class="form-control select2" disabled>
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ $brand->id == $item->brand_id ? 'selected' : '' }}>
                                        {{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[{{ $index }}][brand_id]" value="{{ $item->brand_id }}">
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_type_id]" class="form-control select2" disabled>
                                    <option value="">Select Bag Type</option>
                                    @foreach ($bagTypes as $bagType)
                                    <option value="{{ $bagType->id }}" {{ $bagType->id == $item->bag_type_id ? 'selected' : '' }}>
                                        {{ $bagType->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[{{ $index }}][bag_type_id]" value="{{ $item->bag_type_id }}">
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_packing_id]" class="form-control" disabled>
                                    <option value="">Select Bag Packing</option>
                                    @foreach ($bagPackings as $packing)
                                    <option value="{{ $packing->id }}" {{ $packing->id == $item->bag_packing_id ? 'selected' : '' }}>
                                        {{ $packing->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[{{ $index }}][bag_packing_id]" value="{{ $item->bag_packing_id }}">
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_condition_id]" class="form-control select2" disabled>
                                    <option value="">Select Condition</option>
                                    @foreach ($bagConditions as $condition)
                                    <option value="{{ $condition->id }}" {{ $condition->id == $item->bag_condition_id ? 'selected' : '' }}>
                                        {{ $condition->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[{{ $index }}][bag_condition_id]" value="{{ $item->bag_condition_id }}">
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_color_id]" class="form-control select2" disabled>
                                    <option value="">Select Color</option>
                                    @foreach ($bagColors as $color)
                                    <option value="{{ $color->id }}" {{ $color->id == $item->bag_color_id ? 'selected' : '' }}>
                                        {{ $color->color }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[{{ $index }}][bag_color_id]" value="{{ $item->bag_color_id }}">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][bag_size]" class="form-control bag-size"
                                    step="0.01" value="{{ $item->bag_size }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][metric_tons]"
                                    class="form-control metric-tons" step="0.001" value="{{ $item->metric_tons }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][maunds]" class="form-control maunds"
                                    step="0.001" value="{{ $item->maunds ?? $item->metric_tons * 25 }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][no_of_bags]"
                                    class="form-control no_of_bags" value="{{ $item->no_of_bags }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][total_kgs]" class="form-control total-kgs"
                                    value="{{ $item->total_kgs }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][stuffing_in_container]"
                                    class="form-control stuffing" value="{{ $item->stuffing_in_container }}" step="0.001"
                                    min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][stuffing_maunds]"
                                    class="form-control stuffing_maunds" step="0.001"
                                    value="{{ $item->stuffing_maunds ?? $item->stuffing_in_container * 25 }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][no_of_containers]"
                                    class="form-control containers" value="{{ $item->no_of_containers }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][rate]" class="form-control rates"
                                    value="{{ $item->rate }}" step="0.01" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][rate_per_maund]"
                                    class="form-control rates_mnd" value="{{ $item->rate_per_maund ?? $item->rate / 25 }}"
                                    step="0.01" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][amount]" class="form-control amount"
                                    value="{{ $item->amount }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][amount_pkr]"
                                    class="form-control amount_pkr" value="{{ $item->amount_pkr }}" min="0" readonly>
                            </td>
                            {{-- <td class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                    <i class="ft-trash-2"></i>
                                </button>
                            </td> --}}
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Proforma</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2();

        // Initialize Summernote (Readonly mode as per requirement)
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #other_specifications').summernote({
            tabsize: 2,
            height: 200,
            toolbar: [],
            airMode: false
        }).summernote('disable');

        // Product selection change
        $('#productSelect').change(function() {
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

        // Functions for Calculations
        function calculateFields(item) {
            let metricTons = parseFloat($(item).find('.metric-tons').val()) || 0;
            let bagSize = parseFloat($(item).find('.bag-size').val()) || 0;
            let stuffingMT = parseFloat($(item).find('.stuffing').val()) || 0;
            let rateTon = parseFloat($(item).find('.rates').val()) || 0;
            let currencyRate = parseFloat($('#currencyRate').val()) || 0;

            // MT to Maunds (1 MT = 25 Maunds)
            let maunds = metricTons * 25;
            $(item).find('.maunds').val(maunds.toFixed(3));

            // Stuffing MT to Maunds
            if (stuffingMT > 0) {
                $(item).find('.stuffing_maunds').val((stuffingMT * 25).toFixed(3));
            }

            // Total KGs
            let totalKgs = metricTons * 1000;
            $(item).find('.total-kgs').val(totalKgs.toFixed(2));

            // No of Bags
            if (bagSize > 0) {
                $(item).find('.no_of_bags').val(Math.round(totalKgs / bagSize));
            }

            // Rate/Ton to Rate/Maund
            $(item).find('.rates_mnd').val((rateTon / 25).toFixed(2));

            // Amount
            let amount = metricTons * rateTon;
            $(item).find('.amount').val(amount.toFixed(2));

            // Amount PKR
            if (currencyRate > 0) {
                $(item).find('.amount_pkr').val((amount * currencyRate).toFixed(2));
            }

            // Containers
            if (stuffingMT > 0) {
                $(item).find('.containers').val(Math.ceil(metricTons / stuffingMT));
            }
        }

        // Event Listeners for Calculations
        $(document).on('input', '.metric-tons, .bag-size, .stuffing, .rates', function() {
            calculateFields($(this).closest('.packing-item'));
        });

        $(document).on('input', '.maunds', function() {
            let item = $(this).closest('.packing-item');
            let maunds = parseFloat($(this).val()) || 0;
            let mt = maunds / 25;
            item.find('.metric-tons').val(mt.toFixed(3));
            calculateFields(item);
        });

        $(document).on('input', '.rates_mnd', function() {
            let item = $(this).closest('.packing-item');
            let rateMnd = parseFloat($(this).val()) || 0;
            item.find('.rates').val((rateMnd * 25).toFixed(2));
            calculateFields(item);
        });

        // Add Packing Item
        $('#addPackingItem').click(function() {
            let lastRow = $('#packingItems tr:last');
            let newRow = lastRow.clone();
            
            // Clear inputs and reset indexes
            let index = $('#packingItems tr').length;
            newRow.find('input').val('');
            newRow.find('select').val('').trigger('change');
            
            newRow.find('input, select').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                }
            });

            $('#packingItems').append(newRow);
            newRow.find('.select2').select2();
        });

        // Remove Packing Item
        $(document).on('click', '.remove-packing-item', function() {
            if ($('#packingItems tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        // Bank and Location Logic
        function loadBankDetails(bankId, prefix = '') {
            if (!bankId) return;
            $.get('/export/get-bank-details/' + bankId, function(bank) {
                $(`#${prefix}acc_title`).val(bank.account_title);
                $(`#${prefix}bank_name`).val(bank.bank_name);
                $(`#${prefix}iban`).val(bank.iban || '');
                $(`#${prefix}account_no`).val(bank.account_number || bank.account_no);
                $(`#${prefix}swift_code`).val(bank.swift_code || '');
                $(`#${prefix}bank_address`).val(bank.bank_address || '');
                $(`#${prefix}description`).val(bank.description || '');
            });
        }

        $('#bankSelect').on('change', function() {
            loadBankDetails($(this).val());
        });

        $('#correspondentBankSelect').on('change', function() {
            loadBankDetails($(this).val(), 'cor_');
        });

        if ($('#bankSelect').val()) {
            loadBankDetails($('#bankSelect').val());
        }

        if ($('#correspondentBankSelect').val()) {
            loadBankDetails($('#correspondentBankSelect').val(), 'cor_');
        }

        // Currency Rate
        $('#currencySelect').on('change', function() {
            $('#currencyRate').val($(this).find(':selected').data('rate') || '');
            $('.packing-item').each(function() { calculateFields(this); });
        });

        // Location Logic
        function populateArrivalLocations(companyLocationIds, selectedIds = [], selectedSubIds = []) {
            if (!companyLocationIds || companyLocationIds.length === 0) return;
            $.post('/export/get-arrival-locations', {
                company_location_ids: companyLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(location) {
                    options += `<option value="${location.id}" ${selectedIds.includes(String(location.id)) ? 'selected' : ''}>${location.name}</option>`;
                });
                $('#arrivalLocationSelect').html(options).trigger('change');
                if (selectedIds.length > 0) populateArrivalSubLocations(selectedIds, selectedSubIds);
            });
        }

        function populateArrivalSubLocations(arrivalLocationIds, selectedIds = []) {
            if (!arrivalLocationIds || arrivalLocationIds.length === 0) return;
            $.post('/export/get-arrival-sub-locations', {
                arrival_location_ids: arrivalLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(sub) {
                    options += `<option value="${sub.id}" ${selectedIds.includes(String(sub.id)) ? 'selected' : ''}>${sub.name}</option>`;
                });
                $('#arrivalSubLocationSelect').html(options).trigger('change');
            });
        }

        // Initial Location Population
        $('#companyLocationSelect').on('change', function() {
            populateArrivalLocations($(this).val(), [], []);
        });

        $('#arrivalLocationSelect').on('change', function() {
            populateArrivalSubLocations($(this).val(), []);
        });

        let selectedArrival = @json($exportOrder->arrival_location_ids ?? []).map(String);
        let selectedSub = @json($exportOrder->arrival_sub_location_ids ?? []).map(String);
        populateArrivalLocations($('#companyLocationSelect').val(), selectedArrival, selectedSub);
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
    $(document).ready(function() {

        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #other_specifications').summernote({
            tabsize: 2,
            height: 200,
            toolbar: [],
            airMode: false
        });

        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #other_specifications').summernote('disable');

    });
</script>
