<table class="table table-bordered">
    <thead>
        <tr>
            <th>Commodity</th>
            <th>Location</th>
            <th>Qty (kg)</th>
            <th>Yield %</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

        @php
            // Calculate total sum of all inputs
            $totalInputSum = $inputs->sum('qty');

            // Calculate total outputs by product for commodity totals
            $totalInputsByProduct = $inputs->groupBy('product_id')->map(function ($group) {
                return $group->sum('qty');
            });

            // Group inputs by slot
            $groupedInputs = $inputs->groupBy(function ($input) {
                return $input->slot_id ? 'slot_' . $input->slot_id : 'no_slot';
            });
        @endphp

        @if (count($inputs) != 0)
            @php
                $isFirstSlot = true;
            @endphp
            @foreach($groupedInputs as $groupKey => $groupInputs)
                @php
                    $firstInput = $groupInputs->first();
                    $slot = $firstInput->slot;

                    // Calculate slot input total
                    $slotInputTotal = $groupInputs->sum('qty');
                    // Slot Percentage: (Slot Input / Total Input Sum) * 100 - for breakdown percentage
                    $slotPercentage = $totalInputSum > 0 ? ($slotInputTotal / $totalInputSum) * 100 : 0;

                    // Calculate slot output total and yield (output/input)
                    $slotId = $slot ? $slot->id : null;
                    $slotOutputTotal = $slotId && isset($outputs) ? $outputs->where('slot_id', $slotId)->sum('qty') : 0;
                    $slotYield = $slotInputTotal > 0 ? ($slotOutputTotal / $slotInputTotal) * 100 : 0;
                @endphp

                {{-- Slot Details Row with Total --}}
                @if($slot)
                    @if(!$isFirstSlot)
                        <tr>
                            <td colspan="6" class="border-0">
                                <hr class="my-3" />
                            </td>
                        </tr>
                    @endif
                    @php
                        $isFirstSlot = false;
                    @endphp
                    <tr class="table-info slot-header-row">
                        <td colspan="6" class="slot-header-cell">
                            <i class="ft-calendar"></i>
                            <strong>Slot: {{ $slot->date ? $slot->date->format('Y-m-d') : 'N/A' }} | Time:
                                {{ $slot->start_time ?? 'N/A' }}@if($slot->end_time) - {{ $slot->end_time }}@endif</strong>
                            <span class="float-right">
                                <strong>Input: {{ number_format($slotInputTotal, 2) }} kg</strong>
                                @if(isset($outputs) && $slotOutputTotal > 0)
                                    | Output: <span class="text-success">{{ number_format($slotOutputTotal, 2) }} kg</span>
                                    | Yield: <span class="badge badge-warning">{{ number_format($slotYield, 2) }}%</span>
                                @endif
                                | Slot %: <span class="badge badge-info">{{ number_format($slotPercentage, 2) }}%</span>
                                (of {{ number_format($totalInputSum, 2) }} kg)
                            </span>
                        </td>
                    </tr>
                @endif

                {{-- Input Rows for this Slot, grouped by Commodity --}}
                @php
                    $groupInputsByCommodity = $groupInputs->groupBy('product_id');
                @endphp
                @foreach($groupInputsByCommodity as $productId => $commodityInputs)
                    {{-- Input Rows for this Commodity --}}
                    @foreach($commodityInputs as $input)
                        @php
                            // Row wise percentage: (this input qty / slot input total) * 100
                            $rowPercentage = $slotInputTotal > 0 ? ($input->qty / $slotInputTotal) * 100 : 0;
                        @endphp
                        <tr data-input-id="{{ $input->id }}">
                            <td class="col-md-3">{{ $input->product->name ?? 'N/A' }}</td>
                            <td>{{ $input->location->name ?? 'N/A' }}</td>
                            <td>{{ number_format($input->qty, 2) }}</td>
                            <td><strong>{{ number_format($rowPercentage, 2) }}%</strong></td>
                            <td>{{ $input->remarks ?? '-' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary"
                                    onclick="openModal(this, '{{ route('production-voucher.input.edit-form', [$productionVoucher->id, $input->id]) }}', 'Edit Production Input', false, '50%')">
                                    <i class="ft-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteProductionInput({{ $productionVoucher->id }}, {{ $input->id }})">
                                    <i class="ft-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Commodity Total Row for this Slot --}}
                    @php
                        $commodityProduct = $commodityInputs->first()->product ?? null;
                        $slotCommodityTotal = $commodityInputs->sum('qty');
                        // Commodity percentage based on slot input total (slot itself is 100%)
                        $slotCommodityPercentage = $slotInputTotal > 0 ? ($slotCommodityTotal / $slotInputTotal) * 100 : 0;
                    @endphp
                    <tr class="table-warnig font-weight-bold commodity-total-row">
                        <td class="commodity-total-cell font-weight-bold">
                            Total - {{ $commodityProduct->name ?? 'N/A' }}
                        </td>
                        <td></td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($slotCommodityTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty bg-light-warning">
                            <strong>{{ number_format($slotCommodityPercentage, 2) }}%</strong>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                @endforeach
            @endforeach

            {{-- Commodity-wise Total Rows (All Slots Combined) --}}
            @if(count($totalInputsByProduct) > 0)
                <tr>
                    <td colspan="6" class="border-0">
                        <hr class="my-3 text-white bg-white border-0" />
                    </td>
                </tr>
                <tr class="table-success grand-total-row">
                    <td colspan="6" class="grand-total-cell">
                        <h6 class="mb-0"><i class="ft-bar-chart-2"></i> Grand Totals</h6>
                    </td>
                </tr>
                @foreach($totalInputsByProduct as $productId => $productTotal)
                    @php
                        $commodityProduct = $inputs->where('product_id', $productId)->first()->product ?? null;
                        $commodityTotal = (float) $productTotal;
                        $commodityPercentage = $totalInputSum > 0 ? ($commodityTotal / $totalInputSum) * 100 : 0;
                    @endphp
                    <tr class="table-warning grand-total-commodity-row">
                        <td class="grand-total-commodity-cell">
                            Total - {{ $commodityProduct->name ?? 'N/A' }}
                        </td>
                        <td></td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($commodityTotal, 2) }} kg</strong>
                        </td>
                        <td class="commodity-total-qty">
                            <strong>{{ number_format($commodityPercentage, 2) }}%</strong>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                @endforeach
            @endif
        @else
            <tr>
                <td colspan="6" class="text-center">No production inputs found</td>
            </tr>
        @endif
    </tbody>
</table>