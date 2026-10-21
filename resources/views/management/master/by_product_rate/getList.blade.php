<table class="table m-0">
    <thead>
        <tr>
            <th>Commodity</th>
            <th>Location & Plant</th>
            <th>Title / Reference</th>
            <th>By-Products Configured</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($by_product_rates) != 0)
            @foreach ($by_product_rates as $key => $row)
                <tr>
                    <td>
                        <strong class="text-primary">{{ $row->product->name ?? 'N/A' }}</strong>
                        @if($row->product)
                            <small class="badge badge-light border d-block mt-1" style="width: fit-content;">
                                {{ strtoupper($row->product->product_category_flags ?? 'head') }}
                            </small>
                        @endif
                    </td>
                    <td>
                        <div><strong class="text-dark">{{ $row->location->name ?? 'N/A' }}</strong></div>
                        @if($row->plant)
                            <small class="text-muted d-block">Plant: {{ $row->plant->name }}</small>
                        @endif
                        @if($row->subLocation)
                            <small class="text-muted d-block">Sub-Loc: {{ $row->subLocation->name }}</small>
                        @endif
                    </td>
                    <td>
                        <strong class="text-dark">{{ $row->title }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-info px-2 py-1 font-small-2">
                            {{ $row->items->count() }} By-Products
                        </span>
                        <div class="small text-muted mt-1" style="max-width: 280px;">
                            @foreach($row->items->take(3) as $it)
                                <span class="d-inline-block mr-1">
                                    {{ $it->product->name ?? 'Item' }}: <strong>Rs. {{ number_format($it->rate, 2) }}</strong>;
                                </span>
                            @endforeach
                            @if($row->items->count() > 3)
                                <em>+{{ $row->items->count() - 3 }} more</em>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if ($row->status == 1)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <button onclick="openModal(this,'{{ route('by-product-rate.show', $row->id) }}','View Product Rate')"
                            type="button" class="btn btn-sm btn-info" title="View Rate Details">
                            <i class="ft-eye"></i>
                        </button>
                        <a href="{{ route('by-product-rate.edit', $row->id) }}"
                            class="btn btn-sm btn-warning" title="Edit Rate">
                            <i class="ft-edit"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    No Product Rates found.
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $by_product_rates->links() }}
    </div>
</div>
