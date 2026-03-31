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

<form action="{{ route('export-order.update', $exportOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-order') }}" />

    <div class="row form-mar">
        <div class="col-8">
            <!-- Basic Information -->
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Basic Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quotation#:</label>
                            <select name="quotation_id" class="form-control select2">
                                <option value="">Select Quotation</option>
                                @foreach ($quotations as $quotation)
                                    <option value="{{ $quotation->id }}" {{ old('quotation_id', $exportOrder->quotation_id) == $quotation->id ? 'selected' : '' }}>#{{ $quotation->id }} - {{ $quotation->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <fieldset>
                            <label>Sauda#:</label>
                            <select name="export_soda_id" class="form-control select2">
                                <option value="">Select Sauda</option>
                                @foreach ($exportSodas as $soda)
                                    <option value="{{ $soda->id }}" {{ old('export_soda_id', $exportOrder->export_soda_id) == $soda->id ? 'selected' : '' }}>#{{ $soda->id }} - {{ $soda->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contract No#:</label>
                            <input type="text" readonly name="voucher_no" class="form-control"
                                value="{{ old('voucher_no', $exportOrder->voucher_no) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contract Date:</label>
                            <input type="date" name="voucher_date" class="form-control"
                                value="{{ old('voucher_date', $exportOrder->voucher_date) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reference No#:</label>
                            <input type="text" name="contract_no" class="form-control"
                                value="{{ old('contract_no', $exportOrder->contract_no) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reference Date:</label>
                            <input type="date" name="voucher_heading" class="form-control"
                                value="{{ old('voucher_heading', (is_string($exportOrder->voucher_heading) && strtotime($exportOrder->voucher_heading)) ? date('Y-m-d', strtotime($exportOrder->voucher_heading)) : $exportOrder->voucher_heading) }}">
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name:</label>
                            <select name="buyer_id" class="form-control select2">
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
                                value="{{ old('shipment_delivery_date_from', $exportOrder->shipment_delivery_date_from) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Shipment DeliveryDate To:</label>
                            <input type="date" name="shipment_delivery_date_to" class="form-control"
                                value="{{ old('shipment_delivery_date_to', $exportOrder->shipment_delivery_date_to) }}">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Marking/labeling:</label>
                            <input type="text" name="marking_labeling" class="form-control"
                                value="{{ old('marking_labeling', $exportOrder->marking_labeling) }}">
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
                            <option value="{{ $product->id }}"
                                {{ old('product_id', $exportOrder->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mt-2">
                    <label>Visual Name:</label>
                    <input type="text" name="visual_name" id="visualName" class="form-control" value="{{ old('visual_name', $exportOrder->visual_name) }}" placeholder="Enter visual name for product...">
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
                                                                placeholder="Enter value">
                                                            <div class="input-group-prepend">
                                                                <button class="btn btn-secondary"
                                                                    type="button">{{ $spec->productSlabType->qc_symbol ?? 'N/A' }}</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </td>
                                                <td>
                                                    <select name="specifications[{{ $index }}][value_type]"
                                                        class="form-control">
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
                    <textarea name="other_specifications" class="form-control" rows="4">{{ old('other_specifications', $exportOrder->other_specifications) }}</textarea>
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

            {{-- shipping instructions --}}
            <div class="col-md-12 mb-4">
                <label>Shipping Instruction:</label>
                <textarea name="shipping_instructions" id="shipping_instructions" class="form-control">{{ old('shipping_instructions', $exportOrder->shipping_instructions) }}</textarea>
            </div>

            {{-- broker --}}
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <label>Broker:</label>
                    <select name="broker_id" class="form-control select2">
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
                        <textarea name="other_condition" id="other_condition" class="form-control" rows="3">{{ old('other_condition', $exportOrder->other_condition) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Force Majure:</label>
                        <textarea name="force_majure" id="force_majure" class="form-control" rows="3">{{ old('force_majure', $exportOrder->force_majure) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Application Law:</label>
                        <textarea name="application_law" id="application_law" class="form-control" rows="3">{{ old('application_law', $exportOrder->application_law) }}</textarea>
                    </div>
                </div>
            </div>
            {{-- Commission Section --}}
            <div class="mt-4">
                <h6 class="header-heading-sepration">Commission</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Commission (%):</label>
                            <input type="number" id="commission_percentage" name="commission_percentage"
                                class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Amt/Ton:</label>
                            <input type="number" id="commission_amount_per_ton" name="commission_amount_per_ton"
                                class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Commission:</label>
                            <input type="number" id="commission" name="commission" class="form-control"
                                step="0.01" value="{{ old('commission', $exportOrder->commission) }}" readonly>
                        </div>
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
                            <select name="packing_type" class="form-control select2">
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
                            <select name="mode_of_term_id" class="form-control select2">
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
                            <select name="mode_of_transport_id" class="form-control select2">
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
                            <select name="origin_country_id" class="form-control select2">
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
                            <select name="port_of_discharge_id" class="form-control select2">
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
                            <select name="port_of_loading_id" class="form-control select2">
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
                            <select name="hs_code_id" class="form-control select2">
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
                            <select name="partial_payment" class="form-control select2">
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
                            <select name="transhipment" class="form-control select2">
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
                            <select name="part_shipment" class="form-control select2">
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
                            <select name="insurance_covered_by" class="form-control select2">
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
                                value="{{ old('advance_payment', $exportOrder->advance_payment) }}">
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS(no of days)
                        </td>
                        <td style="width: 70%;">
                            <input type="text" name="payment_days" class="form-control"
                                value="{{ old('payment_days', $exportOrder->payment_days) }}">
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                        <td style="width: 70%;">
                            <select name="currency_id" id="currencySelect" class="form-control select2">
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
                                readonly value="{{ old('currency_rate', $exportOrder->currency_rate) }}">
                        </td>
                    </tr>
                </table>
            </div>


        </div>

        <!-- Packing Details -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add Item</button>
            </h6>

            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="packingTable">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Brand</th>
                            <th style="min-width: 150px;">Bag Type</th>
                            <th style="min-width: 130px;">Packing</th>
                            <th style="min-width: 110px;">Color</th>
                            <th style="min-width: 100px;">Packing Size (kg)</th>
                            <th style="min-width: 100px;">Qty (MT)</th>
                            <th style="min-width: 100px; display: none;">Qty (Mnds)</th>
                            <th style="min-width: 110px; display: none;">Qty (KGs)</th>
                            <th style="min-width: 100px;">Bags</th>
                            <th style="min-width: 120px;">Stuffing (MT)</th>
                            <th style="min-width: 120px; display: none;">Stuffing (Mnd)</th>
                            <th style="min-width: 90px;">Containers</th>
                            <th style="min-width: 110px;">Rate/Ton</th>
                            <th style="min-width: 110px; display: none;">Rate/Mnd</th>
                            <th style="min-width: 130px;">Amount</th>
                            <th style="min-width: 130px;">Amount (PKR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        @foreach ($exportOrder->packingItems as $index => $item)
                        <tr class="packing-item">
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][brand_id]" class="form-control select2">
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ $brand->id == $item->brand_id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_type_id]" class="form-control select2">
                                    <option value="">Select Bag Type</option>
                                    @foreach ($bagTypes as $bagType)
                                        <option value="{{ $bagType->id }}" {{ $bagType->id == $item->bag_type_id ? 'selected' : '' }}>
                                            {{ $bagType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select class="form-control" name="packing_items[{{ $index }}][bag_packing_id]">
                                    <option value="">Select Packing</option>
                                    @foreach ($bagPackings as $packing)
                                        <option value="{{ $packing->id }}" {{ $packing->id == $item->bag_packing_id ? 'selected' : '' }}>
                                            {{ $packing->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <select name="packing_items[{{ $index }}][bag_color_id]" class="form-control select2">
                                    <option value="">Select Color</option>
                                    @foreach ($bagColors as $color)
                                        <option value="{{ $color->id }}" {{ $color->id == $item->bag_color_id ? 'selected' : '' }}>
                                            {{ $color->color }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][bag_size]" class="form-control bag-size"
                                    step="0.01" value="{{ $item->bag_size }}" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][metric_tons]"
                                    class="form-control metric-tons" value="{{ $item->metric_tons }}" step="0.001" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $index }}][maunds]"
                                    class="form-control maunds" value="{{ $item->maunds }}" step="0.01" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $index }}][total_kgs]" class="form-control total-kgs"
                                    value="{{ $item->total_kgs }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][no_of_bags]" class="form-control no_of_bags"
                                    value="{{ $item->no_of_bags }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][stuffing_in_container]"
                                    class="form-control stuffing" value="{{ $item->stuffing_in_container }}" step="0.001" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $index }}][stuffing_maunds]"
                                    class="form-control stuffing_maunds" value="{{ $item->stuffing_maunds }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][no_of_containers]"
                                    class="form-control containers" value="{{ $item->no_of_containers }}" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][rate]" class="form-control rates"
                                    value="{{ $item->rate }}" step="0.01" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $index }}][rate_per_maund]" class="form-control rates_mnd"
                                    value="{{ $item->rate_per_maund }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][amount]" class="form-control amount"
                                    value="{{ $item->amount }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $index }}][amount_pkr]" class="form-control amount_pkr" 
                                    value="{{ $item->amount_pkr }}" readonly>
                            </td>
                            <td class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                    <i class="ft-trash-2"></i>
                                </button>
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
            <button type="submit" class="btn btn-primary submitbutton">Update Export Order</button>
        </div>
    </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

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

        // Initialize Select2 for all elements
        $('.select2').select2({ width: '100%' });

        // Product selection change
        $('#productSelect').on('change', function() {
            var productId = $(this).val();
            var productName = $(this).find(':selected').text();
            
            if (productId) {
                 // Auto-fill visual name
                 $('#visualName').val(productName);
                 
                var url = "{{ url('get-product-specs') }}/" + productId;
                $.get(url, function(data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#visualName').val('');
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

            // Destroy Select2 before cloning
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();

            // Clear values
            newRow.find('input').val(0);
            newRow.find('input[readonly]').val(0);
            newRow.find('select').val('').trigger('change');

            // Append to table
            $('#packingItems').append(newRow);

            // Re-initialize Select2
            $('.select2').select2({ width: '100%' });

            // Re-index all items
            reindexPackingItems();
        }

        // Remove packing item
        $(document).on('click', '.remove-packing-item', function() {
            if ($('#packingItems tr.packing-item').length > 1) {
                $(this).closest('tr').remove();
                reindexPackingItems();
            } else {
                alert('At least one packing item is required.');
            }
        });

        // Conversion Logic
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
            
            // Trigger commission recalculation
            calculateOverallTotals();
        }

        function calculateOverallTotals() {
            let totalMt = 0;
            let totalAmount = 0;
            $('#packingItems tr.packing-item').each(function() {
                totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
                totalAmount += parseFloat($(this).find('.amount').val()) || 0;
            });
            
            // Recalculate commission if percentage or amt/ton is present
            calculateCommissionFields(totalAmount, totalMt);
        }

        // ---- COMMISSION CALCULATIONS ----
        $(document).on('input', '#commission_percentage', function() {
            let totalAmount = 0;
            let totalMt = 0;
            $('#packingItems tr.packing-item').each(function() {
                totalAmount += parseFloat($(this).find('.amount').val()) || 0;
                totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
            });
            
            let percentage = parseFloat($(this).val()) || 0;
            let commission = (totalAmount * percentage) / 100;
            let amtPerTon = totalMt > 0 ? (commission / totalMt) : 0;
            
            $('#commission').val(commission.toFixed(2));
            $('#commission_amount_per_ton').val(amtPerTon.toFixed(2));
        });

        $(document).on('input', '#commission_amount_per_ton', function() {
            let totalAmount = 0;
            let totalMt = 0;
            $('#packingItems tr.packing-item').each(function() {
                totalAmount += parseFloat($(this).find('.amount').val()) || 0;
                totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
            });
            
            let amtPerTon = parseFloat($(this).val()) || 0;
            let commission = totalMt * amtPerTon;
            let percentage = totalAmount > 0 ? (commission / totalAmount) * 100 : 0;
            
            $('#commission').val(commission.toFixed(2));
            $('#commission_percentage').val(percentage.toFixed(2));
        });

        function calculateCommissionFields(totalAmount, totalMt) {
            let percentage = parseFloat($('#commission_percentage').val());
            let amtPerTon = parseFloat($('#commission_amount_per_ton').val());
            
            if (!isNaN(percentage) && percentage > 0) {
                let commission = (totalAmount * percentage) / 100;
                let calculatedAmtPerTon = totalMt > 0 ? (commission / totalMt) : 0;
                $('#commission').val(commission.toFixed(2));
                $('#commission_amount_per_ton').val(calculatedAmtPerTon.toFixed(2));
            } else if (!isNaN(amtPerTon) && amtPerTon > 0) {
                let commission = totalMt * amtPerTon;
                let calculatedPercentage = totalAmount > 0 ? (commission / totalAmount) * 100 : 0;
                $('#commission').val(commission.toFixed(2));
                $('#commission_percentage').val(calculatedPercentage.toFixed(2));
            }
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
        let savedBankId = '{{ $exportOrder->customer_bank_type }}_{{ $exportOrder->customer_bank_id }}';
        
        function loadCustomerBanks(customerId) {
            if (!customerId) {
                $('#bankSelect').html('<option value="">-- Select Bank --</option>').trigger('change');
                return;
            }

            $.get('{{ route('export-order.customer-banks', '') }}/' + customerId, function(response) {
                let options = '<option value="">-- Select Bank --</option>';
                response.forEach(function(bank) {
                    let label = '[' + bank.type + '] ' + bank.account_title + ' - ' + bank.bank_name;
                    let selected = (bank.id === savedBankId) ? 'selected' : '';
                    options += `<option value="${bank.id}" ${selected}
                        data-title="${bank.account_title}"
                        data-bank="${bank.bank_name}"
                        data-branch="${bank.branch_name}"
                        data-branch-code="${bank.branch_code}"
                        data-account="${bank.account_number}">
                        ${label}
                    </option>`;
                });
                $('#bankSelect').html(options).trigger('change');
            });
        }

        // Buyer change
        $('select[name="buyer_id"]').on('change', function() {
            loadCustomerBanks($(this).val());
        });

        // Bank select change
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

        // Correspondent Bank
        function loadCorrespondentBankDetails(bankId) {
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
        }

        $('#correspondentBankSelect').on('change', function() {
            loadCorrespondentBankDetails($(this).val());
        });

        // Arrival Locations Logic
        let selectedArrivalLocations = @json($exportOrder->arrival_location_ids ?? []);
        let selectedArrivalSubLocations = @json($exportOrder->arrival_sub_location_ids ?? []);
        selectedArrivalLocations = selectedArrivalLocations.map(String);
        selectedArrivalSubLocations = selectedArrivalSubLocations.map(String);

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
                    options += `<option value="${locId}" ${selectedIds.includes(locId) ? 'selected' : ''}>${location.name}</option>`;
                });
                $('#arrivalLocationSelect').html(options).trigger('change');
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
                    options += `<option value="${subId}" ${selectedIds.includes(subId) ? 'selected' : ''}>${sub.name}</option>`;
                });
                $('#arrivalSubLocationSelect').html(options).trigger('change');
            });
        }

        $('#companyLocationSelect').on('change', function() {
            populateArrivalLocations($(this).val(), [], []);
        });

        $('#arrivalLocationSelect').on('change', function() {
            populateArrivalSubLocations($(this).val(), []);
        });

        // --- Page Load Initializations ---
        
        // Load customer banks if buyer exists
        let initialBuyer = $('select[name="buyer_id"]').val();
        if (initialBuyer) {
            loadCustomerBanks(initialBuyer);
        }

        // Load correspondent bank details if selected
        let initialCorBank = $('#correspondentBankSelect').val();
        if (initialCorBank) {
            loadCorrespondentBankDetails(initialCorBank);
        }

        // Load arrival locations
        let initialCompanyLocations = $('#companyLocationSelect').val();
        if (initialCompanyLocations && initialCompanyLocations.length > 0) {
            populateArrivalLocations(initialCompanyLocations, selectedArrivalLocations, selectedArrivalSubLocations);
        }

        // Initial calculations for all visible items
        let totalMt = 0;
        let totalAmount = 0;
        $('.packing-item').each(function() {
            calculateTotals($(this));
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
            totalAmount += parseFloat($(this).find('.amount').val()) || 0;
        });
        
        // Initial reverse-calculation of commission on page load
        let initialCommission = parseFloat($('#commission').val()) || 0;
        if (initialCommission > 0) {
            if (totalAmount > 0) {
                $('#commission_percentage').val(((initialCommission / totalAmount) * 100).toFixed(2));
            }
            if (totalMt > 0) {
                $('#commission_amount_per_ton').val((initialCommission / totalMt).toFixed(2));
            }
        }


    });
</script>

