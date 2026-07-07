<style>
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
    }

    .show-content-box {
        max-height: 180px;
        overflow-y: auto;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 8px;
    }
</style>

<div class="row form-mar">
    <div class="col-8">

        {{-- ====== BASIC INFORMATION ====== --}}
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Quotation#:</label>
                        <input type="text" class="form-control"
                            value="{{ $exportOrder->quotation ? $exportOrder->quotation_id . ' - ' . ($exportOrder->quotation->buyer->name ?? 'N/A') . ' (' . ($exportOrder->quotation->product->name ?? '') . ')' : '-' }}"
                            readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contract No#:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Contract No#</button>
                            </div>
                            <input type="text" class="form-control" value="{{ $exportOrder->voucher_no }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contract Date:</label>
                        <input type="text" class="form-control"
                            value="{{ $exportOrder->voucher_date ? $exportOrder->voucher_date->format('d-M-Y') : '-' }}"
                            readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference No#:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->contract_no ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference Date:</label>
                        <input type="text" class="form-control"
                            value="{{ $exportOrder->voucher_heading ? (is_object($exportOrder->voucher_heading) ? $exportOrder->voucher_heading->format('d-M-Y') : $exportOrder->voucher_heading) : '-' }}"
                            readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Buyer's Name:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->buyer->name ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Marking/Labeling:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->marking_labeling ?? '-' }}"
                            readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Delivery Date From:</label>
                        <input type="text" class="form-control"
                            value="{{ $exportOrder->shipment_delivery_date_from ? $exportOrder->shipment_delivery_date_from->format('d-M-Y') : '-' }}"
                            readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Delivery Date To:</label>
                        <input type="text" class="form-control"
                            value="{{ $exportOrder->shipment_delivery_date_to ? $exportOrder->shipment_delivery_date_to->format('d-M-Y') : '-' }}"
                            readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mt-2">
            <h6 class="header-heading-sepration mt-3">Discharge Terms</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Discharge Rate:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->discharge_rate ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SHEX/EIU (Discharge) Type:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->discharge_term_type ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SHEX/EIU (Discharge) Value:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->discharge_shex_eiu ?? '-' }}" readonly>
                    </div>
                </div>
            </div>

            <h6 class="header-heading-sepration mt-3">Load Terms</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Minimum Daily Rate:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->minimum_daily_rate ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SHEX/EIU (Min Daily) Type:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->load_term_type ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SHEX/EIU (Min Daily) Value:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->minimum_daily_shex_eiu ?? '-' }}" readonly>
                    </div>
                </div>
            </div>

            <h6 class="header-heading-sepration mt-3">Additional Details</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fumigation By:</label>
                        @php
                            $fNames = [];
                            if (is_array($exportOrder->fumigation_by)) {
                                $fNames = $fumigationCompanies->whereIn('id', $exportOrder->fumigation_by)->pluck('name')->toArray();
                            }
                        @endphp
                        <input type="text" class="form-control" value="{{ count($fNames) ? implode(', ', $fNames) : '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Inspection By:</label>
                        @php
                            $iNames = [];
                            if (is_array($exportOrder->inspection_by)) {
                                $iNames = $inspectionCompanies->whereIn('id', $exportOrder->inspection_by)->pluck('name')->toArray();
                            }
                        @endphp
                        <input type="text" class="form-control" value="{{ count($iNames) ? implode(', ', $iNames) : '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Gafta:</label>
                        <input type="text" class="form-control" value="{{ $exportOrder->gafta->name ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Arbitration Country:</label>
                        @php
                            $scNames = [];
                            if (is_array($exportOrder->shipment_country)) {
                                $scNames = \App\Models\Export\ShipmentCountry::whereIn('id', $exportOrder->shipment_country)->pluck('name')->toArray();
                            }
                        @endphp
                        <input type="text" class="form-control" value="{{ count($scNames) ? implode(', ', $scNames) : '-' }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- Consignee Details --}}
        @if ($exportOrder->consignee)
            <div class="col-md-12 mt-2">
                <h6 class="header-heading-sepration">Consignee Details</h6>
                <div class="card bg-light border-0 shadow-sm" style="border-radius: 8px; background-color: #e0e0e0;">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><small
                                        class="text-black-50 d-block">Name</small><strong>{{ $exportOrder->consignee->name ?: '-' }}</strong>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><small class="text-black-50 d-block">Contact
                                        Person</small><strong>{{ $exportOrder->consignee->contact_person ?: '-' }}</strong>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><small
                                        class="text-black-50 d-block">Contact</small><strong>{{ $exportOrder->consignee->contact ?: '-' }}</strong>
                                </p>
                            </div>
                            <div class="col-md-6 mt-2">
                                <p class="mb-1"><small
                                        class="text-black-50 d-block">Email</small><strong>{{ $exportOrder->consignee->email ?: '-' }}</strong>
                                </p>
                            </div>
                            <div class="col-md-12 mt-2">
                                <p class="mb-0"><small
                                        class="text-black-50 d-block">Address</small><span>{{ $exportOrder->consignee->address ?: '-' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ====== PRODUCT & SPECS ====== --}}
        <div class="col-md-12 mt-2">
            <h6 class="header-heading-sepration">Commodity/Product</h6>
            <div class="form-group">
                <label>Commodity/Product:</label>
                <select class="form-control select2" disabled>
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ $exportOrder->product_id == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mt-2">
                <label>Visual Name:</label>
                <input type="text" class="form-control" value="{{ $exportOrder->visual_name ?? '-' }}" readonly>
            </div>
        </div>

        {{-- Specifications --}}
        @if($exportOrder->specifications->count())
            <div class="col-md-12 mt-2">
                <h6 class="header-heading-sepration">Specifications</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th width="40%">Specification Name</th>
                                <th width="30%">Value</th>
                                <th width="30%">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exportOrder->specifications as $spec)
                                <tr>
                                    <td><strong>{{ $spec->spec_name }}</strong></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" value="{{ $spec->spec_value ?? 0 }}" class="form-control"
                                                readonly>
                                            <div class="input-group-prepend">
                                                <button class="btn btn-secondary"
                                                    type="button">{{ $spec->productSlabType->qc_symbol ?? 'N/A' }}</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control"
                                            value="{{ ucfirst($spec->value_type ?? 'min') }}" readonly></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="col-md-12 mt-2">
            <div class="form-group">
                <label>Other Specification:</label>
                <textarea class="form-control" rows="4" readonly>{{ $exportOrder->other_specifications }}</textarea>
            </div>
        </div>



        <div id="packingDetailsAnchor"></div>

        {{-- ====== BANK DETAILS ====== --}}
        <div class="row px-2 mt-2">
            {{-- Beneficiary Bank --}}
            <div class="col-md-12">
                <div class="p-3">
                    <h5 class="mb-3"><strong>Beneficiary Bank Details</strong></h5>
                    <div class="row">
                        @php $cBank = $exportOrder->customerBank; @endphp
                        <div class="col-md-12 mb-2">
                            <label>Bank Selected:</label>
                            <input type="text" class="form-control"
                                value="{{ $cBank ? ($cBank->account_title . ' - ' . $cBank->bank_name) : '-' }}"
                                readonly>
                            <small class="text-muted">Beneficiary Bank Details</small>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Account Title:</label>
                            <input type="text" class="form-control" value="{{ $cBank->account_title ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Bank Name:</label>
                            <input type="text" class="form-control" value="{{ $cBank->bank_name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>IBAN:</label>
                            <input type="text" class="form-control" value="{{ $cBank->iban ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Account No:</label>
                            <input type="text" class="form-control" value="{{ $cBank->account_no ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>SWIFT Code:</label>
                            <input type="text" class="form-control" value="{{ $cBank->swift_code ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Bank Address:</label>
                            <input type="text" class="form-control" value="{{ $cBank->bank_address ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>Description:</label>
                            <textarea class="form-control" rows="2" readonly>{{ $cBank->description ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Correspondent Bank --}}
            <div class="col-md-12">
                <div class="p-3">
                    <h5 class="mb-3"><strong>Correspondent Bank Details</strong></h5>
                    <div class="row">
                        @php $corrBank = $exportOrder->correspondentBank; @endphp
                        <div class="col-md-12 mb-2">
                            <label>Select Correspondent Bank:</label>
                            <select class="form-control select2" disabled>
                                <option value="">-- Select Bank --</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ $exportOrder->correspondent_bank_id == $bank->id ? 'selected' : '' }}>{{ $bank->account_title }} - {{ $bank->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Account Title:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->account_title ?? '-' }}"
                                readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Bank Name:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->bank_name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>IBAN:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->iban ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Account No:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->account_no ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>SWIFT Code:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->swift_code ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label>Bank Address:</label>
                            <input type="text" class="form-control" value="{{ $corrBank->bank_address ?? '-' }}"
                                readonly>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>Description:</label>
                            <textarea class="form-control" rows="2"
                                readonly>{{ $corrBank->description ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== SHIPPING INSTRUCTIONS ====== --}}
        <div class="col-md-12 mb-4">
            <label>Shipping Instruction:</label>
            <div class="show-content-box">{!! $exportOrder->shipping_instructions !!}</div>
        </div>

        {{-- ====== BROKER ====== --}}
        <div class="col-md-12 mb-3">
            <div class="form-group">
                <label>Broker:</label>
                <select class="form-control select2" disabled>
                    <option value="">Select Broker</option>
                    @foreach ($brokers as $broker)
                        <option value="{{ $broker->id }}" {{ $exportOrder->broker_id == $broker->id ? 'selected' : '' }}>
                            {{ $broker->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ====== DOCUMENTS TO BE PROVIDED ====== --}}
        <div class="col-md-12 mb-3">
            <label>Documents to be provided:</label>
            <div class="show-content-box">{!! $exportOrder->documents_to_be_provided !!}</div>
        </div>

        {{-- ====== OTHER CONDITIONS / FORCE MAJURE / APPLICATION LAW ====== --}}
        <div class="row p-2">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Other Condition:</label>
                    <div class="show-content-box">{!! $exportOrder->other_condition !!}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Force Majure:</label>
                    <div class="show-content-box">{!! $exportOrder->force_majure ?? '<ol><li>Dummy text for Force Majure</li></ol>' !!}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Governing Law:</label>
                    <div class="show-content-box">{!! $exportOrder->application_law ?? '<ol><li>Dummy text for Governing Law</li></ol>' !!}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Confidentiality:</label>
                    <div class="show-content-box">{!! $exportOrder->confidentiality ?? '<ol><li>Dummy text for Confidentiality</li></ol>' !!}</div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Additional Info:</label>
                    <div class="show-content-box">{!! $exportOrder->additional_info !!}</div>
                </div>
            </div>
        </div>

        {{-- ====== COMMISSION ====== --}}
        <div class="mt-2 px-2">
            <h6 class="header-heading-sepration">Commission</h6>
            @php
                $totalMt = $exportOrder->packingItems->sum('metric_tons');
                $totalAmt = $exportOrder->packingItems->sum('amount');
                $commission = $exportOrder->commission ?? 0;
                $commPct = $totalAmt > 0 ? ($commission / $totalAmt) * 100 : ($exportOrder->commission_percentage ?? 0);
                $amtPerTon = $totalMt > 0 ? ($commission / $totalMt) : ($exportOrder->commission_amount_per_ton ?? 0);
            @endphp
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Commission (%):</label>
                        <input type="text" class="form-control" value="{{ number_format($commPct, 2) }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Amt/Ton:</label>
                        <input type="text" class="form-control" value="{{ number_format($amtPerTon, 2) }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Commission:</label>
                        <input type="text" class="form-control font-weight-bold"
                            value="{{ number_format($commission, 2) }}" readonly>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end col-8 --}}

    {{-- ====== RIGHT SIDEBAR: EXPORT DETAILS ====== --}}
    <div class="col-4">
        <h6 class="header-heading-sepration">Export</h6>
        <div class="table-responsive">
            <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                <tr>
                    <td style="width:35%;font-weight:bold;vertical-align:middle;">INCOTERMS</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm->id }}" {{ $exportOrder->incoterm_id == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @if(str_contains(strtoupper(optional($exportOrder->incoterm)->name), 'FOB'))
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">THC</td>
                    <td>
                        <input type="text" class="form-control" value="{{ $exportOrder->fob_account ?? '-' }}" readonly>
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PACKING TYPE</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            <option value="In Conatiner" {{ $exportOrder->packing_type == 'In Conatiner' ? 'selected' : '' }}>IN CONTAINER</option>
                            <option value="In Bulk" {{ $exportOrder->packing_type == 'In Bulk' ? 'selected' : '' }}>IN
                                BULK</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">MODE OF TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($modeofterms as $term)
                                <option value="{{ $term->id }}" {{ $exportOrder->mode_of_term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">MODE OF TRANSPORT</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($modeoftransport as $transport)
                                <option value="{{ $transport->id }}" {{ $exportOrder->mode_of_transport_id == $transport->id ? 'selected' : '' }}>{{ $transport->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">ORIGIN</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ $exportOrder->origin_country_id == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PORT OF DISCHARGE</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($ports as $port)
                                <option value="{{ $port->id }}" {{ $exportOrder->port_of_discharge_id == $port->id ? 'selected' : '' }}>{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PORT OF LOADING</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($ports as $port)
                                <option value="{{ $port->id }}" {{ $exportOrder->port_of_loading_id == $port->id ? 'selected' : '' }}>{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">HS CODE</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($hscodes as $hs)
                                <option value="{{ $hs->id }}" {{ $exportOrder->hs_code_id == $hs->id ? 'selected' : '' }}>
                                    {{ $hs->code }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PARTIAL PAYMENT</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            <option value="Yes" {{ $exportOrder->partial_payment == 'Yes' ? 'selected' : '' }}>YES
                            </option>
                            <option value="No" {{ $exportOrder->partial_payment == 'No' ? 'selected' : '' }}>NO</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">TRANSHIPMENT</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            <option value="shall be permitted" {{ $exportOrder->transhipment == 'shall be permitted' ? 'selected' : '' }}>SHALL BE PERMITTED</option>
                            <option value="shall not be permitted" {{ $exportOrder->transhipment == 'shall not be permitted' ? 'selected' : '' }}>SHALL NOT BE PERMITTED</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PART SHIPMENT</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            <option value="shall be permitted" {{ $exportOrder->part_shipment == 'shall be permitted' ? 'selected' : '' }}>SHALL BE PERMITTED</option>
                            <option value="shall not be permitted" {{ $exportOrder->part_shipment == 'shall not be permitted' ? 'selected' : '' }}>SHALL NOT BE PERMITTED</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">INSURANCE COVERED BY</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            <option value="Buyer" {{ $exportOrder->insurance_covered_by == 'Buyer' ? 'selected' : '' }}>
                                BUYER</option>
                            <option value="Supplier" {{ $exportOrder->insurance_covered_by == 'Supplier' ? 'selected' : '' }}>SELLER</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">ADVANCE PAYMENT(%)</td>
                    <td>
                        <input type="text" class="form-control" value="{{ $exportOrder->advance_payment }}" readonly>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PAYMENT DAYS</td>
                    <td>
                        <input type="text" class="form-control" value="{{ $exportOrder->payment_days }}" readonly>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">CURRENCY</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="">Select</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ $exportOrder->currency_id == $currency->id ? 'selected' : '' }}>{{ $currency->currency_name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>{{-- end col-4 --}}

    {{-- ====== PACKING DETAILS (Full Width) ====== --}}
    <div class="col-md-12 mt-4" id="packingDetailsSection">
        <h6 class="header-heading-sepration">Packing Details</h6>

        @forelse ($exportOrder->packingItems as $itemIndex => $item)
            <div class="packing-item border rounded bg-white mb-3 p-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="mb-0 font-weight-bold grey">Packing Row #{{ $itemIndex + 1 }}</h6>
                </div>
                <!-- <div class="card-body"> -->
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Brand:</label>
                            <input type="text" class="form-control" value="{{ $item->brand->name ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Type:</label>
                            <input type="text" class="form-control" value="{{ $item->bagType->name ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Packing Type:</label>
                            <input type="text" class="form-control" value="{{ $item->bagPacking->name ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Condition:</label>
                            <input type="text" class="form-control" value="{{ $item->bagCondition->name ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Color:</label>
                            <input type="text" class="form-control" value="{{ $item->bagColor->color ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Thread Color:</label>
                            <input type="text" class="form-control" value="{{ $item->threadColor->color ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Stitching:</label>
                            <input type="text" class="form-control" value="{{ $item->stitching->name ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Size (kg):</label>
                            <input type="text" class="form-control" value="{{ number_format($item->bag_size, 2) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>No. of Bags:</label>
                            <input type="text" class="form-control font-weight-bold"
                                value="{{ number_format($item->no_of_bags, 0) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Extra Bags:</label>
                            <input type="text" class="form-control" value="{{ $item->extra_bags }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Extra Bags %:</label>
                            <input type="text" class="form-control"
                                value="{{ number_format((float) ($item->extra_bags_percentage ?? 0), 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Empty Bags:</label>
                            <input type="text" class="form-control" value="{{ $item->empty_bags }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Empty Bags %:</label>
                            <input type="text" class="form-control"
                                value="{{ number_format((float) ($item->empty_bags_percentage ?? 0), 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Qty (MT):</label>
                            <input type="text" class="form-control font-weight-bold text-primary"
                                value="{{ number_format($item->metric_tons, 3) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Total Bags:</label>
                            <input type="text" class="form-control" value="{{ number_format($item->total_bags, 0) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Stuffing/Cont (MT):</label>
                            <input type="text" class="form-control"
                                value="{{ number_format($item->stuffing_in_container, 3) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Containers:</label>
                            <input type="text" class="form-control" value="{{ $item->no_of_containers }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rate/Ton:</label>
                            <input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Amount:</label>
                            <input type="text" class="form-control font-weight-bold"
                                value="{{ number_format($item->amount, 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Min Weight Empty Bags:</label>
                            <input type="text" class="form-control"
                                value="{{ number_format($item->min_weight_empty_bags, 2) }}" readonly>
                        </div>
                    </div>
                </div>

                {{-- Master Packing Sub-items --}}
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
                                                <th style="min-width: 100px;">Empty Bags %</th>
                                                <th style="min-width: 90px;">Extra Bags</th>
                                                <th style="min-width: 100px;">Extra Bags %</th>
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
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->bagType->name ?? '-' }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->bagSize->size ?? '-' }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->no_of_primary_bags }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->no_of_bags }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->empty_bags }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ number_format((float) ($sub->empty_bags_percentage ?? 0), 2) }}"
                                                            readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->extra_bags }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ number_format((float) ($sub->extra_bags_percentage ?? 0), 2) }}"
                                                            readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->empty_bag_weight }}" readonly></td>
                                                    <td class="p-1"><input type="text"
                                                            class="form-control form-control-sm font-weight-bold"
                                                            value="{{ $sub->total_bags }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->stitching->name ?? '-' }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->bagColor->color ?? '-' }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->brand->name ?? '-' }}" readonly></td>
                                                    <td class="p-1"><input type="text" class="form-control form-control-sm"
                                                            value="{{ $sub->threadColor->color ?? '-' }}" readonly></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <!-- </div> -->
            </div>
        @empty
            <div class="alert alert-light">No packing items found.</div>
        @endforelse
    </div>{{-- end packing col-12 --}}

</div>{{-- end row --}}

<div class="row bottom-button-bar">
    <div class="col-12 mb-3">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        @if (strtolower($exportOrder->am_approval_status ?? '') === 'approved')
            <a href="{{ route('export-order.print', $exportOrder->id) }}" target="_blank" class="btn btn-primary">Print</a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-12">
        <x-approval-status :model="$exportOrder" />
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#packingDetailsSection').insertAfter('#packingDetailsAnchor');
        $('.select2').select2({ width: '100%' });
    });
</script>