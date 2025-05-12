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
</style>
<link rel="stylesheet" href="{{ asset('css/arrival-slip-styles.css') }}">

<form action="{{ route('arrival-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-slip') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id" id="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                        <option @selected($arrivalTicket->id == $arrival_slip->arrival_ticket_id) value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="slabsContainer">
                <!-- Header with company info -->
                <div style="display: none; margin-bottom: 15px;">
                    <div style="width: 120px; padding-right: 15px;">
                        <img src="{{ asset('images/logo.png') }}" alt="Company Logo" style="max-width: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 12px; line-height: 1.4;">
                            <p><strong>Head office:</strong> Saima Trade Tower, Tower B, Room # 1511,1512 & 1513, I. I.
                                Chundrigar Road, Karachi.</p>
                            <p><strong>Factory:</strong> Plot No A-43, A-45 & A-46, Eastern, Industrial Zone, Port
                                Qasim, Karachi, Pakistan.</p>
                            <p><strong>Retail Outlet:</strong> Shop No. 4, K.A.I Center, Opp City Court, Dandia Bazar,
                                Karachi Ph. + 92 21 32733369, 3370</p>
                            <p><strong>Tel: = 03012740216, 0 Fax:</strong></p>
                            <p><strong>email: info@m6.com.pk, web: www.m6.com.pk</strong></p>
                        </div>
                    </div>
                    <div style="text-align: right; padding-left: 15px; white-space: nowrap;">
                        <strong>Arrival Slip</strong>
                    </div>
                </div>

                <!-- Main form table -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                    <tr>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Arrival
                            Slip #</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->unique_no }}" readonly>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Date
                        </td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ now()->format('d-M-Y') }}" readonly>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Truck
                            No.</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->truck_no ?? 'N/A' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Bill/T No.</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->bilty_no }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">No. of Bags</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->bags }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Packing</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                                readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Party Name</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->supplier_name }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Broker Name</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->broker_name ?? 'N/A' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">On A/C of</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->accounts_of_id ?? 'N/A' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Station</td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="1">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->station->name ?? 'N/A' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Commodity</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->product->name }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Deductions</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->lumpsum_deduction ?? 'N/A' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Sauda Term</td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="3">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Gala No.</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Godown</td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="3">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->unloadingLocation->arrivalLocation->name ?? 'N/A' }}"
                                readonly>
                        </td>
                    </tr>
                </table>

                <!-- Weights Section -->
                <div style="margin-top: 15px; font-weight: bold; border-bottom: 1px solid #000; padding: 5px 0;">
                    Weights</div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                    <tr>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Gross
                            Weight</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->secondWeighbridge->weight ?? 'N/A' }}" readonly>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                            Arrival
                            Weight</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->secondWeighbridge->weight ?? 'N/A' }}" readonly>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                            Loading Weight</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Avg. Weight</td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="5">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->net_weight / $arrivalTicket->bags ?? 'N/A' }}" readonly>
                        </td>
                    </tr>
                </table>

                <!-- Freight Section -->
                <div style="margin-top: 15px; font-weight: bold; border-bottom: 1px solid #000; padding: 5px 0;">
                    Freight</div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                    <tr>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                            Filling</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <div style="display: flex; align-items: center;">
                                <input type="text"
                                    style="width: 40px; border: none; background: transparent; text-align: center;"
                                    value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }}" readonly>
                                <span style="padding: 0 5px;">× 10 =</span>
                                <input type="text"
                                    style="width: 50px; border: none; background: transparent; text-align: center;"
                                    value="{{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"
                                    readonly>
                            </div>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                            Freight (Rs.)</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}" readonly>
                        </td>
                        <td style="width: 15%; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                            Freight per Ton</td>
                        <td style="width: 18%; padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Karachi Kanta
                            Charges </td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->karachi_kanta_charges ?? '0.00' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Kanta - Golarchi
                            Charges
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="3">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->kanta_golarchi_charges ?? '0.00' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Other (+)/ Labour
                            Charges</td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="2">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}" readonly>
                        </td>

                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Other Deduction
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="3">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Total Freight
                            Payable (Rs.)</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="4">
                            <input type="text"
                                style="width: 100%; border: none; background: transparent; font-style: italic; font-size: 11px;"
                                value="{{ numberToWords($arrivalTicket->freight->gross_freight_amount ?? 0) }}"
                                readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Unpaid Labour
                            Charge</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="4">
                            <input type="text"
                                style="width: 100%; border: none; background: transparent; font-style: italic; font-size: 11px;"
                                value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}"
                                readonly>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">Final Figure</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <input type="text" style="width: 100%; border: none; background: transparent;"
                                value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;" colspan="4">
                            <input type="text"
                                style="width: 100%; border: none; background: transparent; font-style: italic; font-size: 11px;"
                                value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}" readonly>
                        </td>
                    </tr>
                </table>

                @if ($isCompulsury || $isSlabs)
                    <!-- Sampling Results Section -->
                    <div style="margin-top: 15px; font-weight: bold; border-bottom: 1px solid #000; padding: 5px 0;">
                        Sampling Results</div>
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-top: 10px;">
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
                                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $slab->slabType->name }}
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
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
                                            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
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
                        <strong>Important!</strong> Please apply deductions first before generating the arrival slip.
                    </div>
                @endif
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
    $('#printButton').click(function() {
        printJS({
            printable: 'slabsContainer',
            type: 'html',
            css: [
                'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
            ],
            style: `
                @page { size: auto; margin: 5mm; }
                body { padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            `,
            scanStyles: true,
            targetStyles: ['*']
        });
    });
</script>
