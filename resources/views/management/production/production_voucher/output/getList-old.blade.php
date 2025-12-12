@php
    $headProductName = $productionVoucher->jobOrder->product->name ?? 'Head Product';

    // Group outputs by slot
    $groupedHeadOutputs = $headProductOutputs->groupBy(function ($output) {
        return $output->slot_id ? 'slot_' . $output->slot_id : 'no_slot';
    });

    $groupedOtherOutputs = $otherProductOutputs->groupBy(function ($output) {
        return $output->slot_id ? 'slot_' . $output->slot_id : 'no_slot';
    });

    // Calculate total inputs by product
    $totalInputsByProduct = $inputs->groupBy('product_id')->map(function($group) {
        return $group->sum('qty');
    });

    // Calculate total outputs by product
    $totalOutputsByProduct = collect($headProductOutputs)->merge($otherProductOutputs)
        ->groupBy('product_id')
        ->map(function($group) {
            return $group->sum('qty');
        });

    // Calculate product wise yields (based on total output sum)
    $productYields = [];
    $totalOutputSum = collect($headProductOutputs)->merge($otherProductOutputs)->sum('qty');
    foreach($totalOutputsByProduct as $productId => $totalOutput) {
        $productYields[$productId] = $totalOutputSum > 0 ? ($totalOutput / $totalOutputSum) * 100 : 0;
    }

    // Group outputs by product for display
    $headProductOutputsByCommodity = $headProductOutputs->groupBy('product_id');
    $otherProductOutputsByCommodity = $otherProductOutputs->groupBy('product_id');
@endphp

{{-- Head Product Section --}}
@if($headProductId && count($headProductOutputs) > 0)
    <div class="mb-4">
        <h6 class="header-heading-sepration bg-primary text-white p-2 mb-3">
            <i class="ft-star"></i> Head Product - {{ $headProductName }}
            @php
                $headProductTotalOutput = $headProductOutputs->sum('qty');
                $headProductYield = $totalOutputSum > 0 ? ($headProductTotalOutput / $totalOutputSum) * 100 : 0;
            @endphp
            | <strong>Total Yield:</strong> <span class="badge badge-warning">{{ number_format($headProductYield, 2) }}%</span>
            (Head Product Output: {{ number_format($headProductTotalOutput, 2) }} kg / Total Output: {{ number_format($totalOutputSum, 2) }} kg)
        </h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Commodity</th>
                        <th>Qty (kg)</th>
                        <th>Yield %</th>
                        <th>Storage Location</th>
                        <th>Brand</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedHeadOutputs as $groupKey => $groupOutputs)
                        @php
                            $firstOutput = $groupOutputs->first();
                            $slot = $firstOutput->slot;
                        @endphp

                        @php
                            // Calculate slot totals
                            $slotOutputTotal = $groupOutputs->sum('qty');
                            $slotYield = $totalOutputSum > 0 ? ($slotOutputTotal / $totalOutputSum) * 100 : 0;
                            $slotId = $slot ? $slot->id : null;
                        @endphp

                        {{-- Slot Details Row with Yield --}}
                        @if($slot)
                            <tr class="table-info" style="background-color: #d1ecf1;">
                                <td style="font-weight: bold;">
                                    <i class="ft-calendar"></i>
                                    <strong>Slot:</strong> {{ $slot->date ? $slot->date->format('Y-m-d') : 'N/A' }} |
                                    <strong>Time:</strong> {{ $slot->start_time ?? 'N/A' }}
                                    @if($slot->end_time)
                                        - {{ $slot->end_time }}
                                    @endif
                                </td>
                                <td style="font-weight: bold; text-align: right;">
                                    <strong>{{ number_format($slotOutputTotal, 2) }} kg</strong>
                                </td>
                                <td style="font-weight: bold; text-align: right;">
                                    <span class="badge badge-warning">{{ number_format($slotYield, 2) }}%</span>
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        @endif

                        {{-- Output Rows for this Slot, grouped by Commodity --}}
                        @php
                            $groupOutputsByCommodity = $groupOutputs->groupBy('product_id');
                        @endphp
                        @foreach($groupOutputsByCommodity as $productId => $commodityOutputs)
                            {{-- Output Rows for this Commodity --}}
                            @foreach($commodityOutputs as $output)
                                @php
                                    // Row wise yield: (this output qty / total output sum) * 100
                                    $rowYield = $totalOutputSum > 0 ? ($output->qty / $totalOutputSum) * 100 : 0;
                                @endphp
                                <tr data-output-id="{{ $output->id }}">
                                    <td class="col-md-3">{{ $output->product->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($output->qty, 2) }}</td>
                                    <td><strong>{{ number_format($rowYield, 2) }}%</strong></td>
                                    <td>{{ $output->storageLocation->name ?? 'N/A' }}</td>
                                    <td>{{ $output->brand->name ?? '-' }}</td>
                                    <td>{{ $output->remarks ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="editProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                            <i class="ft-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                            <i class="ft-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach

                    {{-- Commodity-wise Total Rows (All Slots Combined) for Head Product --}}
                    @foreach($totalOutputsByProduct as $productId => $productTotal)
                        @php
                            // Check if this product exists in head product outputs
                            $productInHeadProduct = $headProductOutputs->where('product_id', $productId)->first();
                            if(!$productInHeadProduct) continue;
                            
                            $commodityProduct = $productInHeadProduct->product ?? null;
                            $commodityTotal = (float) $productTotal;
                            $commodityYield = $totalOutputSum > 0 ? ($commodityTotal / $totalOutputSum) * 100 : 0;
                        @endphp
                        <tr class="table-warning" style="background-color: #fff3cd;">
                            <td style="font-weight: bold; padding-left: 30px;">
                                <i class="ft-check-square"></i> Total - {{ $commodityProduct->name ?? 'N/A' }}
                            </td>
                            <td style="font-weight: bold; text-align: right;">
                                <strong>{{ number_format($commodityTotal, 2) }} kg</strong>
                            </td>
                            <td style="font-weight: bold; text-align: right;">
                                <strong class="badge badge-warning">{{ number_format($commodityYield, 2) }}%</strong>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- By Product Section --}}

