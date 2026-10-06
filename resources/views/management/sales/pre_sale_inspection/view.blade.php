<style>
    .info-label {
        font-weight: 600;
        color: #555;
        font-size: 13px;
    }
    .info-value {
        font-size: 14px;
        color: #222;
        margin-bottom: 12px;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card shadow-none border mb-0">
            <div class="card-body">
                {{-- General Info --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-4">
                        <div class="info-label">Location:</div>
                        <div class="info-value">
                            <span class="badge badge-light border text-dark font-medium-1">
                                <i class="ft-map-pin text-primary"></i> {{ $pre_sale_inspection->location?->name ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Dekh Number:</div>
                        <div class="info-value font-weight-bold text-primary">#{{ $pre_sale_inspection->inspection_no }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Dekh Date:</div>
                        <div class="info-value">{{ $pre_sale_inspection->date ? $pre_sale_inspection->date->format('d M Y') : 'N/A' }}</div>
                    </div>
                </div>

                {{-- Party Info --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-4">
                        <div class="info-label">Party Name:</div>
                        <div class="info-value font-weight-bold">{{ $pre_sale_inspection->party_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Party Contact No:</div>
                        <div class="info-value">{{ $pre_sale_inspection->party_contact_no ?: 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Reference:</div>
                        <div class="info-value font-weight-bold text-dark">{{ $pre_sale_inspection->reference ?: 'N/A' }}</div>
                    </div>
                </div>

                {{-- Items, Factory, Section & Weights --}}
                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-12">
                        <div class="info-label mb-2"><i class="ft-package"></i> Items Details:</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 30%;">Item (Product)</th>
                                        <th style="width: 25%;">Factory</th>
                                        <th style="width: 25%;">Section</th>
                                        <th class="text-right" style="width: 15%;">Weight sample in (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalWeight = 0; @endphp
                                    @forelse($pre_sale_inspection->items as $idx => $pi)
                                        @php $totalWeight += (float)$pi->weight; @endphp
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="font-weight-bold text-dark">{{ $pi->item?->name ?? 'N/A' }}</td>
                                            <td>{{ $pi->factory?->name ?? '-' }}</td>
                                            <td>{{ $pi->section?->name ?? '-' }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($pi->weight, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No items recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="4" class="text-right">Total Weight:</td>
                                        <td class="text-right text-primary font-medium-1">{{ number_format($totalWeight, 2) }} kg</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Remarks & Meta --}}
                <div class="row">
                    <div class="col-md-8">
                        <div class="info-label">Remarks:</div>
                        <div class="info-value text-muted font-italic">{{ $pre_sale_inspection->remarks ?: 'No remarks provided.' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Created By:</div>
                        <div class="info-value">{{ $pre_sale_inspection->creator?->name ?? 'System' }} ({{ $pre_sale_inspection->created_at ? $pre_sale_inspection->created_at->format('d M Y, h:i A') : '' }})</div>
                    </div>
                </div>

                {{-- Linked Inquiries & Sale Orders --}}
                @if(($pre_sale_inspection->salesInquiries && $pre_sale_inspection->salesInquiries->count() > 0) || ($pre_sale_inspection->salesOrders && $pre_sale_inspection->salesOrders->count() > 0))
                    <div class="row mt-3 border-top pt-2">
                        @if($pre_sale_inspection->salesInquiries && $pre_sale_inspection->salesInquiries->count() > 0)
                            <div class="col-md-6">
                                <div class="info-label">Linked Sales Inquiries:</div>
                                @foreach($pre_sale_inspection->salesInquiries as $inq)
                                    <span class="badge badge-info p-1 mr-1 mb-1">
                                        <i class="ft-file-text"></i> {{ $inq->inquiry_no }} ({{ $inq->date }})
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($pre_sale_inspection->salesOrders && $pre_sale_inspection->salesOrders->count() > 0)
                            <div class="col-md-6">
                                <div class="info-label">Linked Sales Orders:</div>
                                @foreach($pre_sale_inspection->salesOrders as $so)
                                    <span class="badge badge-success p-1 mr-1 mb-1">
                                        <i class="ft-shopping-cart"></i> {{ $so->reference_no }} ({{ $so->order_date }})
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                {{-- Approval Status Workflow Component --}}
                <div class="row mt-3 border-top pt-3">
                    <div class="col-12">
                        <x-approval-status :model="$pre_sale_inspection" :list-refresh="route('sales.get.pre-sale-inspection.list')" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>
