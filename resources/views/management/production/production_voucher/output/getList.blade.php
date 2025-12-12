@php
    $headProductName = $productionVoucher->jobOrder->product->name ?? 'Head Product';

    // Get all outputs merged
    $allOutputs = collect($headProductOutputs)->merge($otherProductOutputs);

    // Group all outputs by slot
    $allOutputsGroupedBySlot = $allOutputs->groupBy(function ($output) {
        return $output->slot_id ? 'slot_' . $output->slot_id : 'no_slot';
    });

    // Calculate totals
    $totalInputSum = $inputs->sum('qty');
    $totalOutputSum = $allOutputs->sum('qty');
    $totalOutputsByProduct = $allOutputs->groupBy('product_id')->map(function ($group) {
        return $group->sum('qty');
    });
    $productYields = [];
    foreach ($totalOutputsByProduct as $productId => $totalOutput) {
        $productYields[$productId] = $totalOutputSum > 0 ? ($totalOutput / $totalOutputSum) * 100 : 0;
    }
@endphp

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Commodity</th>
                <th>Qty (kg)</th>
                <th>No of Bags</th>
                <th>Bag Size</th>
                <th>Avg Weight/Bag</th>
                <th>Yield %</th>
                <th>Storage Location</th>
                <th>Brand</th>
                <th>Job Order</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allOutputsGroupedBySlot as $slotKey => $slotOutputs)

                @php
                    $firstOutput = $slotOutputs->first();
                    $slot = $firstOutput->slot;
                    $slotId = $slot ? $slot->id : null;

                    // Slot totals
                    $slotOutputTotal = $slotOutputs->sum('qty');
                    $slotInputTotal = $slotId ? $inputs->where('slot_id', $slotId)->sum('qty') : 0;

                    // Slot Yield: (Slot Output / Total Output Sum) * 100 - for breakdown percentage
                    $slotYield = $totalOutputSum > 0 ? ($slotOutputTotal / $totalOutputSum) * 100 : 0;

                    // Slot Efficiency Yield: (Slot Output / Slot Input) * 100 - for efficiency
                    $slotEfficiencyYield = $slotInputTotal > 0 ? ($slotOutputTotal / $slotInputTotal) * 100 : 0;

                    // Slot bag totals
                    $slotTotalBags = $slotOutputs->sum('no_of_bags') ?? 0;
                    $slotBagSizes = $slotOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                    $slotAvgWeightPerBag = $slotTotalBags > 0 ? ($slotOutputTotal / $slotTotalBags) : 0;

                    // Separate head product and by product for this slot
                    $slotHeadProductOutputs = $slotOutputs->where('product_id', $headProductId);
                    $slotOtherProductOutputs = $slotOutputs->where('product_id', '!=', $headProductId);
                @endphp

                {{-- Slot Header --}}
                @if($slot)
                    @if($slotKey != 'slot_1')
                        <tr>
                            <td colspan="11" class="border-0">
                                <hr class="my-3" />
                            </td>
                        </tr>
                    @endif
                    <tr class="table-info slot-header-row">
                        <td class="slot-header-cell">
                            <i class="ft-calendar"></i>
                            <strong>Slot: {{ $slot->date ? $slot->date->format('Y-m-d') : 'N/A' }} | Time:
                                {{ $slot->start_time ?? 'N/A' }}@if($slot->end_time) - {{ $slot->end_time }}@endif</strong>
                        </td>
                        <td>
                            <strong>{{ number_format($slotOutputTotal, 2) }} kg</strong>
                        </td>

                        <td colspan="3">
                            <strong>{{ $slotTotalBags }}</strong>
                        </td>

                        <td>
                            <strong>{{ number_format($slotYield, 2) }}%</strong>
                        </td>
                        <td colspan="5">    
                            <span class="float-right">
                                <strong>Output: {{ number_format($slotOutputTotal, 2) }} kg</strong>
                                @if($slotInputTotal > 0)
                                    | Input: {{ number_format($slotInputTotal, 2) }} kg
                                    | Yield: <span class="badge badge-warning">{{ number_format($slotEfficiencyYield, 2) }}%</span>
                                @endif
                    
                                | Slot %: <span class="badge badge-info">{{ number_format($slotYield, 2) }}%</span>
                                (of {{ number_format($totalOutputSum, 2) }} kg)
                            </span>
                        </td>
                    </tr>
                @endif

                {{-- Head Product Section for this Slot --}}
                @if($headProductId && count($slotHeadProductOutputs) > 0)
                    @php
                        $slotHeadProductTotal = $slotHeadProductOutputs->sum('qty');
                        $slotHeadProductYield = $slotOutputTotal > 0 ? ($slotHeadProductTotal / $slotOutputTotal) * 100 : 0;
                        $slotHeadProductOutputsByCommodity = $slotHeadProductOutputs->groupBy('product_id');
                        $slotHeadProductTotalBags = $slotHeadProductOutputs->sum('no_of_bags') ?? 0;
                        $slotHeadProductBagSizes = $slotHeadProductOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                        $slotHeadProductAvgWeightPerBag = $slotHeadProductTotalBags > 0 ? ($slotHeadProductTotal / $slotHeadProductTotalBags) : 0;
                    @endphp

                    <tr class="table-primary head-product-row">
                        <td colspan="1" class="head-product-cell">
                            <i class="ft-star"></i> Head Product - {{ $headProductName }}
                            <!-- | Yield: <span
                                                                                class="badge badge-warning">{{ number_format($slotHeadProductYield, 2) }}%</span> -->
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($slotHeadProductTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotHeadProductTotalBags > 0 ? $slotHeadProductTotalBags : '-' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $slotHeadProductBagSizes ? $slotHeadProductBagSizes : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotHeadProductAvgWeightPerBag > 0 ? number_format($slotHeadProductAvgWeightPerBag, 3) : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong class="badge badge-warning">{{ number_format($slotHeadProductYield, 2) }}%</strong>
                        </td>
                        <td colspan="5"></td>

                    </tr>

                    @foreach($slotHeadProductOutputsByCommodity as $productId => $commodityOutputs)
                        {{-- Commodity Rows --}}
                        @foreach($commodityOutputs as $output)
                            @php
                                $rowYield = $slotOutputTotal > 0 ? ($output->qty / $slotOutputTotal) * 100 : 0;
                            @endphp
                            <tr data-output-id="{{ $output->id }}">
                                <td class="col-md-3">{{ $output->product->name ?? 'N/A' }}</td>
                                <td>{{ number_format($output->qty, 2) }}</td>
                                <td>{{ $output->no_of_bags ?? '-' }}</td>
                                <td>{{ $output->bag_size ?? '-' }}</td>
                                <td>{{ $output->avg_weight_per_bag ? number_format($output->avg_weight_per_bag, 3) : '-' }}</td>
                                <td><strong>{{ number_format($rowYield, 2) }}%</strong></td>
                                <td>{{ $output->storageLocation ? ($output->storageLocation->name . ' (' . ($output->storageLocation->arrivalLocation->name ?? 'N/A') . ')') : 'N/A' }}</td>
                                <td>{{ $output->brand->name ?? '-' }}</td>
                                <td>
                                    @if($output->jobOrder)
                                        <span class="badge badge-info">{{ $output->jobOrder->job_order_no }}{{ $output->jobOrder->ref_no ? ' (' . $output->jobOrder->ref_no . ')' : '' }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $output->remarks ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        onclick="openModal(this, '{{ route('production-voucher.output.edit-form', [$productionVoucher->id, $output->id]) }}', 'Edit Production Output', false, '50%')">
                                        <i class="ft-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                        <i class="ft-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Commodity Total --}}
                        @php
                            $commodityProduct = $commodityOutputs->first()->product ?? null;
                            $slotCommodityTotal = $commodityOutputs->sum('qty');
                            $slotCommodityYield = $slotOutputTotal > 0 ? ($slotCommodityTotal / $slotOutputTotal) * 100 : 0;
                            $slotCommodityTotalBags = $commodityOutputs->sum('no_of_bags') ?? 0;
                            $slotCommodityBagSizes = $commodityOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                            $slotCommodityAvgWeightPerBag = $slotCommodityTotalBags > 0 ? ($slotCommodityTotal / $slotCommodityTotalBags) : 0;
                        @endphp
                        <tr class="table-warnin  commodity-total-row">
                            <td class="commodity-total-cell">
                                Total - {{ $commodityProduct->name ?? 'N/A' }}
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ number_format($slotCommodityTotal, 2) }} kg</strong>
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ $slotCommodityTotalBags > 0 ? $slotCommodityTotalBags : '-' }}</strong>
                            </td>
                            <td>
                                <strong>{{ $slotCommodityBagSizes ? $slotCommodityBagSizes : '-' }}</strong>
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ $slotCommodityAvgWeightPerBag > 0 ? number_format($slotCommodityAvgWeightPerBag, 3) : '-' }}</strong>
                            </td>
                            <td class="commodity-total-qty bg-light-warning ">
                                <strong class="">{{ number_format($slotCommodityYield, 2) }}%</strong>
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    @endforeach

                    {{-- Head Product Total Row --}}
                    <tr class="table-primary head-product-row font-weight-bold d-none">
                        <td class="head-product-cell">
                            <strong>Total - Head Product</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($slotHeadProductTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotHeadProductTotalBags > 0 ? $slotHeadProductTotalBags : '-' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $slotHeadProductBagSizes ? $slotHeadProductBagSizes : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotHeadProductAvgWeightPerBag > 0 ? number_format($slotHeadProductAvgWeightPerBag, 3) : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong class="badge badge-warning">{{ number_format($slotHeadProductYield, 2) }}%</strong>
                        </td>
                        <td colspan="5"></td>
                    </tr>
                @endif

                {{-- By Product Section for this Slot --}}
                @if(count($slotOtherProductOutputs) > 0)
                    @php
                        $slotOtherProductTotal = $slotOtherProductOutputs->sum('qty');
                        $slotOtherProductYield = $slotOutputTotal > 0 ? ($slotOtherProductTotal / $slotOutputTotal) * 100 : 0;
                        $slotOtherProductOutputsByCommodity = $slotOtherProductOutputs->groupBy('product_id');
                        $slotOtherProductTotalBags = $slotOtherProductOutputs->sum('no_of_bags') ?? 0;
                        $slotOtherProductBagSizes = $slotOtherProductOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                        $slotOtherProductAvgWeightPerBag = $slotOtherProductTotalBags > 0 ? ($slotOtherProductTotal / $slotOtherProductTotalBags) : 0;
                    @endphp

                    <tr class="table-info by-product-row">
                        <td colspan="1" class="by-product-cell">
                            <i class="ft-list"></i> By Product
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($slotOtherProductTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotOtherProductTotalBags > 0 ? $slotOtherProductTotalBags : '-' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $slotOtherProductBagSizes ? $slotOtherProductBagSizes : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotOtherProductAvgWeightPerBag > 0 ? number_format($slotOtherProductAvgWeightPerBag, 3) : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong class="badge badge-warning">{{ number_format($slotOtherProductYield, 2) }}%</strong>
                        </td>
                        <td colspan="4"></td>
     
                    </tr>

                    @foreach($slotOtherProductOutputsByCommodity as $productId => $commodityOutputs)
                        {{-- Commodity Rows --}}
                        @foreach($commodityOutputs as $output)
                            @php
                                $rowYield = $slotOutputTotal > 0 ? ($output->qty / $slotOutputTotal) * 100 : 0;
                            @endphp
                            <tr data-output-id="{{ $output->id }}">
                                <td class="col-md-3">{{ $output->product->name ?? 'N/A' }}</td>
                                <td>{{ number_format($output->qty, 2) }}</td>
                                <td>{{ $output->no_of_bags ?? '-' }}</td>
                                <td>{{ $output->bag_size ?? '-' }}</td>
                                <td>{{ $output->avg_weight_per_bag ? number_format($output->avg_weight_per_bag, 3) : '-' }}</td>
                                <td><strong>{{ number_format($rowYield, 2) }}%</strong></td>
                                <td>{{ $output->storageLocation ? ($output->storageLocation->name . ' (' . ($output->storageLocation->arrivalLocation->name ?? 'N/A') . ')') : 'N/A' }}</td>
                                <td>{{ $output->brand->name ?? '-' }}</td>
                                <td>
                                    @if($output->jobOrder)
                                        <span class="badge badge-info">{{ $output->jobOrder->job_order_no }}{{ $output->jobOrder->ref_no ? ' (' . $output->jobOrder->ref_no . ')' : '' }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $output->remarks ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        onclick="openModal(this, '{{ route('production-voucher.output.edit-form', [$productionVoucher->id, $output->id]) }}', 'Edit Production Output', false, '50%')">
                                        <i class="ft-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                        <i class="ft-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Commodity Total --}}
                        @php
                            $commodityProduct = $commodityOutputs->first()->product ?? null;
                            $slotCommodityTotal = $commodityOutputs->sum('qty');
                            $slotCommodityYield = $slotOutputTotal > 0 ? ($slotCommodityTotal / $slotOutputTotal) * 100 : 0;
                        @endphp
                        {{-- Commodity Total --}}
                        @php
                            $slotCommodityTotalBags = $commodityOutputs->sum('no_of_bags') ?? 0;
                            $slotCommodityBagSizes = $commodityOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                            $slotCommodityAvgWeightPerBag = $slotCommodityTotalBags > 0 ? ($slotCommodityTotal / $slotCommodityTotalBags) : 0;
                        @endphp
                        <tr class="table-warnin commodity-total-row">
                            <td class="commodity-total-cell font-weight-bold col-md-3">
                                Total - {{ $commodityProduct->name ?? 'N/A' }}
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ number_format($slotCommodityTotal, 2) }} kg</strong>
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ $slotCommodityTotalBags > 0 ? $slotCommodityTotalBags : '-' }}</strong>
                            </td>
                            <td>
                                <strong>{{ $slotCommodityBagSizes ? $slotCommodityBagSizes : '-' }}</strong>
                            </td>
                            <td class="commodity-total-qty">
                                <strong>{{ $slotCommodityAvgWeightPerBag > 0 ? number_format($slotCommodityAvgWeightPerBag, 3) : '-' }}</strong>
                            </td>
                            <td class="commodity-total-qty bg-light-warning ">
                                <strong class="">{{ number_format($slotCommodityYield, 2) }}%</strong>
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    @endforeach

                    {{-- By Product Total Row --}}
                    <tr class="table-info by-product-row font-weight-bold d-none">
                        <td class="by-product-cell">
                            <strong>Total - By Product</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($slotOtherProductTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotOtherProductTotalBags > 0 ? $slotOtherProductTotalBags : '-' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $slotOtherProductBagSizes ? $slotOtherProductBagSizes : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $slotOtherProductAvgWeightPerBag > 0 ? number_format($slotOtherProductAvgWeightPerBag, 3) : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong class="badge badge-warning">{{ number_format($slotOtherProductYield, 2) }}%</strong>
                        </td>
                        <td colspan="5"></td>
                    </tr>
                @endif
            @endforeach

            {{-- Grand Totals at the End --}}
            @if($totalOutputSum > 0)
                <tr>
                    <td colspan="11" class="border-0">
                        <hr class="my-3 text-white bg-white border-0" />
                    </td>
                </tr>
                <tr class="table-secondary" style="background-color: #f8f9fa !important;">
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Commodity</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Qty (kg)</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">No of Bags</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Bag Size</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Avg Weight/Bag</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Yield %</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Storage Location</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Brand</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Job Order</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Remarks</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;">Actions</th>
                </tr>
                <tr class="table-success grand-total-row">
                    <td colspan="11" class="grand-total-cell">
                        <h6 class="mb-0"><i class="ft-bar-chart-2"></i> Grand Totals</h6>
                    </td>
                </tr>

                @foreach($totalOutputsByProduct as $productId => $productTotal)
                    @php
                        $productName = $allOutputs->firstWhere('product_id', $productId)->product->name ?? 'N/A';
                        $productYield = $productYields[$productId] ?? 0;
                        $productTotalValue = (float) $productTotal;
                        $productOutputs = $allOutputs->where('product_id', $productId);
                        $productTotalBags = $productOutputs->sum('no_of_bags') ?? 0;
                        $productBagSizes = $productOutputs->whereNotNull('bag_size')->pluck('bag_size')->unique()->implode(', ');
                        $productAvgWeightPerBag = $productTotalBags > 0 ? ($productTotalValue / $productTotalBags) : 0;
                    @endphp
                    <tr class="table-warning grand-total-commodity-row">
                        <td class="grand-total-commodity-cell col-md-3 ">
                            <i class="ft-check-square"></i> Total - {{ $productName }}
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($productTotalValue, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $productTotalBags > 0 ? $productTotalBags : '-' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $productBagSizes ? $productBagSizes : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ $productAvgWeightPerBag > 0 ? number_format($productAvgWeightPerBag, 3) : '-' }}</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong class="badge badge-warning">{{ number_format($productYield, 2) }}%</strong>
                        </td>
                        <td colspan="5"></td>
                    </tr>
                @endforeach
            @endif

            {{-- No Outputs Message --}}
            @if(count($allOutputs) == 0)
                <tr>
                    <td colspan="11" class="text-center">No production outputs found</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Grand Total Summary Card --}}
@if($totalOutputSum > 0)
    <div class="mb-4 mt-3">
        <h6 class="header-heading-sepration mb-3"><i class="ft-bar-chart-2"></i> Grand Total Summary</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card summary-box summary-box-info">
                    <div class="card-number text-info">{{ number_format($totalInputSum, 2) }}</div>
                    <div class="card-title">Total Input</div>
                    <div class="card-subtitle">kg</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card summary-box summary-box-success">
                    <div class="card-number text-success">{{ number_format($totalOutputSum, 2) }}</div>
                    <div class="card-title">Total Output</div>
                    <div class="card-subtitle">kg</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card summary-box summary-box-primary">
                    <div class="card-number text-primary">{{ number_format(100, 2) }}%</div>
                    <div class="card-title">Total Yield</div>
                    <div class="card-subtitle">Percentage</div>
                </div>
            </div>
        </div>
    </div>
@endif