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
        {{-- SECTION 1: B2 PRODUCTS --}}
        @if($b2Items->isNotEmpty())
            <div class="card border mb-3 shadow-none">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center" style="background-color: #e8f4fd;">
                    <span class="font-weight-bold text-primary font-small-3">
                        B2 Products
                    </span>
                    <span class="badge badge-primary font-small-2">{{ $b2Items->count() }} Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th width="40%">B2 Product Name</th>
                                    <th width="15%" class="text-center">Category Flag</th>
                                    <th width="15%" class="text-center">UOM</th>
                                    <th width="25%" class="text-right">Standard Rate (PKR / KG)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($b2Items as $index => $item)
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold">{{ $index + 1 }}</td>
                                        <td class="align-middle">
                                            <strong class="text-dark">{{ $item->product->name ?? 'N/A' }}</strong>
                                            @if($item->product && $item->product->unique_no)
                                                <small class="text-muted d-block">Code: {{ $item->product->unique_no }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-info text-uppercase font-small-1">B2 Product</span>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-muted">
                                            {{ $item->product->unitOfMeasure?->name ?? 'KG' }}
                                        </td>
                                        <td class="text-right align-middle font-weight-bold text-primary font-medium-1">
                                            Rs. {{ number_format($item->rate, 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION 2: BY-PRODUCTS --}}
        @if($byProductItems->isNotEmpty())
            <div class="card border mb-2 shadow-none">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center" style="background-color: #eafbf1;">
                    <span class="font-weight-bold text-success font-small-3">
                        By-Products
                    </span>
                    <span class="badge badge-success font-small-2">{{ $byProductItems->count() }} Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th width="40%">Product Name</th>
                                    <th width="15%" class="text-center">Category Flag</th>
                                    <th width="15%" class="text-center">UOM</th>
                                    <th width="25%" class="text-right">Recovery Rate (PKR / KG)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byProductItems as $index => $item)
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold">{{ $index + 1 }}</td>
                                        <td class="align-middle">
                                            <strong class="text-dark">{{ $item->product->name ?? 'N/A' }}</strong>
                                            @if($item->product && $item->product->unique_no)
                                                <small class="text-muted d-block">Code: {{ $item->product->unique_no }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-secondary text-uppercase font-small-1">Product</span>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-muted">
                                            {{ $item->product->unitOfMeasure?->name ?? 'KG' }}
                                        </td>
                                        <td class="text-right align-middle font-weight-bold text-success font-medium-1">
                                            Rs. {{ number_format($item->rate, 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($b2Items->isEmpty() && $byProductItems->isEmpty())
            <div class="alert alert-warning mb-0 p-3">
                <i class="ft-alert-circle mr-1"></i> No rate items recorded for this schedule.
            </div>
        @endif
    </div>

    <div class="col-md-12 text-right mt-2">
        <a href="{{ route('by-product-rate.edit', $byProductRate->id) }}" class="btn btn-warning mr-1">
            <i class="ft-edit mr-1"></i> Edit Rates
        </a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
</div>
