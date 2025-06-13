@php
    $isSlabs = false;
    $isCompulsury = false;
    $showLumpSum = false;

    if (
        isset($samplingRequest->is_lumpsum_deduction) &&
        $samplingRequest->is_lumpsum_deduction &&
        $samplingRequest->lumpsum_deduction > 0
    ) {
        $showLumpSum = true;
    }

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

<link rel="stylesheet" href="{{ asset('css/arrival-slip-styles.css') }}">
<form action="{{ route('arrival-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-slip') }}" />
    <div class="row form-mar">
        <div class="pri" id="printSection">
            <!-- header -->
            <div class=" col-lg-12 col-md-12 col-sm-12 col-xs-12 auth-img-bg p-3">
                <div class="flex-head">
                    <div class="logo">
                        <img src="{{ asset('management/app-assets/img/meskay-logo.png') }}"
                            alt=""class="img-fluid">
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
                        <hr style="border: 1px solid #ddd;margin-bottom: 10px; margin-top: 0;">
                        <div class="add-main2">
                            <div class="head-add1">
                                <p><strong>Tel:</strong> +923012740216 <strong>Fax:</strong> </p>
                                <p><strong>Email:</strong> info@mft.com.pk <strong>Web:</strong> www.mft.com.pk</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr style="border: 1px solid #ddd; margin-bottom: 0;    margin-top: 0;">
            </div>

            <div class=" col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="">
                    <!-- Header with company info -->
                    <!-- <div style="display: none; margin-bottom: 15px;">
                        <div style="width: 120px; padding-right: 15px;">
                            <img src="{{ asset('images/logo.png') }}" alt="Company Logo" style="max-">
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
                    </div> -->
                    <!-- Main form table -->
                    <table style="border-collapse: collapse; ">
                        <tr>
                            <td style=" padding: 8px;border: none;">Arrival
                                Slip #</td>
                            <td style=" padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->unique_no }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Date
                            </td>
                            <td style=" padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ now()->format('d-M-Y') }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Truck No.</td>
                            <td style=" padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->truck_no ?? 'N/A' }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Bill/T No.</td>
                            <td style="padding: 8px; border: none;" colspan="2">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;width: 100%;"value="{{ $arrivalTicket->bilty_no }}"
                                    readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style=" padding: 8px;border: none;">No. of Bags</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->approvals->total_bags }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Packing</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                                    readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Lot No.</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style=" padding: 8px;border: none;">Party Name</td>
                            <td style="padding: 8px; border: none;" colspan="3">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->miller->name ?? 'N/A' }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Broker Name</td>
                            <td style="padding: 8px; border: none;"colspan="4">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px;background: transparent;"
                                    value="{{ $arrivalTicket->broker_name ?? 'N/A' }}" readonly>
                            </td>

                        </tr>
                        {{-- <tr>
                            <td style=" padding: 8px;border: none;">Broker 2 Name</td>
                            <td style="padding: 8px;border: none;" colspan="3">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Broker 3 Name</td>
                            <td style="padding: 8px; border: none;"colspan="4">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="" readonly>
                            </td>

                        </tr> --}}
                        <tr>
                            <td style=" padding: 8px;border: none;">On A/C of</td>
                            <td style="padding: 8px;border: none;"colspan="3">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->accounts_of_name ?? 'N/A' }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Station</td>
                            <td style="padding: 8px; border: none;" colspan="3">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->station->name ?? 'N/A' }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td style=" padding: 8px;border: none;">Commodity</td>
                            <td style="padding: 8px; border: none;"colspan="3">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->qcProduct->name }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Status</td>
                            <td style="padding: 8px;border: none;" colspan="1">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ isset($arrivalTicket->saudaType->id)
                                        ? ($arrivalTicket->saudaType->id == 1
                                            ? ($arrivalTicket->document_approval_status == 'fully_approved'
                                                ? 'OK'
                                                : ($arrivalTicket->document_approval_status == 'half_approved'
                                                    ? 'P-RH'
                                                    : 'RF'))
                                            : ($arrivalTicket->saudaType->id == 2
                                                ? ($arrivalTicket->document_approval_status == 'fully_approved'
                                                    ? 'TS'
                                                    : ($arrivalTicket->document_approval_status == 'half_approved'
                                                        ? 'TS-RH'
                                                        : 'RF'))
                                                : 'RF'))
                                        : 'RF' }}"
                                    readonly>
                            </td>
                            <td style=" padding: 8px;border: none; ">U/L Slip #</td>
                            <td style="padding: 8px; border: none;" colspan="2">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;width: 100%;"value="{{ $arrivalTicket->unique_no ?? 'N/A' }}"
                                    readonly>
                            </td>
                        </tr>

                        <tr>
                            <td style=" padding: 8px;border: none;">Gala No.</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
                            </td>
                            <td style=" padding: 8px;border: none;">Godown</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->unloadingLocation->arrivalLocation->name ?? 'N/A' }}"
                                    readonly>
                            </td>

                            <td style=" padding: 8px;border: none;">Sauda Term</td>
                            <td style="padding: 8px; border: none;">
                                <input type="text"
                                    style=" border: 1px solid #ddd; padding: 10px 10px; background: transparent;"
                                    value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}" readonly>
                            </td>
                        </tr>
                    </table>

                    <div class="row">
                        <div class=" col-lg-8 col-md-8 col-sm-8 col-xs-8">
                            <!-- Freight Section -->
                            <div style="margin-top: 5px; font-weight: bold; padding: 5px 0;">Freight</div>
                            <hr style="border: 1px solid #ddd; margin-bottom: 0;margin-top: 0;">
                            <table style="border-collapse: collapse; ">
                                <tr>
                                    <td style="padding:8px;border:none;"> Filling</td>
                                    <td style=" padding: 8px;">
                                        <div style="display: flex; align-items: center; width: 100%;">
                                            <input type="text"
                                                style="width: 79px; border: 1px solid #ddd; padding: 10px 10px; text-align: center;"
                                                value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }}"
                                                readonly> <span style="padding: 0 5px;">× 10 =</span>
                                            <input type="text"
                                                style="width:75px;border:1px solid #ddd;padding:10px 10px;text-align:center;"
                                                value="{{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"readonly>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;border:none;"> Freight (Rs.)</td>
                                    <td style=" padding: 8px;">
                                        <input type="text"
                                            style="width:100%;border:1px solid #ddd;padding:10px 10px;"
                                            value="{{ $arrivalTicket->freight->freight_written_on_bilty ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding:8px;border:none;"> Freight per Ton</td>
                                    <td style=" padding: 8px;">
                                        <input type="text"
                                            style="width:100%;border:1px solid #ddd;padding:10px 10px;"
                                            value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
                                    </td>
                                </tr>

                                <tr>
                                    <td style=" padding:8px;border:none;">Arrived Kanta Charges </td>
                                    <td style="padding: 8px;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->karachi_kanta_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style=" padding: 8px;border: none;">Kanta Loading Charges
                                    </td>
                                    <td style="padding: 8px;" colspan="3">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->kanta_golarchi_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                </tr>


                                <tr>
                                    <td style=" padding: 8px;border: none;">Other (+)/ Labour Charges</td>
                                    <td style="padding: 8px;" colspan="1">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="8">
                                        <input type="text"
                                            style="    width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic;"
                                            value="{{ numberToWords($arrivalTicket->freight->other_labour_charges ?? 0) }}"
                                            readonly>
                                    </td>


                                </tr>
                                <tr>

                                    <td style=" padding: 8px;border: none;">Other Deduction
                                    </td>
                                    <td style="padding: 8px;" colspan="1">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
                                    </td>

                                    <td style="padding: 8px;" colspan="8">
                                        <input type="text"
                                            style="    width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic;"
                                            value="{{ numberToWords($arrivalTicket->freight->other_deduction ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                                @php
                                    $payableCharges =
                                        ((int) $arrivalTicket->freight->freight_written_on_bilty ?? 0) +
                                        ((int) $arrivalTicket->freight->kanta_golarchi_charges ?? 0) +
                                        ((int) $arrivalTicket->freight->karachi_kanta_charges ?? 0) +
                                        ((int) $arrivalTicket->freight->other_labour_charges ?? 0) -
                                        ((int) $arrivalTicket->freight->other_deduction ?? 0);
                                @endphp
                                <tr>
                                    <td style=" padding: 8px;border: none;">Total Freight Payable (Rs.)</td>
                                    <td style="padding: 8px;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $payableCharges }}" readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="8">
                                        <input type="text"
                                            style="     width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic;"
                                            value="{{ numberToWords($payableCharges ?? 0) }}" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=" padding: 8px;border: none;">Unpaid Labour Charge</td>
                                    <td style="padding: 8px;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}"
                                            readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="8">
                                        <input type="text"
                                            style="    width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic;"
                                            value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=" padding: 8px;border: none;">Final Figure</td>
                                    <td style="padding: 8px;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
                                    </td>
                                    <td style="padding: 8px;" colspan="8">
                                        <input type="text"
                                            style="     width: 100%; border: 1px solid #ddd; padding: 10px 10px; font-style: italic;"
                                            value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}"
                                            readonly>
                                    </td>
                                </tr>
                            </table>

                        </div>
                        <div class=" col-lg-4 col-md-4 col-sm-4 col-xs-4">
                            <!-- Weights Section -->
                            <div style="margin-top:5px;font-weight:bold;padding:5px 0;"> Weights</div>
                            <hr style="border: 1px solid #ddd; margin-bottom: 0;margin-top: 0;">
                            <table style="border-collapse: collapse;">
                                {{-- <tr>
                                    <td style=" padding: 8px;border: none;">Gross
                                        Weight</td>
                                    <td style=" padding: 8px;">
                                        <input type="text" style=" border:1px solid #ddd;padding:10px 10px;"
                                            value="{{ $arrivalTicket->firstWeighbridge->weight ?? 'N/A' }}" readonly>
                                    </td>
                                </tr> --}}
                                <tr>
                                    <td style=" padding: 8px;border: none;">
                                        Arrival Weight</td>
                                    <td style=" padding: 8px;border: none;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->secondWeighbridge->weight }}"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=" padding: 8px;border: none;">
                                        Loading Weight</td>
                                    <td style=" padding: 8px;border: none;">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px;border: none;">Avg. Weight</td>
                                    <td style="padding: 8px;border: none;" colspan="5">
                                        <input type="text" style=" border: 1px solid #ddd; padding: 10px 10px;"
                                            value="{{ number_format(($arrivalTicket->firstWeighbridge->weight - $arrivalTicket->secondWeighbridge->weight) / $arrivalTicket->approvals->total_bags, 2) ?? 'N/A' }}"
                                            readonly>
                                    </td>
                                </tr>
                            </table>

                            <div>
                                @if ($isCompulsury || $isSlabs || $showLumpSum)
                                    <!-- Sampling Results Section -->
                                    <div style="margin-top:15px;font-weight:bold;padding:5px 0;"> Sampling Results
                                    </div>
                                    <hr style="border: 1px solid #ddd; margin-bottom: 0;margin-top: 0;">
                                    <table
                                        style=" width: 100%; border-collapse:collapse;margin-top:10px;     border: 1px solid #add;">
                                        <thead>
                                            <tr>
                                                <th style="padding: 8px;border: 1px solid #ddd;">
                                                    Parameter</th>
                                                <th style="padding: 8px;border: 1px solid #ddd;">
                                                    Applied Deduction</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($showLumpSum && !$isSlabs && !$isCompulsury)
                                                <tr>
                                                    <td style="padding: 8px;border: 1px solid #ddd;">
                                                        Lumpsum Deduction Rupees
                                                    </td>
                                                    <td
                                                        style="padding: 8px;border: 1px solid #ddd; text-align: center;">
                                                        {{ $samplingRequest->lumpsum_deduction ?? 0 }} (Applied as
                                                        Lumpsum)
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px;border: 1px solid #ddd;">
                                                        Lumpsum Deduction KG's
                                                    </td>
                                                    <td
                                                        style="padding: 8px;border: 1px solid #ddd; text-align: center;">
                                                        {{ $samplingRequest->lumpsum_deduction_kgs ?? 0 }} (Applied as
                                                        Lumpsum)
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Lumpsum Deduction KG's</td>
                                                    <td class="text-center">
                                                        {{ $samplingRequest->lumpsum_deduction_kgs ?? '0.00' }}
                                                        <span class="text-sm">(Applied as Lumpsum)</span>
                                                    </td>
                                                </tr>
                                            @else
                                                @if (count($samplingRequestResults) != 0)
                                                    @foreach ($samplingRequestResults as $slab)
                                                        @php
                                                            if (!$slab->applied_deduction) {
                                                                continue;
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td style="padding: 8px;border: 1px solid #ddd;">
                                                                {{ $slab->slabType->name }}
                                                            </td>
                                                            <td
                                                                style="padding: 8px; border: 1px solid #ddd;  text-align: center;">
                                                                {{ $slab->applied_deduction }} <span
                                                                    class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="2"
                                                            style="padding: 8px; border: 1px solid #ddd;  text-align: center; color: #777;">
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
                                                                    style="padding: 8px;border: 1px solid #ddd;text-align: center;">
                                                                    {{ $slab->applied_deduction }} <span
                                                                        class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                                                </td>
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
                                            @endif
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>

                    <br>
                    <br>
                    <br>
                    <br>

                    <div class="signature">

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
                    </div>

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
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="button" class="btn btn-info mr-2" id="printButton">
                <i class="ft-printer mr-1"></i> Print
            </button>
        </div>
    </div>
</form>

<script>
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').css('background-image', 'url(' + e.target.result + ')');
                $('#imagePreview').hide();
                $('#imagePreview').fadeIn(650);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#imageUpload").change(function() {
        readURL(this);
    });
    $('.submenu').hide();
    $('li.menu-items').on('click', function() {

        $(this).find('.submenu').slideToggle('slow');
        $(this).find('.menu-items-link').toggleClass('active');

    })

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
        @media print{
            @page{margin:2mm !important;margin-top:1mm !important;}
            .flex-head{display:flex !important;align-items:center !important;justify-content:left !important;}
            .add-main1 ul{display:flex !important;align-items:center !important;justify-content:space-evenly !important;padding:0 !important;list-style:none !important;}
            .logo img{width:67% !important;}
            .head-add1 h5{font-size:15px !important;font-weight:700 !important;margin-bottom:6px !important;}
            .head-add1 p{font-size:12px !important;margin-bottom:8px !important;}
            .add-main2{display:flex !important;align-items:center !important;justify-content:space-between !important;}
            a.btn.btn-a{border:2px solid #ddd;color:#000;}
            a.btn.btn-a:hover{box-shadow:0 2px 7px rgba(0,0,0,0.28) !important;cursor:pointer !important;background:#008749 !important;color:#fff !important;}
            .logo p{font-weight:bold !important;}
            #modal-sidebar.open{width:100% !important;}
            table td input{padding:8px 8px !important;}
            table tbody tr td{white-space:nowrap !important;}
            .row{display:flex !important;flex-wrap:nowrap !important;}
            [class*="col-"]{float:left !important;display:block !important;}
            table td{padding:5px 5px !important;}
        }
      </style>
    `;

        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write(printStyles);
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();

        setTimeout(function() {
            printWindow.print();
            setTimeout(function() {
                printWindow.close();
                if (param3 !== 1) {
                    // location.reload();
                }
            }, 500);
        }, 500);
    }

    // Bind the function to the button
    document.getElementById("printButton").addEventListener("click", function() {
        printView('printSection', 'print-section', 0);
    });

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printView('printSection', 'print-section', 0);
        }
    })
</script>
