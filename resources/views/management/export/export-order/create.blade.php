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
                    <div class="col-md-6">
                        <fieldset>
                            <label>Sauda#:</label>
                            <select name="export_soda_id" class="form-control select2">
                                <option value="">Select Sauda</option>
                                @foreach ($exportSodas as $soda)
                                    <option value="{{ $soda->id }}">{{ $soda->reference ?? ('#' . $soda->id) }} - {{ $soda->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quotation#:</label>
                            <select name="quotation_id" class="form-control select2">
                                <option value="">Select Quotation</option>
                                @foreach ($quotations as $quotation)
                                    <option value="{{ $quotation->id }}" data-sauda-id="{{ $quotation->export_soda_id }}">{{ $quotation->reference ?? ('#' . $quotation->id) }} - {{ $quotation->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <fieldset>
                            <label>Contract No#:</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <button class="btn btn-primary" type="button">Contract No#</button>
                                </div>
                                <input type="text" readonly name="voucher_no" class="form-control" placeholder="Select date to generate...">
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contract Date:</label>
                            <input type="date" name="voucher_date" max="{{ date('Y-m-d') }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reference No#:</label>
                            <input type="text" name="contract_no" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reference Date:</label>
                            <input type="date" name="voucher_heading" class="form-control" value="{{ date('Y-m-d') }}">
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
                <div class="form-group mt-2">
                    <label>Visual Name:</label>
                    <input type="text" name="visual_name" id="visualName" class="form-control" placeholder="Enter visual name for product...">
                </div>
            </div>


            <!-- Specifications Section -->
            <div class="col-md-12" id="specificationsSection" style="display: none;">
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
                <textarea name="documents_to_be_provided" id="documents_to_be_provided" class="form-control">{{ old('documents_to_be_provided', '<ol><li>SIGNED COMMERCIAL INVOICE IN 1 ORIGINALS AND 2 COPIES.</li><li>PACKING LIST IN 1 ORIGINALS SHOWING TOTAL NUMBER OF CONTAINERS, NUMBER OF BAGS IN EACH CONTAINER, NET WEIGHT OF EACH CONTAINER, TOTAL NUMBER OF BAGS OF THE SHIPMENT AND TOTAL NET WEIGHT OF EACH SHIPMENT.</li><li>FULL SET(S) 3/3 ORIGINAL CLEAN BILL OF LADING, MARKED FREIGHT PREPAID.</li><li>CERTIFICATE OF ORIGIN ISSUED BY CHAMBER OF COMMERCE OF PAKISTAN IN 1 ORIGINAL AND 2 COPIES.</li><li>CERTIFICATE OF PHYTOSANITARY ISSUED BY OFFICIAL GOVERNMENT AUTHORITY IN 1 ORIGINAL AND 2 COPIES.</li><li>CERTIFICATE OF FUMIGATION ISSUED BY COMPETENT AUTHORITY IN 1 ORIGINAL AND 2 COPIES.</li><li>CERTIFICATE OF QUALITY, QUANTITY & WEIGHT ISSUED BY SGS IN 1 ORIGINAL AND 2 COPIES.</li><li>CERTIFICATE OF ORIGIN CHINA- PAKISTAN FTA DUTY-FREE TREATMENT GRANTED BY CHINA (COMBINED DECLARATION AND CERTIFICATE) ISSUED BY PAKISTAN AUTHORITY IN 1 ORGINAL AND 2 COPIES</li><li>SHIPMENT ADVISE IN 1 ORIGINAL AND 2 COPIES.</li></ol>') }}</textarea>
            </div>

            <div class="row p-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Other Condition:</label>
                        <textarea name="other_condition" id="other_condition" class="form-control" rows="3">{{ old('other_condition', '<ol><li>PARTIAL - ALLOWED / TRANSSHIPMENT ALLOWED</li><li>ALL DISCHARGING COSTS INCLUDING TERMINAL HANDLING CHARGES, LEVIES, AND TAXES ON THE CARGO AT DISCHARGING PORT ARE FOR BUYER\'S ACCOUNT.</li><li>SELLER TO REMAIN OWNER OF THE GOODS UNTIL FULL PAYMENT IS AFFECTED. ALL BANKING CHARGES OUT SIDE CHINA FOR SELLER\'S ACCOUNT.</li><li>BUYER AND SELLER AGREE TO CONSIDER THE CONTRACT AS FULLY CONFIDENTIAL AND AGREE NOT TO DISCLOSE ANY OF ITS CONTENTS TO THIRD PARTY.</li><li>14 FREE DAYS DETENTION AT DESTINATION.</li><li>IF PAYMENT DELAYS, WE RESERVE THE RIGHT TO CHARGE INTEREST AND CLAIM PENALTY IMPOSED BY SBP (IF ANY)</li></ol>') }}</textarea>
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
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Additional Info:</label>
                        <textarea name="additional_info" id="additional_info" class="form-control" rows="3"></textarea>
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
                            <input type="number" id="commission_percentage" name="commission_percentage" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Amt/Ton:</label>
                            <input type="number" id="commission_amount_per_ton" name="commission_amount_per_ton" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Commission:</label>
                            <input type="number" id="commission" name="commission" class="form-control" step="0.01" readonly>
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
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">
                    <i class="ft-plus"></i> Add Packing Row
                </button>
            </h6>

            <div id="packingItemsContainer">
                <div class="packing-item card border-secondary mb-4 shadow-sm" data-index="0">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 font-weight-bold grey">Packing Row #1</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                            <i class="ft-trash-2"></i> Remove Row
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Brand:</label>
                                    <select name="packing_items[0][brand_id]" class="form-control select2" required>
                                        <option value="">Select Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bag Type:</label>
                                    <select name="packing_items[0][bag_type_id]" class="form-control select2" required>
                                        <option value="">Select Bag Type</option>
                                        @foreach ($bagTypes as $bagType)
                                            <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Packing Type:</label>
                                    <select name="packing_items[0][bag_packing_id]" class="form-control select2" required>
                                        <option value="">Select Packing</option>
                                        @foreach ($bagPackings as $packing)
                                            <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bag Condition:</label>
                                    <select name="packing_items[0][bag_condition_id]" class="form-control select2" required>
                                        <option value="">Select Condition</option>
                                        @foreach ($bagConditions as $cond)
                                            <option value="{{ $cond->id }}">{{ $cond->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bag Color:</label>
                                    <select name="packing_items[0][bag_color_id]" class="form-control select2" required>
                                        <option value="">Select Color</option>
                                        @foreach ($bagColors as $color)
                                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Thread Color:</label>
                                    <select name="packing_items[0][thread_color_id]" class="form-control select2" required>
                                        <option value="">Select Color</option>
                                        @foreach ($threadColors as $color)
                                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Stitching:</label>
                                    <select name="packing_items[0][stitching_id]" class="form-control select2" required>
                                        <option value="">Select Stitching</option>
                                        @foreach ($stitchings as $stitching)
                                            <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bag Size (kg):</label>
                                    <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size" step="0.01" value="0" required min="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>No. of Bags:</label>
                                    <input type="number" name="packing_items[0][no_of_bags]" class="form-control no_of_bags" value="0" required min="1">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Extra Bags:</label>
                                    <input type="number" name="packing_items[0][extra_bags]" class="form-control extra-bags" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Empty Bags:</label>
                                    <input type="number" name="packing_items[0][empty_bags]" class="form-control empty-bags" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Qty (MT):</label>
                                    <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" step="0.001" value="0" required min="0.001">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total Bags:</label>
                                    <input type="number" name="packing_items[0][total_bags]" class="form-control total-bags" value="0" readonly>
                                </div>
                            </div>
                            <div class="col-md-2" style="display:none;">
                                <label>Total KGs:</label>
                                <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs" value="0" readonly>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Stuffing/Cont (MT):</label>
                                    <input type="number" name="packing_items[0][stuffing_in_container]" class="form-control stuffing" step="0.001" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Containers:</label>
                                    <input type="number" name="packing_items[0][no_of_containers]" class="form-control containers" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Rate/Ton:</label>
                                    <input type="number" name="packing_items[0][rate]" class="form-control rate-per-ton" step="0.01" value="0" required min="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Amount:</label>
                                    <input type="number" name="packing_items[0][amount]" class="form-control item-amount" value="0" readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Amount (PKR):</label>
                                    <input type="number" name="packing_items[0][amount_pkr]" class="form-control item-amount-pkr" value="0" readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Min Weight Empty Bags:</label>
                                    <input type="number" name="packing_items[0][min_weight_empty_bags]" class="form-control" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fumigation By:</label>
                                    <select name="packing_items[0][fumigation_company_id][]" class="form-control select2" multiple>
                                        <option value="">Select Fumigation Company (Optional)</option>
                                        @foreach ($inspectionCompanies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Master Packing Section -->
                        <div class="mt-4">
                            <div class="card border-info shadow-none">
                                <div class="card-header bg-light-info d-flex justify-content-between align-items-center py-1">
                                    <h6 class="mb-0 font-weight-bold">Master Packing #01</h6>
                                    <button type="button" class="btn btn-sm btn-info add-sub-packing-item" data-index="0">
                                        <i class="ft-plus"></i> Add Master Packing Item
                                    </button>
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
                                                    <th style="min-width: 150px;">Attachment</th>
                                                    <th style="min-width: 80px;" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="sub-packing-items-container" data-index="0">
                                                <!-- Dynamic sub-items here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        // Initialize Summernote
        const summernoteOptions = {
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
        };
        
        $('#shipping_instructions, #documents_to_be_provided, #other_condition, #force_majure, #application_law, #additional_info').summernote(summernoteOptions);

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

        // Product selection
        $('#productSelect').on('change', function() {
            var productId = $(this).val();
            var productName = $(this).find(':selected').text();
            if (productId) {
                $('#visualName').val(productName);
                $.get("{{ route('get.product_specs.export', '') }}/" + productId, function(data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#visualName').val('');
                $('#specificationsSection').hide();
            }
        });

        // Sauda / Quotation Autofill Logic
        function triggerAutofill() {
            let quotationId = $('select[name="quotation_id"]').val();
            let saudaId = $('select[name="export_soda_id"]').val();

            if (quotationId) {
                $.get("{{ route('export-order.get-quotation-details', '') }}/" + quotationId, function(data) {
                    fillFormFromData(data);
                });
            } else if (saudaId) {
                $.get("{{ route('quotation.get-sauda-details', '') }}/" + saudaId, function(data) {
                    fillFormFromData(data);
                });
            }
        }

        window.allQuotations = @json($quotations->map(function($q) {
            return [
                'id' => $q->id,
                'export_soda_id' => $q->export_soda_id,
                'text' => ($q->reference ?? ('#' . $q->id)) . ' - ' . ($q->product->name ?? '')
            ];
        }));

        function addPackingRowsFromData(items) {
            let container = $('#packingItemsContainer');
            let firstRow = container.find('.packing-item').first();

            // Remove all rows except the first
            container.find('.packing-item').not(':first').remove();

            items.forEach(function(item, index) {
                let row;
                if (index === 0) {
                    row = firstRow;
                } else {
                    row = firstRow.clone();
                    row.attr('data-index', index);
                    row.find('.card-header h6').text('Packing Row #' + (index + 1));
                    row.find('.select2-container').remove();
                    row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
                    row.find('input').val(0);
                    row.find('input[type="date"]').val('');
                    row.find('select').val('');
                    row.find('.sub-packing-items-container').empty().attr('data-index', index);
                    row.find('.add-sub-packing-item').attr('data-index', index);
                    container.append(row);
                    row.find('.select2').select2({ width: '100%' });
                }

                // Set select dropdowns
                if (item.bag_type_id)
                    row.find('select[name*="bag_type_id"]').val(item.bag_type_id).trigger('change.select2');
                if (item.bag_packing_id)
                    row.find('select[name*="bag_packing_id"]').val(item.bag_packing_id).trigger('change.select2');

                // Set numeric inputs
                if (item.bag_size)    row.find('input.bag-size').val(item.bag_size);
                if (item.metric_tons) row.find('input.metric-tons').val(item.metric_tons);
                if (item.no_of_bags)  row.find('input.no_of_bags').val(item.no_of_bags);
                if (item.extra_bags)  row.find('input.extra-bags').val(item.extra_bags);
                if (item.empty_bags)  row.find('input.empty-bags').val(item.empty_bags);
                if (item.rate)        row.find('input.rate-per-ton').val(item.rate);

                row.find('input.metric-tons, input.bag-size, input.no_of_bags').trigger('input');
            });

            reindexAll();
        }

        function fillFormFromData(data) {
            // Basic fields
            if (data.buyer_id)    $('select[name="buyer_id"]').val(data.buyer_id).trigger('change');
            if (data.product_id)  $('select[name="product_id"]').val(data.product_id).trigger('change');
            if (data.visual_name) $('input[name="visual_name"], #visualName').val(data.visual_name);

            // Dates
            if (data.shipment_delivery_date_from) $('input[name="shipment_delivery_date_from"]').val(data.shipment_delivery_date_from);
            if (data.shipment_delivery_date_to)   $('input[name="shipment_delivery_date_to"]').val(data.shipment_delivery_date_to);

            // Export sidebar dropdowns
            if (data.incoterm_id)          $('select[name="incoterm_id"]').val(data.incoterm_id).trigger('change.select2');
            if (data.packing_type)         $('select[name="packing_type"]').val(data.packing_type).trigger('change.select2');
            if (data.mode_of_term_id)      $('select[name="mode_of_term_id"]').val(data.mode_of_term_id).trigger('change.select2');
            if (data.mode_of_transport_id) $('select[name="mode_of_transport_id"]').val(data.mode_of_transport_id).trigger('change.select2');
            if (data.origin_country_id)    $('select[name="origin_country_id"]').val(data.origin_country_id).trigger('change.select2');
            if (data.port_of_discharge_id) $('select[name="port_of_discharge_id"]').val(data.port_of_discharge_id).trigger('change.select2');
            if (data.port_of_loading_id)   $('select[name="port_of_loading_id"]').val(data.port_of_loading_id).trigger('change.select2');
            if (data.hs_code_id)           $('select[name="hs_code_id"]').val(data.hs_code_id).trigger('change.select2');
            if (data.partial_payment)      $('select[name="partial_payment"]').val(data.partial_payment).trigger('change.select2');
            if (data.transhipment)         $('select[name="transhipment"]').val(data.transhipment).trigger('change.select2');
            if (data.part_shipment)        $('select[name="part_shipment"]').val(data.part_shipment).trigger('change.select2');
            if (data.insurance_covered_by) $('select[name="insurance_covered_by"]').val(data.insurance_covered_by).trigger('change.select2');

            // Export sidebar numeric fields
            if (data.advance_payment) $('input[name="advance_payment"]').val(data.advance_payment);
            if (data.payment_days)    $('input[name="payment_days"]').val(data.payment_days);
            if (data.currency_id)     $('select[name="currency_id"]').val(data.currency_id).trigger('change.select2');
            if (data.currency_rate)   $('input[name="currency_rate"], #currencyRate').val(data.currency_rate);

            // Packing rows
            if (data.packing_items && data.packing_items.length > 0) {
                addPackingRowsFromData(data.packing_items);
            }
        }

        $('select[name="export_soda_id"]').on('change', function() {
            let selectedSauda = $(this).val();
            let quotationSelect = $('select[name="quotation_id"]');
            
            let currentVal = quotationSelect.val();
            quotationSelect.empty();
            quotationSelect.append(new Option('Select Quotation', '', false, false));
            
            window.allQuotations.forEach(function(q) {
                if (!selectedSauda || q.export_soda_id == selectedSauda) {
                    let opt = new Option(q.text, q.id, false, false);
                    opt.setAttribute('data-sauda-id', q.export_soda_id);
                    quotationSelect.append(opt);
                }
            });
            
            quotationSelect.val(currentVal).trigger('change.select2');
            
            if (!quotationSelect.find('option:selected').length) {
                quotationSelect.val('').trigger('change.select2');
            }

            triggerAutofill();
        });

        $('select[name="quotation_id"]').on('change', function() {
            triggerAutofill();
        });

        // Packing Row Management
        $('#addPackingItem').click(function() {
            const container = $('#packingItemsContainer');
            const rowCount = container.find('.packing-item').length;
            const newRow = container.find('.packing-item').first().clone();
            
            // Clean up new row
            newRow.attr('data-index', rowCount);
            newRow.find('.card-header h6').text('Packing Row #' + (rowCount + 1));
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            newRow.find('input').val(0);
            newRow.find('input[type="date"]').val('');
            newRow.find('select').val('').trigger('change');
            newRow.find('.sub-packing-items-container').empty().attr('data-index', rowCount);
            newRow.find('.add-sub-packing-item').attr('data-index', rowCount);
            
            container.append(newRow);
            newRow.find('.select2').select2({ width: '100%' });
            reindexAll();
        });

        // Calculations (JobOrder Style)
        $(document).off('input', '.no_of_bags, .bag-size, .metric-tons, .extra-bags, .empty-bags, .rate-per-ton, .stuffing, .containers').on('input', '.no_of_bags, .bag-size, .metric-tons, .extra-bags, .empty-bags, .rate-per-ton, .stuffing, .containers', function() {
            let sourceField = null;
            if ($(this).hasClass('no_of_bags')) sourceField = 'no_of_bags';
            if ($(this).hasClass('bag-size')) sourceField = 'bag-size';
            if ($(this).hasClass('metric-tons')) sourceField = 'metric-tons';
            if ($(this).hasClass('stuffing')) sourceField = 'stuffing';
            if ($(this).hasClass('containers')) sourceField = 'containers';
            
            calculateMainRow($(this).closest('.packing-item'), sourceField);
        });

        $(document).off('input', '.sub-no-of-primary-bags, .sub-no-of-bags, .sub-empty-bags, .sub-extra-bags').on('input', '.sub-no-of-primary-bags, .sub-no-of-bags, .sub-empty-bags, .sub-extra-bags', function() {
            const subRow = $(this).closest('tr');
            const mainRow = $(this).closest('.packing-item');
            calculateSubItemNoOfBags(subRow, mainRow);
        });

        $(document).off('change', '.sub-bag-size-id').on('change', '.sub-bag-size-id', function() {
            const subRow = $(this).closest('tr');
            const mainRow = $(this).closest('.packing-item');
            calculateSubItemNoOfBags(subRow, mainRow);
        });

        $(document).off('click', '.remove-sub-item').on('click', '.remove-sub-item', function() {
            const row = $(this).closest('.packing-item');
            $(this).closest('tr').remove();
            reindexAll();
            calculateMainRow(row);
        });

        $(document).off('click', '.add-sub-packing-item').on('click', '.add-sub-packing-item', function() {
            const parentIndex = $(this).attr('data-index');
            const container = $(this).closest('.card').find('.sub-packing-items-container');
            const subIndex = container.find('tr').length;
            
            const html = `
                <tr class="sub-packing-item">
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][bag_type_id]" class="form-control form-control-sm select2">
                            <option value="">Select Bag Type</option>
                            @foreach ($bagTypes as $bt)
                                <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][bag_size_id]" class="form-control form-control-sm select2 sub-bag-size-id">
                            <option value="">Select Size</option>
                            @foreach ($bagSizes as $sz)
                                <option value="{{ $sz->id }}" data-size="{{ $sz->size }}">{{ $sz->size }} kg</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][no_of_primary_bags]" class="form-control form-control-sm sub-no-of-primary-bags" value="0"></td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][no_of_bags]" class="form-control form-control-sm sub-no-of-bags" value="0" readonly></td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][empty_bags]" class="form-control form-control-sm sub-empty-bags" value="0"></td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][extra_bags]" class="form-control form-control-sm sub-extra-bags" value="0"></td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][empty_bag_weight]" class="form-control form-control-sm sub-empty-bag-weight" value="0" min="0" step="0.01"></td>
                    <td><input type="number" name="packing_items[${parentIndex}][sub_items][${subIndex}][total_bags]" class="form-control form-control-sm sub-total-bags" value="0" readonly></td>
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][stitching_id]" class="form-control form-control-sm select2">
                            <option value="">Select</option>
                            @foreach ($stitchings as $st) <option value="{{ $st->id }}">{{ $st->name }}</option> @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][bag_color_id]" class="form-control form-control-sm select2">
                            <option value="">Select</option>
                            @foreach ($bagColors as $c) <option value="{{ $c->id }}">{{ $c->color }}</option> @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][brand_id]" class="form-control form-control-sm select2">
                            <option value="">Select</option>
                            @foreach ($brands as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="packing_items[${parentIndex}][sub_items][${subIndex}][thread_color_id]" class="form-control form-control-sm select2">
                            <option value="">Select</option>
                            @foreach ($threadColors as $tc) <option value="{{ $tc->id }}">{{ $tc->color }}</option> @endforeach
                        </select>
                    </td>
                    <td><input type="file" name="packing_items[${parentIndex}][sub_items][${subIndex}][attachment]" class="form-control form-control-sm"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-sub-item"><i class="ft-x"></i></button>
                    </td>
                </tr>
            `;
            
            const $html = $(html);
            container.append($html);
            $html.find('.select2').select2({ width: '100%' });
            reindexAll();
        });

        $(document).off('click', '.remove-packing-item').on('click', '.remove-packing-item', function() {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                reindexAll();
                calculateGrandTotals();
            } else {
                alert('At least one packing row is required.');
            }
        });

        $(document).on('input', '.no_of_bags, .bag-size', function() {
            const mainRow = $(this).closest('.packing-item');
            mainRow.find('.sub-packing-item').each(function() {
                calculateSubItemNoOfBags($(this), mainRow);
            });
        });

        function calculateSubItemNoOfBags(subRow, mainRow) {
            const noOfBagsMain = parseInt(mainRow.find('.no_of_bags').val()) || 0;
            const noOfPrimaryBags = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;
            const bagSize = parseFloat(subRow.find('.sub-bag-size-id option:selected').data('size')) || 0;

            if (noOfPrimaryBags > 0) {
                // If main no_of_bags exists, suggest a breakdown
                if (noOfBagsMain > 0) {
                    const suggestedBags = Math.floor(noOfBagsMain / noOfPrimaryBags);
                    subRow.find('.sub-no-of-bags').val(suggestedBags);
                }
                
                const noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
                const emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
                const extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;
                subRow.find('.sub-total-bags').val(noOfBags + emptyBags + extraBags);
            }
            
            // NO sumUpSubItemsToMain(mainRow); // One-way flow: Main -> Sub
        }

                // Removed sumUpSubItemsToMain logic to avoid circular dependency

        function calculateMainRow(row, sourceField = null) {
            let noOfBags = parseInt(row.find('.no_of_bags').val()) || 0;
            let bagSize = parseFloat(row.find('.bag-size').val()) || 0;
            let metricTons = parseFloat(row.find('.metric-tons').val()) || 0;
            let stuffing = parseFloat(row.find('.stuffing').val()) || 0;
            let containers = parseInt(row.find('.containers').val()) || 0;

            if (sourceField === 'no_of_bags' || sourceField === 'bag-size') {
                metricTons = (noOfBags * bagSize) / 1000;
                row.find('.metric-tons').val(metricTons.toFixed(3));
            } else if (sourceField === 'metric-tons') {
                if (bagSize > 0) {
                    noOfBags = Math.round((metricTons * 1000) / bagSize);
                    row.find('.no_of_bags').val(noOfBags);
                }
            }

            // Bi-directional Stuffing & Containers based on MT
            if (sourceField === 'metric-tons' || sourceField === 'no_of_bags' || sourceField === 'bag-size') {
                if (containers > 0) {
                    stuffing = metricTons / containers;
                    row.find('.stuffing').val(stuffing.toFixed(3));
                } else if (stuffing > 0) {
                    containers = Math.ceil(metricTons / stuffing);
                    row.find('.containers').val(containers);
                }
            } else if (sourceField === 'stuffing') {
                if (stuffing > 0) {
                    containers = Math.ceil(metricTons / stuffing);
                    row.find('.containers').val(containers);
                }
            } else if (sourceField === 'containers') {
                if (containers > 0) {
                    stuffing = metricTons / containers;
                    row.find('.stuffing').val(stuffing.toFixed(3));
                }
            }

            const extraBags = parseInt(row.find('.extra-bags').val()) || 0;
            const emptyBags = parseInt(row.find('.empty-bags').val()) || 0;
            const totalBags = noOfBags + extraBags + emptyBags;

            row.find('.total-bags').val(totalBags);
            row.find('.total-kgs').val((metricTons * 1000).toFixed(2));

            // Sync with Sub Items
            row.find('.sub-packing-item').each(function() {
                calculateSubItemNoOfBags($(this), row);
            });

            // Financials
            const rate = parseFloat(row.find('.rate-per-ton').val()) || 0;
            const amount = metricTons * rate;
            row.find('.item-amount').val(amount.toFixed(2));

            const currencyRate = parseFloat($('#currencyRate').val()) || 1;
            row.find('.item-amount-pkr').val((amount * currencyRate).toFixed(2));

            // Re-sync grand totals
            calculateGrandTotals();
        }

        function calculateGrandTotals() {
            let grandAmount = 0;
            let grandMT = 0;
            $('.packing-item').each(function() {
                grandAmount += parseFloat($(this).find('.item-amount').val()) || 0;
                grandMT += parseFloat($(this).find('.metric-tons').val()) || 0;
            });

            // Commission logic
            let percentage = parseFloat($('#commission_percentage').val()) || 0;
            let amtPerTon = parseFloat($('#commission_amount_per_ton').val()) || 0;

            if (percentage > 0) {
                let commission = (grandAmount * percentage) / 100;
                $('#commission').val(commission.toFixed(2));
                $('#commission_amount_per_ton').val(grandMT > 0 ? (commission / grandMT).toFixed(2) : 0);
            } else if (amtPerTon > 0) {
                let commission = grandMT * amtPerTon;
                $('#commission').val(commission.toFixed(2));
                $('#commission_percentage').val(grandAmount > 0 ? ((commission / grandAmount) * 100).toFixed(2) : 0);
            }
        }

        $(document).on('input', '#commission_percentage, #commission_amount_per_ton', function() {
            calculateGrandTotals();
        });

        function reindexAll() {
            $('#packingItemsContainer .packing-item').each(function(pIdx) {
                $(this).attr('data-index', pIdx);
                $(this).find('.card-header h6').text('Packing Row #' + (pIdx + 1));
                $(this).find('.add-sub-packing-item').attr('data-index', pIdx);
                
                $(this).find('input, select, textarea').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        // Correctly replace parent index and preserve sub_items structure
                        if (name.includes('[sub_items]')) {
                            name = name.replace(/packing_items\[\d+\]/, `packing_items[${pIdx}]`);
                        } else {
                            name = name.replace(/packing_items\[\d+\]/, `packing_items[${pIdx}]`);
                        }
                        $(this).attr('name', name);
                    }
                });

                $(this).find('.sub-packing-items-container tr').each(function(sIdx) {
                    $(this).find('input, select, textarea').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/\[sub_items\]\[\d+\]/, `[sub_items][${sIdx}]`);
                            $(this).attr('name', name);
                        }
                    });
                });
            });
        }

        // Currency handling
        $('#currencySelect').on('change', function() {
            let rate = $(this).find(':selected').data('rate') || '';
            $('#currencyRate').val(rate);
            $('.packing-item').each(function() {
                calculateMainRow($(this));
            });
        });

        // Bank Details
        $('select[name="buyer_id"]').on('change', function() {
            let customerId = $(this).val();
            $('#bankSelect').html('<option value="">-- Select Bank --</option>').trigger('change');
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
    });
</script>


