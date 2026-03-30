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

<form action="{{ route('export-order.update', $exportOrder->id) }}" method="POST" id="ajaxSubmit2" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-order') }}" />

    <div class="row form-mar">
        <div class="col-8">
            <!-- Basic Information -->
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Basic Information</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Quotation#:</label>
                            <select name="quotation_id" class="form-control select2" readonly>
                                <option value="">Select Quotation</option>
                                @foreach ($quotations as $quotation)
                                    <option value="{{ $quotation->id }}" {{ old('quotation_id', $exportOrder->quotation_id) == $quotation->id ? 'selected' : '' }}>#{{ $quotation->id }} - {{ $quotation->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <fieldset>
                            <label>Sauda#:</label>
                            <select name="export_soda_id" class="form-control select2" readonly>
                                <option value="">Select Sauda</option>
                                @foreach ($exportSodas as $soda)
                                    <option value="{{ $soda->id }}" {{ old('export_soda_id', $exportOrder->export_soda_id) == $soda->id ? 'selected' : '' }}>#{{ $soda->id }} - {{ $soda->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </fieldset>
                    </div>
                    <div class="col-md-4">
                        <fieldset>
                            <label>Voucher No#:</label>
                            <div class="input-group">
                                <input type="text" name="voucher_no" class="form-control"
                                    value="{{ old('voucher_no', $exportOrder->voucher_no) }}" readonly>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contract No#:</label>
                            <input type="text" name="contract_no" class="form-control"
                                value="{{ old('contract_no', $exportOrder->contract_no) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Date:</label>
                            <input type="date" name="voucher_date" class="form-control"
                                value="{{ old('contract_no', $exportOrder->voucher_date) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Voucher Heading:</label>
                            <input type="text" name="voucher_heading" class="form-control"
                                value="{{ old('voucher_heading', $exportOrder->voucher_heading) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name:</label>
                            <select name="buyer_id" class="form-control select2" readonly>
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
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Shipment DeliveryDate To:</label>
                            <input type="date" name="shipment_delivery_date_to" class="form-control"
                                value="{{ old('shipment_delivery_date_to', $exportOrder->shipment_delivery_date_to) }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Marking/labeling:</label>
                            <input type="text" name="marking_labeling" class="form-control"
                                value="{{ old('marking_labeling', $exportOrder->marking_labeling) }}" readonly>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Product Selection -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>Commodity/Product:</label>
                    <select name="product_id" class="form-control select2" id="productSelect" readonly>
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
                                                                placeholder="Enter value" readonly>
                                                            <div class="input-group-prepend">
                                                                <button class="btn btn-secondary"
                                                                    type="button">{{ $spec->productSlabType->qc_symbol ?? 'N/A' }}</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </td>
                                                <td>
                                                    <select name="specifications[{{ $index }}][value_type]"
                                                        class="form-control" readonly>
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
                    <textarea name="other_specifications" class="form-control" rows="4" readonly>{{ old('other_specifications', $exportOrder->other_specifications) }}</textarea>
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
                                <input type="text" class="form-control" value="{{ $exportOrder->customerBank ? $exportOrder->customerBank->bank_name . ' (' . ($exportOrder->customer_bank_type == 'owner' ? 'Owner' : 'Company') . ')' : 'N/A' }}" readonly>
                            </div>

                            {{-- Auto Filled Fields --}}
                            <div class="col-md-6 mt-2">
                                <label>Account Title:</label>
                                <input type="text" id="acc_title" class="form-control" value="{{ $exportOrder->customerBank->account_title ?? '' }}" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Name:</label>
                                <input type="text" id="bank_name" class="form-control" value="{{ $exportOrder->customerBank->bank_name ?? '' }}" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Name:</label>
                                <input type="text" id="branch_name" class="form-control" value="{{ $exportOrder->customerBank->branch_name ?? '' }}" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="account_no" class="form-control" value="{{ $exportOrder->customerBank->account_number ?? '' }}" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Branch Code:</label>
                                <input type="text" id="branch_code" class="form-control" value="{{ $exportOrder->customerBank->branch_code ?? '' }}" readonly>
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
                                    class="form-control select2" readonly>
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
                                <input type="text" id="cor_acc_title" class="form-control" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Name:</label>
                                <input type="text" id="cor_bank_name" class="form-control" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>IBAN:</label>
                                <input type="text" id="cor_iban" class="form-control" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="cor_account_no" class="form-control" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>SWIFT Code:</label>
                                <input type="text" id="cor_swift_code" class="form-control" readonly>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Bank Address:</label>
                                <input type="text" id="cor_bank_address" class="form-control" readonly>
                            </div>

                            <div class="col-md-12 mt-2">
                                <label>Description:</label>
                                <textarea id="cor_description" class="form-control" rows="2" readonly></textarea>
                            </div>
                        </div>

                    </div>
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
                    <select name="broker_id" class="form-control select2" readonly>
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
                        <textarea name="other_condition" id="other_condition" class="form-control" rows="3" readonly>{!! $exportOrder->other_condition !!}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Force Majure:</label>
                        <textarea name="force_majure" id="force_majure" class="form-control" rows="3" readonly>{!! $exportOrder->force_majure !!}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Application Law:</label>
                        <textarea name="application_law" id="application_law" class="form-control" rows="3" readonly>{!! $exportOrder->application_law !!}</textarea>
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
                            <select name="incoterm_id" class="form-control select2" readonly>
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
                            <select name="packing_type" class="form-control select2" readonly>
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
                            <select name="mode_of_term_id" class="form-control select2" readonly>
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
                            <select name="mode_of_transport_id" class="form-control select2" readonly>
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
                            <select name="origin_country_id" class="form-control select2" readonly>
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
                            <select name="port_of_discharge_id" class="form-control select2" readonly>
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
                            <select name="port_of_loading_id" class="form-control select2" readonly>
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
                            <select name="hs_code_id" class="form-control select2" readonly>
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
                            <select name="partial_payment" class="form-control select2" readonly>
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
                            <select name="transhipment" class="form-control select2" readonly>
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
                            <select name="part_shipment" class="form-control select2" readonly>
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
                            <select name="insurance_covered_by" class="form-control select2" readonly>
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
                                value="{{ old('advance_payment', $exportOrder->advance_payment) }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS(no of days)
                        </td>
                        <td style="width: 70%;">
                            <input type="text" name="payment_days" class="form-control"
                                value="{{ old('payment_days', $exportOrder->payment_days) }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                        <td style="width: 70%;">
                            <select name="currency_id" id="currencySelect" class="form-control select2" readonly>
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
                                readonly value="{{ old('currency_rate', $exportOrder->currency_rate) }}" readonly>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details</h6>

            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Brand</th>
                            <th style="min-width: 150px;">Bag Type</th>
                            <th style="min-width: 130px;">Packing</th>
                            <th style="min-width: 110px;">Color</th>
                            <th style="min-width: 100px;">Packing Size (kg)</th>
                            <th style="min-width: 100px;">Qty (MT)</th>
                            <th style="min-width: 100px; display: none;">Qty (Mnds)</th>
                            <th style="min-width: 110px;">Qty (KGs)</th>
                            <th style="min-width: 100px;">Bags</th>
                            <th style="min-width: 120px;">Stuffing (MT)</th>
                            <th style="min-width: 120px; display: none;">Stuffing (Mnd)</th>
                            <th style="min-width: 90px;">Containers</th>
                            <th style="min-width: 110px;">Rate/Ton</th>
                            <th style="min-width: 110px; display: none;">Rate/Mnd</th>
                            <th style="min-width: 130px;">Amount</th>
                            <th style="min-width: 130px;">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exportOrder->packingItems as $index => $item)
                        <tr>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ $item->brand->name ?? 'N/A' }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ $item->bagType->name ?? 'N/A' }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ $item->bagPacking->name ?? 'N/A' }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ $item->bagColor->color ?? 'N/A' }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->bag_size, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->metric_tons, 3) }}" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="text" class="form-control" value="{{ number_format($item->maunds, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->total_kgs, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->no_of_bags, 0) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->stuffing_in_container, 3) }}" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="text" class="form-control" value="{{ number_format($item->stuffing_maunds, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ $item->no_of_containers }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="text" class="form-control" value="{{ number_format($item->rate_per_maund, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->amount, 2) }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="form-control" value="{{ number_format($item->amount_pkr, 2) }}" readonly>
                            </td>
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
        </div>
    </div>
</form>

<div class="row">
    <div class="col-12">
        <x-approval-status :model="$exportOrder" />
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2 for all multi-selects
        $('.select2').select2();

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

        $(document).on('input', '.bag-size, .metric-tons', function() {
            let item = $(this).closest('.packing-item');
            calculateTotals(item);
        });

        // Auto-calculate stuffing based on metric tons and containers
        $(document).on('input', '.metric-tons, .containers', function() {
            var item = $(this).closest('.packing-item');
            calculateStuffing(item);
        });

        // Auto-calculate containers based on metric tons and stuffing
        $(document).on('input', '.metric-tons, .stuffing', function() {
            var item = $(this).closest('.packing-item');
            calculateContainers(item);
        });

        $(document).on('input', '.rates, .metric-tons', function() {
            let item = $(this).closest('.packing-item');
            calculateAmount(item);
        });


        function calculateStuffing(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var containers = parseInt(item.find('.containers').val()) || 0;

            if (containers > 0 && metricTons > 0) {
                var stuffingPerContainer = metricTons / containers;
                item.find('.stuffing').val(stuffingPerContainer.toFixed(3));
            }
        }

        function calculateContainers(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var stuffing = parseFloat(item.find('.stuffing').val()) || 0;

            if (stuffing > 0 && metricTons > 0) {
                var containers = Math.ceil(metricTons / stuffing);
                item.find('.containers').val(containers);
            }
        }

        function calculateTotals(item) {
            let bagSize = parseFloat(item.find('.bag-size').val()) || 0; // kg
            let quantityMT = parseFloat(item.find('.metric-tons').val()) || 0; // MTs

            // Total KGs
            let totalKgs = quantityMT * 1000;

            // No of Bags
            let totalBags = 0;
            if (bagSize > 0) {
                totalBags = totalKgs / bagSize;
            }

            // Update fields
            item.find('.no_of_bags').val(totalBags.toFixed(0));
            item.find('.total-kgs').val(totalKgs.toFixed(2));

            // Stuffing auto
            let containers = parseInt(item.find('.containers').val()) || 0;
            if (containers > 0) {
                calculateStuffing(item);
            }

            calculateAmount(item);
        }


        function calculateAmount(item) {
            let rate = parseFloat(item.find('.rates').val()) || 0;
            let metricTons = parseFloat(item.find('.metric-tons').val()) || 0;

            // keep 3 decimals
            rate = parseFloat(rate.toFixed(3));
            metricTons = parseFloat(metricTons.toFixed(3));

            let amount = rate * metricTons;

            item.find('.amount').val(amount.toFixed(2));

            // PKR conversion
            let currencyRate = parseFloat($('#currencyRate').val()) || 0;
            if (currencyRate > 0) {
                let amountPKR = amount * currencyRate;
                item.find('.amount_pkr').val(amountPKR.toFixed(2));
            }
        }


        function reindexPackingItems() {
            $('.packing-item').each(function(index) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }

        // Initial calculation for first item
        calculateTotals($('.packing-item').first());
    });

    function loadBankDetails(bankId) {
        if (!bankId) {
            $('#acc_title, #bank_name, #iban, #account_no, #swift_code, #bank_address, #description').val('');
            return;
        }

        $.get('/export/get-bank-details/' + bankId, function(bank) {
            $('#acc_title').val(bank.account_title);
            $('#bank_name').val(bank.bank_name);
            $('#iban').val(bank.iban);
            $('#account_no').val(bank.account_no);
            $('#swift_code').val(bank.swift_code);
            $('#bank_address').val(bank.bank_address);
            $('#description').val(bank.description);
        });
    }

    function loadCorrespondentBankDetails(bankId) {
        if (!bankId) {
            $('#cor_acc_title, #cor_bank_name, #cor_iban, #cor_account_no, #cor_swift_code, #cor_bank_address, #cor_description')
                .val('');
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
    }

    $(document).ready(function() {

        // change events
        $('#bankSelect').on('change', function() {
            loadBankDetails($(this).val());
        });

        $('#correspondentBankSelect').on('change', function() {
            loadCorrespondentBankDetails($(this).val());
        });

        let selectedBank = $('#bankSelect').val();
        if (selectedBank) {
            loadBankDetails(selectedBank);
        }

        let selectedCorBank = $('#correspondentBankSelect').val();
        if (selectedCorBank) {
            loadCorrespondentBankDetails(selectedCorBank);
        }
    });

    $(document).ready(function() {
        $('#currencySelect').on('change', function() {
            let rate = $(this).find(':selected').data('rate') || '';
            $('#currencyRate').val(rate);
        });

    });

    $(document).ready(function() {
        let selectedArrivalLocations = @json($exportOrder->arrival_location_ids ?? []);
        let selectedArrivalSubLocations = @json($exportOrder->arrival_sub_location_ids ?? []);

        // Convert saved IDs to strings for comparison
        selectedArrivalLocations = selectedArrivalLocations.map(String);
        selectedArrivalSubLocations = selectedArrivalSubLocations.map(String);

        // Populate arrival locations on page load
        let companyLocationIds = $('#companyLocationSelect').val();
        if (companyLocationIds && companyLocationIds.length > 0) {
            populateArrivalLocations(companyLocationIds, selectedArrivalLocations, selectedArrivalSubLocations);
        }

        // Company location change
        $('#companyLocationSelect').on('change', function() {
            selectedArrivalLocations = [];
            selectedArrivalSubLocations = [];
            populateArrivalLocations($(this).val(), [], []);
        });

        // Arrival location change
        $('#arrivalLocationSelect').on('change', function() {
            selectedArrivalSubLocations = [];
            populateArrivalSubLocations($(this).val(), []);
        });

        // Functions
        function populateArrivalLocations(companyLocationIds, selectedIds = [], selectedSubIds = []) {
            $('#arrivalLocationSelect').empty().trigger('change');
            $('#arrivalSubLocationSelect').empty().trigger('change');

            if (!companyLocationIds || companyLocationIds.length === 0) return;

            $.post('/export/get-arrival-locations', {
                company_location_ids: companyLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(location) {
                    let locId = String(location.id);
                    options += `<option value="${locId}" ${selectedIds.includes(locId) ? 'selected' : ''}>
                                ${location.name}
                            </option>`;
                });
                $('#arrivalLocationSelect').html(options).trigger('change');

                // Populate sub locations for selected arrival locations
                if (selectedIds.length > 0) {
                    populateArrivalSubLocations(selectedIds, selectedSubIds);
                }
            });
        }

        function populateArrivalSubLocations(arrivalLocationIds, selectedIds = []) {
            $('#arrivalSubLocationSelect').empty().trigger('change');

            if (!arrivalLocationIds || arrivalLocationIds.length === 0) return;

            $.post('/export/get-arrival-sub-locations', {
                arrival_location_ids: arrivalLocationIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function(response) {
                let options = '';
                response.forEach(function(sub) {
                    let subId = String(sub.id);
                    options += `<option value="${subId}" ${selectedIds.includes(subId) ? 'selected' : ''}>
                                ${sub.name}
                            </option>`;
                });
                $('#arrivalSubLocationSelect').html(options).trigger('change');
            });
        }
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
    $(document).ready(function() {

        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law').summernote({
            tabsize: 2,
            height: 200,
            toolbar: [],
            airMode: false
        });

        $('#shipping_instructions').summernote('disable');
        $('#documents_to_be_provided').summernote('disable');
        $('#other_condition').summernote('disable');
        $('#force_majure').summernote('disable');
        $('#application_law').summernote('disable');

    });
</script>
