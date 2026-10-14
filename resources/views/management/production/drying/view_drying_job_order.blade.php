<!-- Action Bar (Hidden on Print) -->
{{-- <div class="d-flex justify-content-end mb-2 d-print-none bg-light p-1 rounded">
    <div>
        <button onclick="printDiv('print-area')" class="btn btn-primary btn-sm">
            <i class="ft-printer mr-1"></i> Print
        </button>
        <button type="button" class="btn btn-danger btn-sm ml-1" data-close="model">
            <i class="ft-x"></i>
        </button>
    </div>
</div>
<br> --}}
<div class="print-ready-section" id="print-area">
    <div class="rela">
        <div class="header mb-4">
            <div class="row" style="width: 100% !important; align-items: center !important;">
                <div class="col-6">
                    <div class="logo">
                        <img class="logo-img" alt="Logo" src="/storage/company_logos/meskay-logo-20250514082251.png" style="max-height: 60px;">
                    </div>
                </div>
                <div class="col-6 text-right">
                    <h3 class="font-weight-bold text-uppercase mb-0">DRYING JOB ORDER</h3>
                    <p class="text-muted mb-0">Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $order->status) }}</strong></p>
                </div>
            </div>
        </div>

        <div class="job-order-print-container p-0 bg-white">
            <div class="row mb-3">
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr class="bg-light">
                                <th colspan="2" class="text-center font-weight-bold">BASIC INFORMATION</th>
                            </tr>
                            @if($order->exportOrder)
                            <tr>
                                <th>Export Order:</th>
                                <td><span class="badge badge-info">{{ $order->exportOrder->voucher_no }}</span></td>
                            </tr>
                            @endif
                            <tr>
                                <th width="35%">Job Order No#:</th>
                                <td><strong class="text-primary">{{ $order->job_order_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Job Order Date:</th>
                                <td>{{ \Carbon\Carbon::parse($order->job_order_date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>{{ $order->companyLocation->name ?? 'N/A' }}</td>
                            </tr>
                            @if($order->ref_no)
                            <tr>
                                <th>Reference No:</th>
                                <td>{{ $order->ref_no }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Attention To:</th>
                                <td>
                                    @php
                                        $attentionUsers = $order->attentionUsers;
                                    @endphp
                                    @if($attentionUsers->count() > 0)
                                        {{ $attentionUsers->pluck('name')->implode(', ') }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $order->creator->name ?? 'N/A' }} ({{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }})</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr class="bg-light">
                                <th colspan="2" class="text-center font-weight-bold">PROCESS & COMMODITY</th>
                            </tr>
                            <tr>
                                <th width="35%">Commodity / Product:</th>
                                <td><strong>{{ $order->product->name ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Crop Year:</th>
                                <td>{{ $order->crop_year ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Moisture Level:</th>
                                <td>
                                    @if($order->moisture_level == 'half_dried')
                                        <span class="badge badge-warning">Half-Dried (~15%)</span>
                                    @elseif($order->moisture_level == 'fully_dried')
                                        <span class="badge badge-success">Fully-Dried (13.5-14%)</span>
                                    @elseif($order->moisture_level)
                                        {{ ucwords(str_replace('_', ' ', $order->moisture_level)) }}
                                    @endif
                                    @if($order->moisture_value)
                                        <strong class="ml-1 text-primary">({{ $order->moisture_value }}%)</strong>
                                    @endif
                                    @if(!$order->moisture_level && !$order->moisture_value)
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Production Recipe:</th>
                                <td>
                                    @if($order->recipe_name || $order->productionRecipe)
                                        <strong>{{ $order->recipe_name ?: $order->productionRecipe->name }}</strong>
                                        @if($order->production_recipe_id)
                                            <span class="badge badge-info ml-1">Master</span>
                                        @else
                                            <span class="badge badge-warning ml-1">Job Order Recipe</span>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Specifications:</th>
                                <td><span class="badge badge-info">{{ $order->specifications->count() }} Configured</span></td>
                            </tr>
                            <tr>
                                <th>Recipe Parameters:</th>
                                <td><span class="badge badge-secondary">{{ $order->items->count() }} Configured</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Specifications (like Milling Job Order) -->
            @if($order->specifications->count() > 0)
            <div class="mb-3">
                <h6 class="font-weight-bold bg-light p-1 border">
                    <i class="ft-file-text mr-1 text-primary"></i>SPECIFICATIONS
                </h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="bg-light">
                            <th width="8%" class="text-center">#</th>
                            <th width="42%">Specification Name</th>
                            <th width="30%">Value</th>
                            <th width="20%">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->specifications as $index => $spec)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong>{{ $spec->spec_name }}</strong></td>
                            <td>
                                <strong>{{ $spec->spec_value }}</strong> {{ $spec->uom }}
                            </td>
                            <td class="text-uppercase">{{ $spec->value_type }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- Parameters / Recipe Details -->
            <div class="mb-3">
                <h6 class="font-weight-bold bg-light p-1 border d-flex justify-content-between align-items-center">
                    <span><i class="ft-sliders mr-1 text-primary"></i>RECIPE PARAMETERS</span>
                    @if($order->recipe_name || $order->productionRecipe)
                        <span class="font-weight-normal text-muted" style="font-size: 0.85rem;">
                            Recipe: <strong class="text-dark">{{ $order->recipe_name ?: $order->productionRecipe->name }}</strong>
                            @if($order->production_recipe_id)
                                <span class="badge badge-info ml-1">Master</span>
                            @else
                                <span class="badge badge-warning ml-1">Job Order Only</span>
                            @endif
                        </span>
                    @endif
                </h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="bg-light">
                            <th width="8%" class="text-center">#</th>
                            <th width="42%">Parameter Key</th>
                            <th width="20%">Type</th>
                            <th width="30%">Parameter Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong class="text-monospace">{{ $item->key }}</strong></td>
                            <td class="text-capitalize">{{ $item->type ?? 'text' }}</td>
                            <td><strong class="text-dark">{{ $item->value }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">
                                No recipe parameters defined for this Drying Job Order.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Remarks, Description, Other Specifications -->
            @if($order->remarks || $order->order_description || $order->other_specifications)
            <div class="row">
                @if($order->remarks)
                <div class="col-6 mb-2">
                    <div class="border p-2 rounded bg-light">
                        <strong>Remarks:</strong>
                        <p class="mb-0 text-muted">{{ $order->remarks }}</p>
                    </div>
                </div>
                @endif
                @if($order->order_description)
                <div class="col-6 mb-2">
                    <div class="border p-2 rounded bg-light">
                        <strong>Order Description:</strong>
                        <p class="mb-0 text-muted">{{ $order->order_description }}</p>
                    </div>
                </div>
                @endif
                @if($order->other_specifications)
                <div class="col-12 mb-2">
                    <div class="border p-2 rounded bg-light">
                        <strong>Other Specifications:</strong>
                        <p class="mb-0 text-muted">{{ $order->other_specifications }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
