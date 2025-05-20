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

                            <div class="row mb-4">
                                {{-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="supplier_id">Supplier</label>
                                        <select name="supplier_id" id="supplier_id" class="form-control">
                                            <option value="">Select Supplier</option>
                                        </select>
                                    </div>
                                </div> --}}
                                <div class="col-12">
                                    <div class="d-flex w-100 align-items-center">
                                        <div class="form-group w-100 mr-2">
                                            <label for="contract_id">Contract</label>
                                            <select name="contract_id" id="contract_id" class="form-control select2"
                                                @disabled($arrivalTicket->arrival_purchase_order_id ?? false)>
                                                <option value="">Select Contract</option>
                                                @foreach ($purchaseOrders as $contract)
                                                    <option value="{{ $contract->id }}"
                                                        @if (old('contract_id', $arrivalTicket->arrival_purchase_order_id ?? null) == $contract->id) selected @endif>
                                                        {{ $contract->contract_no }} ({{ $contract->qcProduct->name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if ($arrivalTicket->arrival_purchase_order_id ?? false)
                                            <a href="{{ route('raw-material.ticket-contracts.index') }}"
                                                class="btn btn-danger mt-1">
                                                Close
                                            </a>
                                        @else
                                            <button type="submit" class="mt-1 btn btn-primary">
                                                Submit
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="section-title">Ticket Information</h5>
                                    <hr>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Ticket Number</label>
                                        <input type="text" class="form-control" value="{{ $arrivalTicket->unique_no }}"
                                            readonly>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="text" class="form-control" value="{{ now()->format('d-M-Y') }}"
                                            readonly>
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
                                            value="{{ $arrivalTicket->accounts_of_name ?? 'N/A' }}" readonly>
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
                                            value="{{ $arrivalTicket->truckType->name ?? 'N/A' }}" readonly>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Bilty No</label>
                                        <input type="text" class="form-control" value="{{ $arrivalTicket->bilty_no }}"
                                            readonly>
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
                                            value="{{ $arrivalTicket->firstWeighbridge->weight ?? 'N/A' }}" readonly>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Second Weight</label>
                                        <input type="text" class="form-control"
                                            value="{{ $arrivalTicket->secondWeighbridge->weight ?? 'N/A' }}" readonly>
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
                                            value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
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
                                            value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
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
                                            value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
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
                                            value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
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
                                                                {{ $samplingRequest->lumpsum_deduction ?? 0 }} (Applied as
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
                                                                <td colspan="2" class="text-center text-muted">
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
                                                                    <td colspan="2" class="text-center text-muted">
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

                            {{-- <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    @if ($arrivalTicket->arrival_purchase_order_id ?? false)
                                        <a href="{{ route('raw-material.ticket-contracts.index') }}"
                                            class="btn btn-danger">
                                            Close
                                        </a>
                                    @else
                                        <button type="submit" class="btn btn-primary">
                                            Submit
                                        </button>
                                    @endif
                                </div>
                            </div> --}}
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
            $('.select2').select2();

            initializeDynamicSelect2('#supplier_id', 'brokers', 'name', 'name', true, false);
        });
    </script>

    <style>
        .section-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .signature-box {
            margin-top: 50px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 10px auto;
            height: 1px;
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }
    </style>
@endsection
