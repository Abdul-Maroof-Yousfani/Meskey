<div class="row">
    <div class="col-md-8 mb-2">
        <label class="text-muted font-small-3 d-block">Main Commodity / Product</label>
        <h4 class="font-weight-bold text-primary mb-0">{{ $byProductRate->product->name ?? 'N/A' }}</h4>
        <small class="badge badge-secondary">{{ strtoupper($byProductRate->product->product_category_flags ?? 'head') }}</small>
    </div>

    <div class="col-md-4 mb-2 text-md-right">
        <label class="text-muted font-small-3 d-block">Status</label>
        @if($byProductRate->status == 1)
            <span class="badge badge-success font-medium-1 px-2 py-1">Active</span>
        @else
            <span class="badge badge-danger font-medium-1 px-2 py-1">Inactive</span>
        @endif
    </div>

    <div class="col-md-12"><hr class="my-1"></div>

    <div class="col-md-4 mb-2">
        <label class="text-muted font-small-2 d-block">Location</label>
        <strong class="text-dark font-medium-1">{{ $byProductRate->location->name ?? 'N/A' }}</strong>
    </div>

    <div class="col-md-4 mb-2">
        <label class="text-muted font-small-2 d-block">Sub Location</label>
        <strong class="text-dark">{{ $byProductRate->subLocation->name ?? '-' }}</strong>
    </div>

    <div class="col-md-4 mb-2">
        <label class="text-muted font-small-2 d-block">Plant</label>
        <strong class="text-dark">{{ $byProductRate->plant->name ?? '-' }}</strong>
    </div>

    <div class="col-md-12 mb-2">
        <label class="text-muted font-small-2 d-block">Title / Reference</label>
        <div class="font-weight-bold text-dark">{{ $byProductRate->title }}</div>
    </div>

    @if($byProductRate->description)
    <div class="col-md-12 mb-2">
        <label class="text-muted font-small-2 d-block">Description / Remarks</label>
        <div class="p-2 bg-light rounded text-muted">{{ $byProductRate->description }}</div>
    </div>
    @endif

    <div class="col-md-12 mt-2">
        <h5 class="font-weight-bold text-dark mb-2">
            <i class="ft-layers mr-1 text-primary"></i> By-Product Standard Rates
        </h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th width="5%">#</th>
                        <th>By-Product</th>
                        <th width="15%" class="text-center">Sort Type</th>
                        <th width="15%" class="text-center">UOM</th>
                        <th width="25%" class="text-right">Rate (PKR / KG)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byProductRate->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong class="text-dark">{{ $item->product->name ?? 'N/A' }}</strong>
                                @if($item->product && $item->product->unique_no)
                                    <small class="text-muted d-block">Code: {{ $item->product->unique_no }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary text-uppercase">{{ $item->product->product_category_flags ?? 'by' }}</span>
                            </td>
                            <td class="text-center">
                                {{ $item->product->unitOfMeasure?->name ?? 'KG' }}
                            </td>
                            <td class="text-right font-weight-bold text-primary font-medium-1">
                                Rs. {{ number_format($item->rate, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No rate items recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12 text-right mt-2">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
</div>
