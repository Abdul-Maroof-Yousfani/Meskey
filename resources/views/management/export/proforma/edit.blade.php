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

<form action="{{ route('proforma.update', $proforma->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.proforma') }}" />

    <div class="row form-mar">
        <div class="col-8">
            <!-- Basic Information -->
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Basic Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <fieldset>
                            <label>Sauda#:</label>
                            <select name="export_soda_id" class="form-control select2" disabled>
                                <option value="">Select Sauda</option>
                                @foreach ($exportSodas as $soda)
                                    <option value="{{ $soda->id }}" {{ old('export_soda_id', $exportOrder->export_soda_id) == $soda->id ? 'selected' : '' }}>
                                        {{ $soda->reference ?? ('#' . $soda->id) }} - {{ $soda->product->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quotation#:</label>
                            <select name="quotation_id" class="form-control select2" disabled>
                                <option value="">Select Quotation</option>
                                @foreach ($quotations as $quotation)
                                    <option value="{{ $quotation->id }}" {{ old('quotation_id', $exportOrder->quotation_id) == $quotation->id ? 'selected' : '' }}>
                                        {{ $quotation->reference ?? ('#' . $quotation->id) }} - {{ $quotation->product->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <fieldset>
                            <label>Voucher No#</label>
                            <div class="input-group">
                                <input type="text" readonly name="voucher_no" class="form-control"
                                    value="{{ old('voucher_no', $exportOrder->voucher_no) }}">
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
                                value="{{ old('voucher_date', $exportOrder->voucher_date ? \Carbon\Carbon::parse($exportOrder->voucher_date)->format('Y-m-d') : '') }}" disabled>
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
                                value="{{ old('shipment_delivery_date_from', $exportOrder->shipment_delivery_date_from ? \Carbon\Carbon::parse($exportOrder->shipment_delivery_date_from)->format('Y-m-d') : '') }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Shipment DeliveryDate To:</label>
                            <input type="date" name="shipment_delivery_date_to" class="form-control"
                                value="{{ old('shipment_delivery_date_to', $exportOrder->shipment_delivery_date_to ? \Carbon\Carbon::parse($exportOrder->shipment_delivery_date_to)->format('Y-m-d') : '') }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Marking/labeling:</label>
                            <input type="text" name="marking_labeling" class="form-control"
                                value="{{ old('marking_labeling', $exportOrder->marking_labeling) }}" disabled>
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
                <div class="form-group mt-2">
                    <label>Visual Name:</label>
                    <input type="text" name="visual_name" id="visualName" class="form-control"
                        value="{{ old('visual_name', $exportOrder->visual_name) }}" disabled>
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
                                <input type="text" id="bankSelect" class="form-control"
                                    value="{{ $exportOrder->customerBank ? $exportOrder->customerBank->bank_name . ' (' . ($exportOrder->customer_bank_type == 'owner' ? 'Owner' : 'Company') . ')' : 'N/A' }}"
                                    disabled>
                                <input type="hidden" name="customer_bank_id" value="{{ $exportOrder->customer_bank_id }}">
                                <input type="hidden" name="customer_bank_type" id="bank_type_hidden" value="{{ old('customer_bank_type', $exportOrder->customer_bank_type) }}">
                            </div>

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
                                <label>Branch Code:</label>
                                <input type="text" id="branch_code" class="form-control" value="{{ $exportOrder->customerBank->branch_code ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6 mt-2">
                                <label>Account No:</label>
                                <input type="text" id="account_no" class="form-control" value="{{ $exportOrder->customerBank->account_number ?? '' }}" disabled>
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

            <!-- <div class="col-md-12">
                <div class="form-group">
                    <label>Consignee Details:</label>
                    <textarea name="consigned_details" id="consigned_details" class="form-control" rows="4">{{ old('consigned_details', $proforma->consigned_details) }}</textarea>
                </div>
            </div> -->

            {{-- shipping instructions --}}
            <div class="col-md-12 mb-4">
                <label>Shipping Instruction:</label>
                <textarea name="shipping_instructions" id="shipping_instructions" class="form-control" disabled>{{ old('shipping_instructions', $exportOrder->shipping_instructions) }}</textarea>
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
                <textarea name="documents_to_be_provided" id="documents_to_be_provided" class="form-control" disabled>{{ old('documents_to_be_provided', $exportOrder->documents_to_be_provided) }}</textarea>
            </div>

            <div class="row p-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Other Condition:</label>
                        <textarea name="other_condition" id="other_condition" class="form-control" rows="3" disabled>{{ old('other_condition', $exportOrder->other_condition) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Force Majure:</label>
                        <textarea name="force_majure" id="force_majure" class="form-control" rows="3" disabled>{{ old('force_majure', $exportOrder->force_majure) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Application Law:</label>
                        <textarea name="application_law" id="application_law" class="form-control" rows="3" disabled>{{ old('application_law', $exportOrder->application_law) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Additional Info:</label>
                    <textarea name="additional_info" id="additional_info" class="form-control" rows="3" disabled>{{ old('additional_info', $exportOrder->additional_info) }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <h6 class="header-heading-sepration">Commission</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Commission (%):</label>
                            <input type="number" id="commission_percentage" name="commission_percentage"
                                class="form-control" step="0.01" min="0"
                                value="{{ old('commission_percentage', $exportOrder->commission_percentage) }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Amt/Ton:</label>
                            <input type="number" id="commission_amount_per_ton" name="commission_amount_per_ton"
                                class="form-control" step="0.01" min="0"
                                value="{{ old('commission_amount_per_ton', $exportOrder->commission_amount_per_ton ?? (($exportOrder->packingItems->sum('metric_tons') > 0) ? (($exportOrder->commission ?? 0) / $exportOrder->packingItems->sum('metric_tons')) : 0)) }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Commission:</label>
                            <input type="number" id="commission" name="commission" class="form-control" step="0.01"
                                value="{{ old('commission', $exportOrder->commission) }}" readonly>
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
                                readonly value="{{ old('currency_rate', $exportOrder->currency_rate) }}" disabled>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Packing Details</h6>
            @forelse ($exportOrder->packingItems as $itemIndex => $item)
                <div class="packing-item card border-secondary mb-4 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 font-weight-bold grey">Packing Row #{{ $itemIndex + 1 }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2"><div class="form-group"><label>Brand:</label><input type="text" class="form-control" value="{{ $item->brand->name ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Bag Type:</label><input type="text" class="form-control" value="{{ $item->bagType->name ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Packing Type:</label><input type="text" class="form-control" value="{{ $item->bagPacking->name ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Bag Condition:</label><input type="text" class="form-control" value="{{ $item->bagCondition->name ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Bag Color:</label><input type="text" class="form-control" value="{{ $item->bagColor->color ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Thread Color:</label><input type="text" class="form-control" value="{{ $item->threadColor->color ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Stitching:</label><input type="text" class="form-control" value="{{ $item->stitching->name ?? '-' }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Bag Size (kg):</label><input type="text" class="form-control" value="{{ number_format($item->bag_size, 2) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>No. of Bags:</label><input type="text" class="form-control font-weight-bold" value="{{ number_format($item->no_of_bags, 0) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Extra Bags:</label><input type="text" class="form-control" value="{{ $item->extra_bags }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Empty Bags:</label><input type="text" class="form-control" value="{{ $item->empty_bags }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Qty (MT):</label><input type="text" class="form-control font-weight-bold text-primary" value="{{ number_format($item->metric_tons, 3) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Total Bags:</label><input type="text" class="form-control" value="{{ number_format($item->total_bags, 0) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Stuffing/Cont (MT):</label><input type="text" class="form-control" value="{{ number_format($item->stuffing_in_container, 3) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Containers:</label><input type="text" class="form-control" value="{{ $item->no_of_containers }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Rate/Ton:</label><input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Amount:</label><input type="text" class="form-control font-weight-bold" value="{{ number_format($item->amount, 2) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Amount (PKR):</label><input type="text" class="form-control font-weight-bold text-success" value="{{ number_format($item->amount_pkr, 2) }}" readonly></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Min Weight Empty Bags:</label><input type="text" class="form-control" value="{{ number_format($item->min_weight_empty_bags, 2) }}" readonly></div></div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fumigation By:</label>
                                    @php
                                        $fNames = [];
                                        if (is_array($item->fumigation_company_id)) {
                                            $fNames = $inspectionCompanies->whereIn('id', $item->fumigation_company_id)->pluck('name')->toArray();
                                        }
                                    @endphp
                                    <input type="text" class="form-control" value="{{ count($fNames) ? implode(', ', $fNames) : '-' }}" readonly>
                                </div>
                            </div>
                        </div>
                        @if($item->subItems->count() > 0)
                            <div class="mt-4">
                                <div class="card border-info shadow-none">
                                    <div class="card-header bg-light-info d-flex justify-content-between align-items-center py-1">
                                        <h6 class="mb-0 font-weight-bold">Master Packing #{{ sprintf('%02d', $itemIndex + 1) }}</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-0 text-nowrap">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="min-width: 150px;">Bag Type</th>
                                                        <th style="min-width: 120px;">Bag Size</th>
                                                        <th style="min-width: 150px;">Primary Bags fit in master bag</th>
                                                        <th style="min-width: 90px;">No. of Bags</th>
                                                        <th style="min-width: 90px;">Empty Bags</th>
                                                        <th style="min-width: 90px;">Extra Bags</th>
                                                        <th style="min-width: 100px;">Empty Bag Weight (g)</th>
                                                        <th style="min-width: 90px;">Total Bags</th>
                                                        <th style="min-width: 120px;">Stitching</th>
                                                        <th style="min-width: 120px;">Bag Color</th>
                                                        <th style="min-width: 120px;">Brand</th>
                                                        <th style="min-width: 120px;">Thread Color</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($item->subItems as $sub)
                                                        <tr>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->bagType->name ?? '-' }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->bagSize->size ?? '-' }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->no_of_primary_bags }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->no_of_bags }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->empty_bags }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->extra_bags }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->empty_bag_weight }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm font-weight-bold" value="{{ $sub->total_bags }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->stitching->name ?? '-' }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->bagColor->color ?? '-' }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->brand->name ?? '-' }}" readonly></td>
                                                            <td class="p-1"><input type="text" class="form-control form-control-sm" value="{{ $sub->threadColor->color ?? '-' }}" readonly></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alert alert-light">No packing items found.</div>
            @endforelse
        </div>

    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Proforma</button>
        </div>
    </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Summernote (safe re-open in modal)
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #other_specifications, #consigned_details, #additional_info').each(function() {
            if ($(this).next('.note-editor').length) {
                $(this).summernote('destroy');
            }
        }).summernote({
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
        $('#other_condition').summernote('code', `{!! addslashes(old('other_condition', $exportOrder->other_condition ?? '')) !!}`);
        $('#force_majure').summernote('code', `{!! addslashes(old('force_majure', $exportOrder->force_majure ?? '')) !!}`);
        $('#application_law').summernote('code', `{!! addslashes(old('application_law', $exportOrder->application_law ?? '')) !!}`);
        $('#additional_info').summernote('code', `{!! addslashes(old('additional_info', $exportOrder->additional_info ?? '')) !!}`);
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #other_specifications, #additional_info').summernote('disable');

        // Initialize Select2 (safe re-open in modal)
        $('.select2').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            $(this).select2({ width: '100%' });
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
        }).trigger('change');

    });
</script>
