@php
    $isSlabs = false;
    $isCompulsury = false;

    foreach ($samplingRequestCompulsuryResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isCompulsury = true;
    }

    foreach ($samplingRequestResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isSlabs = true;
    }
@endphp
<style>
    [readonly] {
        background-color: white !important
    }

    .flex-head {
        display: flex;
        align-items: center;
        justify-content: left;
    }

    .add-main1 ul {
        display: flex;
        align-items: center;
        justify-content: space-evenly;
    }

    .logo img {
        width: 67%;
    }

    .add-main1 ul {
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        padding: 0;
        list-style: none;
    }

    .head-add1 h5 {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .head-add1 p {
        font-size: 14px;
        margin-bottom: 8px;
    }

    .add-main2 {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    a.btn.btn-a {
        border: 2px solid #ddd;
        color: #000;
    }

    a.btn.btn-a:hover {
        box-shadow: 0 2px 7px rgba(0, 0, 0, 0.28);
        cursor: pointer;
        background: #008749;
        color: #fff !important;
    }

    .logo p {
        font-weight: bold;
    }

    #modal-sidebar.open {
        width: 100% !important;
    }
</style>
<link rel="stylesheet" href="{{ asset('css/arrival-slip-styles.css') }}">
<form action="{{ route('arrival-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-slip') }}" />
    <div class="row form-mar">
        <div class="pri" id="printSection">
            <div class=" col-sm-12 col-md-12 col-xs-12 auth-img-bg p-3">
                <div class="flex-head">
                    <div class="logo">
                        <img src="{{ asset('management/app-assets/img/meskay-logo.png') }}" alt=""
                            class="img-fluid">
                        <p>Original / Duplicate</p>
                    </div>
                    <div class="logo-cont">
                        <div class="add-main1">
                            <ul>
                                <li>
                                    <div class="head-add1">
                                        <h5><u>Head office:</u></h5>
                                        <p>Salma Trade Tower, Tower B, Room # 1511,1512 & 1513,I,I, Chaundrigar Road,
                                            Karachi.</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="head-add1">
                                        <h5><u>Factory:</u></h5>
                                        <p>Plot No A-13,A-15 & A-46, Eastern, Industrial Zone, Port Qasim, Karachi
                                            Pakistan.</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="head-add1">
                                        <h5><u>Retail Outlet:</u></h5>
                                        <p>Shop: No.4, K.A.I Center, Opp City Court, Dandla Bazar, Karachi Ph.
                                            +922332713369,3378</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <hr style="border: 1px solid #ddd;">
                        <div class="add-main2">
                            <div class="head-add1">
                                <p><strong>Tel:</strong> +03012740216,0 <strong>Fax:</strong></p>
                                <p><strong>email:</strong> Info@mft.com.pk, web:www.mft.com.pk</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr style="border: 1px solid #ddd; margin-bottom: 0;">
            </div>

            <div class=" col-sm-12 col-md-12 col-xs-12">
                <div>
                    <!-- Header with company info -->
                    <div style="display: none; margin-bottom: 15px;">
                        <div style="width: 120px; padding-right: 15px;">
                            <img src="{{ asset('images/logo.png') }}" alt="Company Logo" style="max-width: 100%;">
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 12px; line-height: 1.4;">
                                <p><strong>Head office:</strong> Saima Trade Tower, Tower B, Room # 1511,1512 & 1513, I.
                                    I.
                                    Chundrigar Road, Karachi.</p>
                                <p><strong>Factory:</strong> Plot No A-43, A-45 & A-46, Eastern, Industrial Zone, Port
                                    Qasim, Karachi, Pakistan.</p>
                                <p><strong>Retail Outlet:</strong> Shop No. 4, K.A.I Center, Opp City Court, Dandia
                                    Bazar,
                                    Karachi Ph. + 92 21 32733369, 3370</p>
                                <p><strong>Tel: = 03012740216, 0 Fax:</strong></p>
                                <p><strong>Email: info@m6.com.pk, web: www.m6.com.pk</strong></p>
                            </div>
                        </div>
                        <div style="text-align: right; padding-left: 15px; white-space: nowrap;">
                            <strong>Arrival Slip</strong>
                        </div>
                    </div>
                    <!-- Main form table -->
                    <table style="width: 100%; border-collapse: collapse; ">
                        <tr>
                            <td style="width: 2%; padding: 8px;border: none;">Arrival
                                Slip #</td>
                            <td style="width: 12%; padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->unique_no }}" readonly>
                            </td>
                            <td style="width: 2%; padding: 8px;border: none;">Date
                            </td>
                            <td style="width: 12%; padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ now()->format('d-M-Y') }}" readonly>
                            </td>
                            <td style="width: 12%; padding: 8px;border: none;">Truck No.</td>
                            <td style="width: 10%; padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->truck_no ?? 'N/A' }}" readonly>
                            </td>
                            <td style="width: 7%; padding: 8px;border: none;">Bill/T No.</td>
                            <td style="padding: 8px; border: none;" colspan="2">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->bilty_no }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">No. of Bags</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->bags }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Packing</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                                    readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Lot No.</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">Party Name</td>
                            <td style="padding: 8px; border: none;" colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->supplier_name }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Broker Name</td>
                            <td style="padding: 8px; border: none;"colspan="4">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;background: transparent;"
                                    value="{{ $arrivalTicket->broker_name ?? 'N/A' }}" readonly>
                            </td>

                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">Broker 2 Name</td>
                            <td style="padding: 8px;border: none;" colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Broker 3 Name</td>
                            <td style="padding: 8px; border: none;"colspan="4">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>

                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">On A/C of</td>
                            <td style="padding: 8px;border: none;"colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->accounts_of_id ?? 'N/A' }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Station</td>
                            <td style="padding: 8px; border: none;" colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->station->name ?? 'N/A' }}" readonly>
                            </td>
                        </tr>



                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">Commodity</td>
                            <td style="padding: 8px; border: none;"colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->product->name }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Status</td>
                            <td style="padding: 8px;border: none;"colspan="1">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->station->name ?? 'N/A' }}" readonly>
                            </td>
                            <td style="width: 7%; padding: 8px;border: none; ">U/L Slip #</td>
                            <td style="padding: 8px; border: none;" colspan="2">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>

                        </tr>
                        <tr>

                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">Deductions</td>
                            <td style="padding: 8px; border: none;" colspan="3">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->lumpsum_deduction ?? 'N/A' }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Sauda Term</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 15%; padding: 8px;border: none;">Gala No.</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
                            </td>
                            <td style="width: 15%; padding: 8px;border: none;">Godown</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->unloadingLocation->arrivalLocation->name ?? 'N/A' }}"
                                    readonly>
                            </td>
                        </tr>
                    </table>
                    <hr style="border: 1px solid #ddd; margin-bottom: 0;">
                    <div class="row">
                        <div class=" col-sm-8 col-md-8 col-xs-8  ">
                            <!-- Freight Section -->
                            <div style="margin-top: 15px; font-weight: bold; padding: 5px 0;">
                                Freight</div>

                            <table style="width: 100%; border-collapse: collapse; ">
                                <tr>
                                    <td style="width: 15%;  padding: 8px;border: none;">
                                        Filling</td>
                                    <td style="width: 18%; padding: 8px;">
                                        <div style="display: flex; align-items: center;">
                                            <input type="text"
                                                style="width: 40px; border: 1px solid #ddd; padding: 10px 10px; text-align: center;"
                                                value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }}"
                                                readonly>
                                            <span style="padding: 0 5px;">× 10 =</span>
                                            <input type="text"
                                                style="width: 50px; border: 1px solid #ddd; padding: 10px 10px; text-align: center;"
                                                value="{{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"
                                                readonly>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 15%;  padding: 8px;border: none;">
                                        Freight (Rs.)</td>
                                    <td style="width: 18%; padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->freight_written_on_bilty ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="width: 15%;  padding: 8px;border: none;">
                                        Freight per Ton</td>
                                    <td style="width: 18%; padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
                                    </td>
                                </tr>

                                <tr>
                                    <td style=" padding: 8px;border: none;">Karachi Kanta
                                        Charges </td>
                                    <td style="padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->karachi_kanta_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style=" padding: 8px;border: none;">Kanta - Golarchi
                                        Charges
                                    </td>
                                    <td style="padding: 8px;" colspan="3">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->kanta_golarchi_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                </tr>


                                <tr>
                                    <td style=" padding: 8px;border: none;">Other (+)/ Labour Charges</td>
                                    <td style="padding: 8px;" colspan="1">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="2">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ numberToWords($arrivalTicket->freight->other_labour_charges ?? 0) }}"
                                            readonly>
                                    </td>


                                </tr>
                                <tr>

                                    <td style=" padding: 8px;border: none;">Other Deduction
                                    </td>
                                    <td style="padding: 8px;" colspan="1">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
                                    </td>

                                    <td style="padding: 8px;" colspan="2">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ numberToWords($arrivalTicket->freight->other_deduction ?? 0) }}"
                                            readonly>
                                    </td>

                                </tr>

                                <tr>
                                    <td style=" padding: 8px;border: none;">Total Freight Payable (Rs.)</td>
                                    <td style="padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="4">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ numberToWords($arrivalTicket->freight->gross_freight_amount ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=" padding: 8px;border: none;">Unpaid Labour Charge</td>
                                    <td style="padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="4">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=" padding: 8px;border: none;">Final Figure</td>
                                    <td style="padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="4">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic; font-size: 11px;"
                                            value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                            </table>

                            @if ($isCompulsury || $isSlabs)
                                <!-- Sampling Results Section -->
                                <div
                                    style="margin-top: 15px; font-weight: bold; border-bottom: 1px solid #000; padding: 5px 0;">
                                    Sampling Results</div>
                                <table
                                    style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th
                                                style="width: 60%; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">
                                                Parameter</th>
                                            <th
                                                style="width: 40%; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">
                                                Applied Deduction (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (count($samplingRequestResults) != 0)
                                            @foreach ($samplingRequestResults as $slab)
                                                @php
                                                    if (!$slab->applied_deduction) {
                                                        continue;
                                                    }
                                                @endphp
                                                <tr>
                                                    <td style="padding: 8px; border: 1px solid #ddd;">
                                                        {{ $slab->slabType->name }}
                                                    </td>
                                                    <td
                                                        style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                                        {{ $slab->applied_deduction }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2"
                                                    style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #777;">
                                                    No Initial Slabs Found</td>
                                            </tr>
                                        @endif

                                        @if ($isCompulsury)
                                            @if (count($samplingRequestCompulsuryResults) != 0)
                                                @foreach ($samplingRequestCompulsuryResults as $slab)
                                                    @php
                                                        if (!$slab->applied_deduction) {
                                                            continue;
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td style="padding: 8px; border: 1px solid #ddd;">
                                                            {{ $slab->qcParam->name }}</td>
                                                        <td
                                                            style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                                            {{ $slab->applied_deduction }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="2"
                                                        style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #777;">
                                                        No Compulsory Slabs Found</td>
                                                </tr>
                                            @endif
                                        @endif
                                    </tbody>
                                </table>
                            @endif

                        </div>
                        <div class=" col-sm-4 col-md-4  col-xs-4">
                            <!-- Weights Section -->
                            <div style="margin-top: 15px; font-weight: bold;  padding: 5px 0;">
                                Weights</div>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 15%;  padding: 8px;border: none;">Gross
                                        Weight</td>
                                    <td style="width: 18%; padding: 8px;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->firstWeighbridge->weight ?? 'N/A' }}" readonly>
                                    </td>


                                </tr>
                                <tr>
                                    <td style="width: 15%; padding: 8px;border: none;">
                                        Net Weight</td>
                                    <td style="width: 18%; padding: 8px;border: none;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->firstWeighbridge->weight -  $arrivalTicket->secondWeighbridge->weight}}" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 15%; padding: 8px;border: none;">
                                        Loading Weight</td>
                                    <td style="width: 18%; padding: 8px;border: none;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px;border: none;">Avg. Weight</td>
                                    <td style="padding: 8px;border: none;" colspan="5">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->net_weight / $arrivalTicket->bags ?? 'N/A' }}"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 15%; padding: 8px;border: none;">
                                        Arrival
                                        Weight</td>
                                    <td style="width: 18%; padding: 8px;border: none;">
                                        <input type="text"
                                            style="width: 100%; border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->secondWeighbridge->weight ?? 'N/A' }}" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Section -->
                    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
                        <tr>
                            <td style="width: 33%; text-align: center; padding: 8px; vertical-align: top;">
                                <div style="font-weight: bold; margin-bottom: 5px;">Confirmed Form</div>
                                <div style="margin-top: 30px; border-top: 1px solid #000; padding-top: 5px;">
                                    {{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}
                                </div>
                            </td>
                            <td style="width: 33%; text-align: center; padding: 8px; vertical-align: top;">
                                <div style="font-weight: bold; margin-bottom: 5px;">Contract Number</div>
                                <div style="margin-top: 30px; border-top: 1px solid #000; padding-top: 5px;">
                                    {{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}
                                </div>
                            </td>
                            <td style="width: 33%; text-align: center; padding: 8px; vertical-align: top;">
                                <div style="font-weight: bold; margin-bottom: 5px;">Prepared By:</div>
                                <div style="margin-top: 30px; border-top: 1px solid #000; padding-top: 5px;">
                                    {{ auth()->user()->name }}
                                </div>
                            </td>
                        </tr>
                    </table>

                    @if ($isNotGeneratable)
                        <div
                            style="margin-top: 15px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                            <strong>Important!</strong> Please apply deductions first before generating the arrival
                            slip.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="button" class="btn btn-info mr-2" id="printButton">
                <i class="ft-printer mr-1"></i> Print
            </button>
        </div>
    </div>
</form>


<script>
    function printView(param1, param2, param3) {
        $(".qrCodeDiv").removeClass("hidden");

        if (param2 !== "") {
            $('.' + param2).prop('href', '');
        }

        $('.printHide').hide();

        var printContents = document.getElementById(param1).innerHTML;

        // Open new print window
        var printWindow = window.open('', '', 'height=600,width=800');

        // Define print styles
        var printStyles = `
      <style>
        @media print{.flex-head{display:flex !important;align-items:center !important;justify-content:left !important;}
        .add-main1 ul{display:flex !important;align-items:center !important;justify-content:space-evenly !important;padding:0 !important;list-style:none !important;}
        .logo img{width:67% !important;}
        .head-add1 h5{font-size:15px !important;font-weight:700 !important;margin-bottom:6px !important;}
        .head-add1 p{font-size:12px !important;margin-bottom:8px !important;}
        .add-main2{display:flex !important;align-items:center !important;justify-content:space-between !important;}
        a.btn.btn-a{border:2px solid #ddd;color:#000;}
        a.btn.btn-a:hover{box-shadow:0 2px 7px rgba(0,0,0,0.28) !important;cursor:pointer !important;background:#008749 !important;color:#fff !important;}
        .logo p{font-weight:bold !important;}
        #modal-sidebar.open{width:100% !important;}
        td{font-size:12px !important;}
        td input{font-size:12px !important;}
        }
      </style>
    `;

        // Write content + styles into print window
        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write(printStyles); // inject styles
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();

        // Delay print & close logic
        setTimeout(function() {
            printWindow.print();

            setTimeout(function() {
                printWindow.close();

                 if (param3 !== 1) {
                     location.reload();
                 }
            }, 500);
        }, 500);
    }

    // Bind the function to the button
    document.getElementById("printButton").addEventListener("click", function() {
        printView('printSection', 'print-section', 0);
    });
</script>
