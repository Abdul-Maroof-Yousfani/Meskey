@extends('management.layouts.master')

@section('title')
    Arrival Ticket Summary
@endsection

@section('content')
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

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            Link Arrival Ticket To Contract: #{{ $arrivalTicket->unique_no }}
                            @if ($arrivalTicket->first_qc_status == 'rejected')
                                <span class="badge badge-danger ml-2">Rejected</span>
                            @elseif($arrivalTicket->is_ticket_verified == 1)
                                <span class="badge badge-success ml-2">Contract Verified</span>
                            @endif
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('raw-material.ticket-contracts.store') }}" method="POST" id="ajaxSubmit">
                            @csrf
                            <input type="hidden" id="url"
                                value="{{ route('raw-material.ticket-contracts.index') }}" />
                            <input type="hidden" name="arrival_ticket_id" value="{{ $arrivalTicket->id }}">
                            <input type="hidden" name="selected_freight" id="selected_freight_input">

                            <div class="row">
                                <div class="col-12">
                                    <div class="card shadow">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="contract_search">Search Contract</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="contract_search"
                                                        placeholder="Search by contract no, product etc.">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-info" type="button"
                                                            id="search_contract_btn">
                                                            <i class="fa fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="contract-results mt-3" id="contracts_table"
                                                style="display: none; max-height: 600px; overflow-y: auto;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 text-right">
                                    {{-- @if (($arrivalTicket->arrival_purchase_order_id ?? false) && ($arrivalTicket->purchaseOrder->status === 'completed' ?? false))
                                        <a href="{{ route('raw-material.ticket-contracts.index') }}" class="btn btn-danger">
                                            Close
                                        </a>
                                    @else --}}
                                    <button type="button" class="btn btn-primary" id="confirm_submit_btn"
                                        {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}>
                                        <i class="fa fa-check"></i> Submit
                                    </button>
                                    <button type="submit" class="btn btn-primary d-none" id="real_submit_btn">
                                        <i class="fa fa-check"></i> Submit
                                    </button>
                                    {{-- @endif --}}
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card shadow">
                                        <div class="card-header">
                                            <h3 class="card-title">Ticket Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Ticket Number</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->unique_no }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Date</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ now()->format('d-M-Y') }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->document_approval_status == 'fully_approved' ? 'Approved' : ucfirst(str_replace('_', ' ', $arrivalTicket->document_approval_status)) }}"
                                                            readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Product</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->product->name }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>QC Product</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->qcProduct->name }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group"
                                                        style="background-color: #ffff99; padding: 10px; border-radius: 5px;">
                                                        <label style="font-weight: bold;">Sauda Type</label>
                                                        <input type="text" class="form-control"
                                                            style="font-weight: bold;"
                                                            value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}"
                                                            readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-md-12">
                                                    <h5 class="section-title">Party Information</h5>
                                                    <hr>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Miller</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->miller->name ?? 'N/A' }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Broker</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->broker_name ?? 'N/A' }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>On Account Of</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->accounts_of_name ?? 'N/A' }}"
                                                            readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-md-12">
                                                    <h5 class="section-title">Transport Information</h5>
                                                    <hr>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Truck No</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->truck_no ?? 'N/A' }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Truck Type</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->truckType->name ?? 'N/A' }}"
                                                            readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Bilty No</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->bilty_no }}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Station</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->station->name ?? 'N/A' }}" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($arrivalTicket->first_qc_status != 'rejected')
                                                <div class="row mt-3">
                                                    <div class="col-md-12">
                                                        <h5 class="section-title">Weight Information</h5>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>First Weight</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->firstWeighbridge->weight ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Second Weight</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->secondWeighbridge->weight ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Net Weight</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->net_weight ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Arrival Weight</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->secondWeighbridge->weight }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>No. of Bags</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->approvals->total_bags }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Avg. Weight per Bag</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->net_weight / $arrivalTicket->bags ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Packing</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Gala No</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-3">
                                                    <div class="col-md-12">
                                                        <h5 class="section-title">Freight Information</h5>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Filling Bags</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }} × 10 = {{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Freight (Rs.)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->freight_written_on_bilty ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Freight per Ton</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Arrived Kanta Charges</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->karachi_kanta_charges ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Kanta Loading Charges</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->kanta_golarchi_charges ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Other/Labour Charges</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Other/Labour Charges (in words)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ numberToWords($arrivalTicket->freight->other_labour_charges ?? 0) }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Other Deduction</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Other Deduction (in words)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ numberToWords($arrivalTicket->freight->other_deduction ?? 0) }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Total Freight Payable</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Total Freight Payable (in words)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ numberToWords($arrivalTicket->freight->gross_freight_amount ?? 0) }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Unpaid Labour Charge</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Unpaid Labour Charge (in words)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Final Figure</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Final Figure (in words)</label>
                                                            <input type="text" class="form-control"
                                                                value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($isCompulsury || $isSlabs || $showLumpSum)
                                                <div class="row mt-3">
                                                    <div class="col-md-12">
                                                        <h5 class="section-title">Sampling Results</h5>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Parameter</th>
                                                                        <th>Applied Deduction</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @if ($showLumpSum && !$isSlabs && !$isCompulsury)
                                                                        <tr>
                                                                            <td>Lumpsum Deduction Rupees</td>
                                                                            <td class="text-center">
                                                                                {{ $samplingRequest->lumpsum_deduction ?? 0 }}
                                                                                (Applied as Lumpsum)
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Lumpsum Deduction KG's</td>
                                                                            <td class="text-center">
                                                                                {{ $samplingRequest->lumpsum_deduction_kgs ?? 0 }}
                                                                                (Applied as Lumpsum)
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
                                                                                    <td>{{ $slab->slabType->name }}</td>
                                                                                    <td class="text-center">
                                                                                        {{ $slab->applied_deduction }}
                                                                                        <span
                                                                                            class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @else
                                                                            <tr>
                                                                                <td colspan="2"
                                                                                    class="text-center text-muted">
                                                                                    No Initial Slabs Found
                                                                                </td>
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
                                                                                        <td>{{ $slab->qcParam->name }}</td>
                                                                                        <td class="text-center">
                                                                                            {{ $slab->applied_deduction }}
                                                                                            <span
                                                                                                class="text-sm">Rs.</span>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            @else
                                                                                <tr>
                                                                                    <td colspan="2"
                                                                                        class="text-center text-muted">
                                                                                        No Compulsory Slabs Found
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endif
                                                                    @endif
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let currentSelectedContract = null;
            let currentSelectedFreight = null;

            loadInitialContracts();

            const remainingTruckRow = 14;
            const remainingQtyRow = 15;

            $('#contract_search, #search_contract_btn').on('input click', function() {
                const searchTerm = $('#contract_search').val().trim();
                if (searchTerm.length < 2) {
                    loadInitialContracts();
                    return;
                }
                searchContracts(searchTerm);
            });

            $(document).on('change', 'input[name="selected_contract"]', function() {
                const contractId = $(this).val();
                currentSelectedContract = contractId;
                $('#selected_contract_id').val(contractId);
                $('#confirm_submit_btn').prop('disabled',
                    {{ $arrivalTicket->is_ticket_verified == 1 ? 'true' : 'false' }});

                $('.contract-row').removeClass('table-active');
                $(this).closest('.contract-row').addClass('table-active');

                $('.freight-row').hide();
                $(`.freight-row[data-contract-id="${contractId}"]`).show();

                currentSelectedFreight = null;
                $('#selected_freight_input').val('');

                const selectedFreightRadio = $(`input[name="selected_freight_${contractId}"]:checked`);
                if (selectedFreightRadio.length > 0) {
                    currentSelectedFreight = selectedFreightRadio.val();
                    $('#selected_freight_input').val(currentSelectedFreight);
                }
            });

            $(document).on('change', 'input[name^="selected_freight_"]', function() {
                const freightId = $(this).val();
                const contractId = $(this).data('contract-id');

                if (currentSelectedContract == contractId) {
                    currentSelectedFreight = freightId;
                    $('#selected_freight_input').val(freightId);

                    $(`.freight-item`).removeClass('table-success');
                    $(this).closest('.freight-item').addClass('table-success');
                }
            });

            // $(document).on('click', '.toggle-freights', function() {
            //     const contractId = $(this).data('contract-id');
            //     const freightDetails = $(this).closest('.freight-container').find('.freight-details');
            //     const icon = $(this).find('i');

            //     if (freightDetails.is(':visible')) {
            //         freightDetails.slideUp();
            //         icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            //         $(this).removeClass('expanded');
            //         $(this).html('<i class="fa fa-chevron-down"></i> Show Freights');
            //     } else {
            //         // freightDetails.html('<div class="freight-shimmer p-3">Loading freights...</div>');
            //         freightDetails.slideDown();

            //         // setTimeout(() => {
            //         loadFreightDetails(contractId);
            //         icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            //         $(this).addClass('expanded');
            //         $(this).html('<i class="fa fa-chevron-up"></i> Hide Freights');
            //         // }, 500);
            //     }
            // });

            $(document).on('click', '#confirm_submit_btn', function() {
                if (!currentSelectedContract) {
                    Swal.fire('Error', 'Please select a contract first', 'error');
                    return;
                }

                const contractRow = $(`.contract-row[data-id="${currentSelectedContract}"]`);
                const remainingQty = parseFloat(contractRow.find(`td:eq(${remainingQtyRow})`).text().split(
                        ' - ')[1] ||
                    contractRow.find(`td:eq(${remainingQtyRow})`).text().split(' - ')[0]) || 0;
                const ticketWeight = parseFloat('{{ $arrivalTicket->net_weight ?? 0 }}');
                const remainingTrucks = parseInt(contractRow.find(`td:eq(${remainingTruckRow}) span`)
                    .text()) || 0;
                const isTicketVerified = @json($arrivalTicket->is_ticket_verified == 1);
                const isTicketCompleted = @json($arrivalTicket->purchaseOrder?->status ?? '' === 'completed');

                let requiresFreightConfirmation = false;
                let freightDetails = {};

                if (currentSelectedFreight) {
                    // const selectedFreightRadio = $(`input[value="${currentSelectedFreight}"]`);
                    const secondInput = $(`input[value="${currentSelectedFreight}"].freight`);

                    const freightTruckNo = secondInput.data('truck-no');
                    const freightBiltyNo = secondInput.data('bilty-no');
                    const ticketTruckNo = '{{ $arrivalTicket->truck_no }}';
                    const ticketBiltyNo = '{{ $arrivalTicket->bilty_no }}';

                    console.log({
                        freightTruckNo,
                        freightBiltyNo,
                        secondInput
                    });

                    if (freightTruckNo?.toString()?.toLowerCase() !== ticketTruckNo.toLowerCase() ||
                        freightBiltyNo?.toString()?.toLowerCase() !== ticketBiltyNo.toLowerCase()) {
                        requiresFreightConfirmation = true;
                        freightDetails = {
                            freight_truck: freightTruckNo,
                            freight_bilty: freightBiltyNo,
                            ticket_truck: ticketTruckNo,
                            ticket_bilty: ticketBiltyNo
                        };
                    }
                }

                let confirmationHtml = `
                    <div class="form-group text-left">
                        <label>Closing Trucks Quantity</label>
                        <select id="swal-closing-trucks" class="form-control select2" required>
                            <option value="0.5">0.5</option>
                            <option value="1" selected>1</option>
                            <option value="1.5">1.5</option>
                            <option value="2">2</option>
                        </select>
                        <small class="text-muted">Max allowed: ${remainingTrucks}</small>
                    </div>
                    <div class="form-check text-left mt-3">
                        <input type="checkbox" class="form-check-input" id="swal-verify-ticket" ${isTicketVerified ? 'checked' : ''}>
                        <label class="form-check-label" for="swal-verify-ticket">Verify Ticket Contract</label>
                    </div>
                    <div class="form-check text-left mt-3">
                        <input type="checkbox" class="form-check-input" id="swal-mark-completed" ${isTicketCompleted ? 'checked' : ''}>
                        <label class="form-check-label" for="swal-mark-completed">Mark contract as completed</label>
                    </div>
                `;

                //  if (requiresFreightConfirmation) {
                //     confirmationHtml += `
            //         <div class="alert alert-warning mt-3">
            //             <h6><i class="fa fa-exclamation-triangle"></i> Freight Mismatch Warning</h6>
            //             <p><strong>Ticket Details:</strong> Truck: ${freightDetails.ticket_truck}, Bilty: ${freightDetails.ticket_bilty}</p>
            //             <p><strong>Selected Freight:</strong> Truck: ${freightDetails.freight_truck}, Bilty: ${freightDetails.freight_bilty}</p>
            //             <div class="form-check">
            //                 <input type="checkbox" class="form-check-input" id="swal-confirm-different-freight" required>
            //                 <label class="form-check-label" for="swal-confirm-different-freight">
            //                     I confirm linking this freight despite the mismatch
            //                 </label>
            //             </div>
            //         </div>
            //     `;
                // }

                if (requiresFreightConfirmation) {
                    confirmationHtml += `
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fa fa-exclamation-triangle"></i> Freight Mismatch Warning</h6>
                            <p><strong>Ticket Details:</strong> Truck: ${freightDetails.ticket_truck}, Bilty: ${freightDetails.ticket_bilty}</p>
                            <p><strong>Selected Freight:</strong> Truck: ${freightDetails.freight_truck}, Bilty: ${freightDetails.freight_bilty}</p>
                        </div>
                    `;
                }

                Swal.fire({
                    title: 'Confirm Submission',
                    html: confirmationHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Cancel',
                    focusConfirm: false,
                    preConfirm: () => {
                        const trucksQty = parseFloat($('#swal-closing-trucks').val());
                        const verifyTicket = $('#swal-verify-ticket').is(':checked');

                        if (!trucksQty || trucksQty <= 0) {
                            Swal.showValidationMessage(
                                'Closing trucks quantity is required and must be greater than 0'
                            );
                            return false;
                        }

                        if (trucksQty > remainingTrucks) {
                            Swal.showValidationMessage(
                                `Closing trucks quantity cannot exceed remaining trucks (${remainingTrucks})`
                            );
                            return false;
                        }

                        // if (requiresFreightConfirmation && !$('#swal-confirm-different-freight')
                        //     .is(':checked')) {
                        //     Swal.showValidationMessage(
                        //         'Please confirm the freight mismatch to proceed');
                        //     return false;
                        // }

                        const markCompleted = remainingQty - ticketWeight <= 0 || $(
                            '#swal-mark-completed').is(':checked');

                        return {
                            trucksQty,
                            markCompleted,
                            verifyTicket,
                            // confirmDifferentFreight: requiresFreightConfirmation
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const {
                            trucksQty,
                            markCompleted,
                            verifyTicket,
                            confirmDifferentFreight
                        } = result.value;

                        $('<input>').attr({
                            type: 'hidden',
                            name: 'closing_trucks_qty',
                            value: trucksQty
                        }).appendTo('#ajaxSubmit');

                        if (markCompleted) {
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'mark_completed',
                                value: '1'
                            }).appendTo('#ajaxSubmit');
                        }

                        if (verifyTicket) {
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'verify_ticket',
                                value: '1'
                            }).appendTo('#ajaxSubmit');
                        }

                        // if (confirmDifferentFreight) {
                        //     $('<input>').attr({
                        //         type: 'hidden',
                        //         name: 'confirm_different_freight',
                        //         value: '1'
                        //     }).appendTo('#ajaxSubmit');
                        // }

                        $('#real_submit_btn').click();
                    }
                });
            });

            $(document).on('click', '.mark-completed', function(e) {
                e.preventDefault();
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to mark this contract as completed?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, mark as completed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('raw-material.purchase-order.mark-completed') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                id: contractId
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Completed!',
                                        'Contract has been marked as completed.',
                                        'success');
                                    loadInitialContracts();
                                } else {
                                    Swal.fire('Error!', response.message ||
                                        'Something went wrong.', 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });

            @if ($arrivalTicket->arrival_purchase_order_id ?? false)
                setTimeout(() => {
                    $('input[name="selected_contract"][value="{{ $arrivalTicket->arrival_purchase_order_id }}"]')
                        .prop('checked', true)
                        .trigger('change');
                }, 1000);
            @endif

            function loadInitialContracts() {
                showLoadingShimmer();
                $.ajax({
                    url: '{{ route('raw-material.ticket-contracts.search-contracts') }}',
                    method: 'GET',
                    data: {
                        initial: true,
                        ticket_id: '{{ $arrivalTicket->id ?? '' }}'
                    },
                    success: function(response) {
                        populateContractResults(response);
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        hideLoadingShimmer();
                    }
                });
            }

            function searchContracts(searchTerm) {
                showLoadingShimmer();
                $.ajax({
                    url: '{{ route('raw-material.ticket-contracts.search-contracts') }}',
                    method: 'GET',
                    data: {
                        search: searchTerm,
                        ticket_id: '{{ $arrivalTicket->id ?? '' }}'
                    },
                    success: function(response) {
                        populateContractResults(response);
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        hideLoadingShimmer();
                    }
                });
            }

            function populateContractResults(response) {
                $('#contracts_table').empty();

                if (response.success && response.html) {
                    $('.contract-results').show();
                    $('#contracts_table').html(response.html);

                    $('input[name="selected_contract"]:checked').trigger('change');
                } else {
                    $('#contracts_table').html(`
                        <div class="text-center text-muted p-4">
                            <i class="fa fa-search fa-3x mb-3"></i>
                            <p>No contracts found</p>
                        </div>
                    `);
                    $('.contract-results').show();
                }

                hideLoadingShimmer();
            }

            function loadFreightDetails(contractId) {
                const freightContainer = $(`.freight-row[data-contract-id="${contractId}"] .freight-details`);
                freightContainer.html(freightContainer.html());
            }

            function showLoadingShimmer() {
                $('#contracts_table').html(`
                    <div class="freight-shimmer p-4">
                        <div class="row">
                            <div class="col-12">
                                <div class="bg-light rounded p-3 mb-2"></div>
                                <div class="bg-light rounded p-3 mb-2"></div>
                                <div class="bg-light rounded p-3 mb-2"></div>
                            </div>
                        </div>
                    </div>
                `);
                $('.contract-results').show();
            }

            function hideLoadingShimmer() {}
        });
    </script>

    <style>
        .contract-row {
            cursor: pointer;
        }

        .contract-row:hover {
            background-color: #f8f9fa;
        }

        .contract-results {
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .contract-results table {
            margin-bottom: 0;
        }

        .table-active {
            background-color: #e7f5ff !important;
        }

        .freight-container {
            margin: 5px 0;
            border-radius: 5px;
        }

        .freight-item.table-success {
            background-color: #d4edda !important;
        }

        .contract-row.table-info {
            background-color: #d1ecf1 !important;
        }

        .freight-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .section-title {
            color: #495057;
            font-weight: 600;
        }
    </style>
@endsection
