{{-- Duplicate Rates Alert with Direct Edit Button --}}
@if(isset($existingRateSchedule) && $existingRateSchedule)
    <div class="alert alert-warning border-warning shadow-sm mb-3 p-3 rounded" style="background-color: #fff9e6; border-left: 5px solid #ff9800 !important;">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h6 class="font-weight-bold text-dark mb-1">
                    <i class="ft-alert-triangle text-warning font-medium-3 mr-1"></i>
                    Rates Already Created for this Product & Location!
                </h6>
                <p class="mb-0 text-dark" style="font-size: 13px;">
                    This Product (<strong>{{ $existingRateSchedule->product?->name }}</strong>) rates on location (<strong>{{ $existingRateSchedule->location?->name }}</strong>) already created. Please edit them.
                </p>
            </div>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('by-product-rate.edit', $existingRateSchedule->id) }}" 
                    class="btn btn-warning text-dark font-weight-bold shadow-sm">
                    <i class="ft-edit mr-1"></i> Edit Existing Rates
                </a>
            </div>
        </div>
    </div>
    <script>
        $('#saveBtn').prop('disabled', true).addClass('disabled').attr('title', 'Rates already exist for this product and location');
    </script>
@else
    <script>
        $('#saveBtn').prop('disabled', false).removeClass('disabled').removeAttr('title');
    </script>
@endif

@if($b2Products->isEmpty() && $byProducts->isEmpty())
    <div class="alert alert-warning mb-0 p-3">
        <i class="ft-alert-circle mr-1"></i> No B2 Products or Products found under this Commodity family.
    </div>
@else

    {{-- SECTION 1: B2 PRODUCTS --}}
    @if($b2Products->isNotEmpty())
        <div class="card border mb-3 shadow-none">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #e8f4fd;">
                <span class="font-weight-bold text-primary font-small-3">
                    B2 Products
                </span>
                <span class="badge badge-primary font-small-2">{{ $b2Products->count() }} Items</span>
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
                                <th width="25%">Standard Rate (PKR / KG) <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($b2Products as $index => $bp)
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong class="text-dark">{{ $bp->name }}</strong>
                                        @if($bp->unique_no)
                                            <small class="text-muted d-block">Code: {{ $bp->unique_no }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-info text-uppercase font-small-1">B2 Product</span>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-muted">
                                        {{ $bp->unitOfMeasure?->name ?? 'KG' }}
                                    </td>
                                    <td class="align-middle">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-small-2 bg-light font-weight-bold">PKR</span>
                                            </div>
                                            <input type="number" step="0.0001" min="0" name="rates[{{ $bp->id }}]"
                                                value="{{ isset($existingRates[$bp->id]) ? number_format((float)$existingRates[$bp->id], 4, '.', '') : '' }}"
                                                class="form-control form-control-sm text-right font-weight-bold text-primary"
                                                placeholder="0.0000" required />
                                        </div>
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
    @if($byProducts->isNotEmpty())
        <div class="card border mb-2 shadow-none">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #eafbf1;">
                <span class="font-weight-bold text-success font-small-3">
                    By-Products
                </span>
                <span class="badge badge-success font-small-2">{{ $byProducts->count() }} Items</span>
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
                                <th width="25%">Recovery Rate (PKR / KG) <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byProducts as $index => $bp)
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong class="text-dark">{{ $bp->name }}</strong>
                                        @if($bp->unique_no)
                                            <small class="text-muted d-block">Code: {{ $bp->unique_no }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-secondary text-uppercase font-small-1">Product</span>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-muted">
                                        {{ $bp->unitOfMeasure?->name ?? 'KG' }}
                                    </td>
                                    <td class="align-middle">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-small-2 bg-light font-weight-bold">PKR</span>
                                            </div>
                                            <input type="number" step="0.0001" min="0" name="rates[{{ $bp->id }}]"
                                                value="{{ isset($existingRates[$bp->id]) ? number_format((float)$existingRates[$bp->id], 4, '.', '') : '' }}"
                                                class="form-control form-control-sm text-right font-weight-bold text-success"
                                                placeholder="0.0000" required />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endif