<div class="mb-4">
    <h6 class="header-heading-sepration bg-info text-white p-2 mb-3">
        <i class="ft-list"></i> By Product
        @php
            $otherProductsTotalOutput = $otherProductOutputs->sum('qty');
            $otherProductsYield = $totalOutputSum > 0 ? ($otherProductsTotalOutput / $totalOutputSum) * 100 : 0;
        @endphp
        | <strong>Total Yield:</strong> <span class="badge badge-warning">{{ number_format($otherProductsYield, 2) }}%</span>
        (Other Products Output: {{ number_format($otherProductsTotalOutput, 2) }} kg / Total Output: {{ number_format($totalOutputSum, 2) }} kg)
    </h6>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Commodity</th>
                    <th>Qty (kg)</th>
                    <th>Yield %</th>
                    <th>Storage Location</th>
                    <th>Brand</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(count($otherProductOutputs) > 0)
                    @foreach($groupedOtherOutputs as $groupKey => $groupOutputs)
                        @php
                            $firstOutput = $groupOutputs->first();
                            $slot = $firstOutput->slot;
                        @endphp

                        @php
                            // Calculate slot totals
                            $slotOutputTotal = $groupOutputs->sum('qty');
                            $slotYield = $totalOutputSum > 0 ? ($slotOutputTotal / $totalOutputSum) * 100 : 0;
                            $slotId = $slot ? $slot->id : null;
                        @endphp

                        {{-- Slot Details Row with Yield --}}
                        @if($slot)
                            <tr class="table-info" style="background-color: #d1ecf1;">
                                <td style="font-weight: bold;">
                                    <i class="ft-calendar"></i>
                                    <strong>Slot:</strong> {{ $slot->date ? $slot->date->format('Y-m-d') : 'N/A' }} |
                                    <strong>Time:</strong> {{ $slot->start_time ?? 'N/A' }}
                                    @if($slot->end_time)
                                        - {{ $slot->end_time }}
                                    @endif
                                </td>
                                <td style="font-weight: bold; text-align: right;">
                                    <strong>{{ number_format($slotOutputTotal, 2) }} kg</strong>
                                </td>
                                <td style="font-weight: bold; text-align: right;">
                                    <span class="badge badge-warning">{{ number_format($slotYield, 2) }}%</span>
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        @endif

                        {{-- Output Rows for this Slot, grouped by Commodity --}}
                        @php
                            $groupOutputsByCommodity = $groupOutputs->groupBy('product_id');
                        @endphp
                        @foreach($groupOutputsByCommodity as $productId => $commodityOutputs)
                            {{-- Output Rows for this Commodity --}}
                            @foreach($commodityOutputs as $output)
                                @php
                                    // Row wise yield: (this output qty / total output sum) * 100
                                    $rowYield = $totalOutputSum > 0 ? ($output->qty / $totalOutputSum) * 100 : 0;
                                @endphp
                                <tr data-output-id="{{ $output->id }}">
                                    <td class="col-md-3">{{ $output->product->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($output->qty, 2) }}</td>
                                    <td><strong>{{ number_format($rowYield, 2) }}%</strong></td>
                                    <td>{{ $output->storageLocation->name ?? 'N/A' }}</td>
                                    <td>{{ $output->brand->name ?? '-' }}</td>
                                    <td>{{ $output->remarks ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="editProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                            <i class="ft-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                                            <i class="ft-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach

                    {{-- Commodity-wise Total Rows (All Slots Combined) for By Product --}}
                    @foreach($totalOutputsByProduct as $productId => $productTotal)
                        @php
                            // Check if this product exists in other product outputs (not in head product)
                            $productInOtherProducts = $otherProductOutputs->where('product_id', $productId)->first();
                            $productInHeadProduct = $headProductOutputs->where('product_id', $productId)->first();
                            
                            // Skip if it's in head product or not in other products
                            if($productInHeadProduct || !$productInOtherProducts) continue;
                            
                            $commodityProduct = $productInOtherProducts->product ?? null;
                            $commodityTotal = (float) $productTotal;
                            $commodityYield = $totalOutputSum > 0 ? ($commodityTotal / $totalOutputSum) * 100 : 0;
                        @endphp
                        <tr class="table-warning" style="background-color: #fff3cd;">
                            <td style="font-weight: bold; padding-left: 30px;">
                                <i class="ft-check-square"></i> Total - {{ $commodityProduct->name ?? 'N/A' }}
                            </td>
                            <td style="font-weight: bold; text-align: right;">
                                <strong>{{ number_format($commodityTotal, 2) }} kg</strong>
                            </td>
                            <td style="font-weight: bold; text-align: right;">
                                <strong class="badge badge-warning">{{ number_format($commodityYield, 2) }}%</strong>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center">No production outputs found</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>


{{-- Grand Total Summary --}}
@if($totalOutputSum > 0)
    <div class="mb-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="ft-bar-chart-2"></i> Grand Total Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5><strong>Total Input:</strong> <span class="text-info">{{ number_format($inputs->sum('qty'), 2) }} kg</span></h5>
                    </div>
                    <div class="col-md-4">
                        <h5><strong>Total Output:</strong> <span class="text-success">{{ number_format($totalOutputSum, 2) }} kg</span></h5>
                    </div>
                    <div class="col-md-4 text-right">
                        <h5><strong>Total Yield:</strong> <span class="badge badge-success badge-lg">{{ number_format(100, 2) }}%</span></h5>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6><strong>Breakdown by Commodity:</strong></h6>
                        <ul class="list-group">
                            @foreach($totalOutputsByProduct as $productId => $productTotal)
                                @php
                                    $productName = collect($headProductOutputs)->merge($otherProductOutputs)
                                        ->firstWhere('product_id', $productId)->product->name ?? 'N/A';
                                    $productYield = $productYields[$productId] ?? 0;
                                    $productTotalValue = (float) $productTotal;
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="ft-package"></i> {{ $productName }}</span>
                                    <span>
                                        <strong>{{ number_format($productTotalValue, 2) }} kg</strong>
                                        <span class="badge badge-warning ml-2">{{ number_format($productYield, 2) }}%</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- No Outputs Message --}}
@if((!$headProductId || count($headProductOutputs) == 0) && count($otherProductOutputs) == 0)
    <div class="alert alert-info">
        <p class="mb-0">No production outputs found</p>
    </div>
@endif