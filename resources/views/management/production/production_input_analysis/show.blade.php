<div class="row">
    <!-- Header Information -->
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Input Analysis Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date:</label>
                    <p class="form-control-static">{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Company Location:</label>
                    <p class="form-control-static">{{ $item->location->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Location:</label>
                    <p class="form-control-static">{{ $item->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Plant:</label>
                    <p class="form-control-static">{{ $item->plant->name ?? 'N/A' }}</p>
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
                        <th style="min-width: 150px;">Unit</th>
                        @foreach($productSlabTypes as $productSlabType)
                            <th style="min-width: 150px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->items as $analysisItem)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($analysisItem->analysis_time)->format('h:i A') }}</td>
                            <td>{{ $analysisItem->unit->name ?? '-' }}</td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td>
                                    {{ $analysisItem->slabs->where('slab_type_id', $productSlabType->id)->first()->production_analysis_value ?? '-' }}
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
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>
