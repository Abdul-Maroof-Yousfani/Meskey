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
                    <div class="col-md-6">
                        <div class="info-label">Inspection Number:</div>
                        <div class="info-value font-weight-bold text-primary">#{{ $pre_sale_inspection->inspection_no }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Inspection Date:</div>
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

                {{-- Items & Weights --}}
                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-12">
                        <div class="info-label mb-2"><i class="ft-package"></i> Items & Weights:</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 8%;">#</th>
                                        <th>Item (Product)</th>
                                        <th class="text-right" style="width: 25%;">Weight (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalWeight = 0; @endphp
                                    @forelse($pre_sale_inspection->items as $idx => $pi)
                                        @php $totalWeight += (float)$pi->weight; @endphp
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="font-weight-bold text-dark">{{ $pi->item?->name ?? 'N/A' }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($pi->weight, 2) }}</td>
                                        </tr>
                                    @empty
                                        @if($pre_sale_inspection->item)
                                            <tr>
                                                <td>1</td>
                                                <td class="font-weight-bold text-dark">{{ $pre_sale_inspection->item->name }}</td>
                                                <td class="text-right font-weight-bold">0.00</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No items recorded.</td>
                                            </tr>
                                        @endif
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="2" class="text-right">Total Weight:</td>
                                        <td class="text-right text-primary font-medium-1">{{ number_format($totalWeight, 2) }} kg</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Locations --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-4">
                        <div class="info-label">Locations:</div>
                        <div class="info-value">
                            @forelse($pre_sale_inspection->locationModels as $loc)
                                <span class="badge badge-light border mb-1">{{ $loc->companyLocation?->name }}</span>
                            @empty
                                @if(is_array($pre_sale_inspection->locations))
                                    @foreach(\App\Models\Master\CompanyLocation::whereIn('id', $pre_sale_inspection->locations)->pluck('name') as $lname)
                                        <span class="badge badge-light border mb-1">{{ $lname }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @endforelse
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Factory:</div>
                        <div class="info-value">
                            @forelse($pre_sale_inspection->factoryModels as $f)
                                <span class="badge badge-light border mb-1">{{ $f->factory?->name }}</span>
                            @empty
                                @if(is_array($pre_sale_inspection->factories))
                                    @foreach(\App\Models\Master\ArrivalLocation::whereIn('id', $pre_sale_inspection->factories)->pluck('name') as $fname)
                                        <span class="badge badge-light border mb-1">{{ $fname }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @endforelse
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Section:</div>
                        <div class="info-value">
                            @forelse($pre_sale_inspection->sectionModels as $s)
                                <span class="badge badge-light border mb-1">{{ $s->section?->name }}</span>
                            @empty
                                @if(is_array($pre_sale_inspection->sections))
                                    @foreach(\App\Models\Master\ArrivalSubLocation::whereIn('id', $pre_sale_inspection->sections)->pluck('name') as $sname)
                                        <span class="badge badge-light border mb-1">{{ $sname }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @endforelse
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

                @if($pre_sale_inspection->salesInquiries && $pre_sale_inspection->salesInquiries->count() > 0)
                    <div class="row mt-3 border-top pt-2">
                        <div class="col-12">
                            <div class="info-label">Linked Sales Inquiries:</div>
                            @foreach($pre_sale_inspection->salesInquiries as $inq)
                                <span class="badge badge-info p-1 mr-1">
                                    <i class="ft-file-text"></i> {{ $inq->inquiry_no }} ({{ $inq->date }})
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>
