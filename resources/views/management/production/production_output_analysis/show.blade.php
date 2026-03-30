<div class="row">
    <!-- Header Information -->
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Output Analysis Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date:</label>
                    <p class="form-control-static">{{ $item->analysis_date->format('d-m-Y') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Job Order(s):</label>
                    <p class="form-control-static">
                        @foreach($item->jobOrders as $jobOrder)
                            <span class="badge badge-primary">{{ $jobOrder->job_order_no }}</span>
                        @endforeach
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Brand:</label>
                    <p class="form-control-static">{{ $item->brand->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Packing:</label>
                    <p class="form-control-static">{{ $item->bagPacking->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Location:</label>
                    <p class="form-control-static">{{ $item->location->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Variety:</label>
                    <p class="form-control-static">{{ $item->variety }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Crop Year:</label>
                    <p class="form-control-static">{{ $item->cropYear->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Extra Fields for Output Analysis -->
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Milling Degree:</label>
                    <p class="form-control-static text-capitalize">{{ $item->milling_degree ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Stitching:</label>
                    <p class="form-control-static text-capitalize">{{ $item->inner_stitching ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Outer Stitching:</label>
                    <p class="form-control-static text-capitalize">{{ $item->outer_stitching ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items Section -->
    <div class="col-md-12 mt-4">
        <h6 class="header-heading-sepration">Line Items</h6>
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered table-striped" style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Time</th>
                        @foreach($productSlabTypes as $productSlabType)
                            <th style="min-width: 250px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedData as $time => $dataRows)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($time)->format('h:i A') }}</td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td>
                                    {{ $dataRows->where('slab_type_id', $productSlabType->id)->first()->production_analysis_value ?? '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Remarks Section -->
    <div class="col-md-12 mt-4">
        <div class="form-group">
            <label>Remarks:</label>
            <p class="form-control-static text-justify">{{ $item->remarks ?? 'No remarks provided.' }}</p>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-4">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
    </div>
</div>
