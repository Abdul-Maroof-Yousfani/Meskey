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
                        <h4 class="card-title">Link Arrival Ticket To Contract: #{{ $arrivalTicket->unique_no }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('raw-material.ticket-contracts.store') }}" method="POST" id="ajaxSubmit">
                            @csrf
                            <input type="hidden" name="arrival_ticket_id" value="{{ $arrivalTicket->id }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card shadow">
                                        <div class="card-header">
                                            <h3 class="card-title">Ticket Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Ticket Number</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->unique_no }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Date</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ now()->format('d-M-Y') }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->document_approval_status == 'fully_approved' ? 'Approved' : ucfirst(str_replace('_', ' ', $arrivalTicket->document_approval_status)) }}"
                                                            readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Product</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->product->name }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>QC Product</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->qcProduct->name }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Sauda Type</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}" readonly>
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
                                                            value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
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
                                                            value="{{ $arrivalTicket->approvals->total_bags }}" readonly>
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
                                                        <label>Karachi Kanta Charges</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $arrivalTicket->freight->karachi_kanta_charges ?? '0.00' }}"
                                                            readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Kanta Golarchi Charges</label>
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
                                                                            <td>Lumpsum Deduction</td>
                                                                            <td class="text-center">
                                                                                {{ $samplingRequest->lumpsum_deduction ?? 0 }}
                                                                                (Applied as
                                                                                Lumpsum)
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
                                                                                                class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
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
                                <div class="col-md-6">
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

                                            <div class="contract-results mt-3"
                                                style="display: none; max-height: 400px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered table-hover">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th width="10%">Select</th>
                                                            <th width="25%">Contract No</th>
                                                            <th width="20%">Product</th>
                                                            <th width="20%">Supplier</th>
                                                            <th width="15%">Quantity</th>
                                                            <th width="10%">Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="contract_results_body"></tbody>
                                                </table>
                                            </div>

                                            <div class="mt-3 text-right">
                                                @if ($arrivalTicket->arrival_purchase_order_id ?? false)
                                                    <a href="{{ route('raw-material.ticket-contracts.index') }}"
                                                        class="btn btn-danger">
                                                        Close
                                                    </a>
                                                @else
                                                    <button type="submit" class="btn btn-primary" id="submit_btn"
                                                        disabled>
                                                        <i class="fa fa-check"></i> Submit
                                                    </button>
                                                @endif
                                            </div>
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
            loadInitialContracts();

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
                $('#selected_contract_id').val(contractId);
                $('#submit_btn').prop('disabled', false);

                $('.contract-row').removeClass('table-active');
                $(this).closest('.contract-row').addClass('table-active');
            });

            @if ($arrivalTicket->arrival_purchase_order_id ?? false)
                $('input[name="selected_contract"][value="{{ $arrivalTicket->arrival_purchase_order_id }}"]')
                    .prop('checked', true)
                    .closest('.contract-row').addClass('table-active');
            @endif

            function loadInitialContracts() {
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
                    }
                });
            }

            function searchContracts(searchTerm) {
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
                    }
                });
            }

            function populateContractResults(response) {
                const resultsBody = $('#contract_results_body');
                resultsBody.empty();

                if (response.success && response.data.length > 0) {
                    response.data.forEach(contract => {
                        const row = `
                            <tr class="contract-row" data-id="${contract.id}">
                                <td class="text-center">
                                    <input type="radio" name="selected_contract" 
                                           value="${contract.id}" 
                                           ${contract.id == '{{ $arrivalTicket->arrival_purchase_order_id ?? '' }}' ? 'checked' : ''}>
                                </td>
                                <td>${contract.contract_no}</td>
                                <td>${contract.qc_product_name}</td>
                                <td>${contract.supplier.name}</td>
                                <td>${contract.total_quantity} kg</td>
                                <td>${contract.contract_date_formatted}</td>
                            </tr>
                        `;
                        resultsBody.append(row);
                    });

                    $('.contract-results').show();
                } else {
                    resultsBody.html(`
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No contracts found
                            </td>
                        </tr>
                    `);
                    $('.contract-results').show();
                }
            }
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
    </style>
@endsection
