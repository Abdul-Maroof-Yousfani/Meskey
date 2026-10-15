<table class="table table-hover table-striped m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Job Order No#</th>
            <th width="10%">Date</th>
            <th width="12%">Location</th>
            <th width="14%">Commodity</th>
            <th width="9%">Crop Year</th>
            <th width="13%">Moisture Level</th>
            <th width="13%">Specs & Recipe</th>
            <th width="6%">Status</th>
            <th width="8%" class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($dryingJobOrders) != 0)
            @foreach ($dryingJobOrders as $order)
                <tr>
                    <td>
                        <div>
                            <strong class="text-primary">{{ $order->job_order_no }}</strong>
                            @if($order->exportOrder)
                                <small class="text-info d-block font-weight-bold"><i class="ft-tag mr-1"></i>EO: {{ $order->exportOrder->voucher_no }}</small>
                            @endif
                            @if($order->ref_no)
                                <small class="text-muted d-block">Ref: {{ $order->ref_no }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($order->job_order_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-light border">{{ $order->companyLocation->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <strong>{{ $order->product->name ?? 'N/A' }}</strong>
                        @if($order->recipe_name || $order->productionRecipe)
                            <small class="text-success d-block" title="Recipe">
                                <i class="ft-book-open mr-1"></i>{{ $order->recipe_name ?: $order->productionRecipe->name }}
                                @if(!$order->production_recipe_id)
                                    <span class="badge badge-warning p-0 px-1 font-weight-normal" style="font-size: 70%;">Custom</span>
                                @endif
                            </small>
                        @endif
                    </td>
                    <td>{{ $order->crop_year ?? 'N/A' }}</td>
                    <td>
                        @if($order->moisture_level == 'half_dried')
                            <span class="badge badge-warning">Half-Dried</span>
                        @elseif($order->moisture_level == 'fully_dried')
                            <span class="badge badge-success">Fully-Dried</span>
                        @elseif($order->moisture_level)
                            {{ ucwords(str_replace('_', ' ', $order->moisture_level)) }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                        @if($order->moisture_value)
                            <small class="d-block font-weight-bold text-dark">{{ $order->moisture_value }}%</small>
                        @endif
                    </td>
                    <td>
                        <div>
                            <span class="badge badge-info" title="{{ $order->specifications->pluck('spec_name')->implode(', ') }}">
                                <i class="ft-file-text mr-1"></i>{{ $order->specifications->count() }} Specs
                            </span>
                            <span class="badge badge-secondary d-inline-block mt-1" title="{{ $order->items->pluck('key')->implode(', ') }}">
                                <i class="ft-sliders mr-1"></i>{{ $order->items->count() }} Params
                            </span>
                        </div>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($order->status) {
                                'completed' => 'badge-success',
                                'in_progress' => 'badge-primary',
                                'cancelled' => 'badge-danger',
                                default => 'badge-warning'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status ?? 'pending')) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group" role="group">
                            <a href="{{ route('production-drying-job-order.edit', $order->id) }}"
                                onclick="loadPageContent('{{ route('production-drying-job-order.edit', $order->id) }}')"
                                class="btn btn-outline-primary btn-sm" title="Edit">
                                <i class="ft-edit"></i>
                            </a>
                            <button type="button"
                                onclick="deletemodal('{{ route('production-drying-job-order.destroy', $order->id) }}', '{{ route('get.production-drying-job-order') }}')"
                                class="btn btn-outline-danger btn-sm" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                            <button type="button"
                                onclick="openModal(this, '{{ route('production-drying-job-order.show', $order->id) }}', 'Drying Job Order Details', true, '85%')"
                                class="btn btn-outline-info btn-sm" title="View">
                                <i class="ft-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <i class="ft-info mr-1"></i> No Drying Job Orders found.
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($dryingJobOrders) != 0)
<div class="row w-100 mx-auto mt-2">
    <div class="col-md-6">
        Showing {{ $dryingJobOrders->firstItem() ?? 0 }} to {{ $dryingJobOrders->lastItem() ?? 0 }} of {{ $dryingJobOrders->total() }} entries
    </div>
    <div class="col-md-6 text-right">
        {{ $dryingJobOrders->links() }}
    </div>
</div>
@endif
