<!-- Action Bar (Hidden on Print) -->
<div class="d-flex justify-content-end mb-2 d-print-none bg-light p-1 rounded">
    <div>
        <button onclick="printDiv('print-area')" class="btn btn-primary btn-sm">
            <i class="ft-printer mr-1"></i> Print
        </button>
        <button type="button" class="btn btn-danger btn-sm ml-1" data-close="model">
            <i class="ft-x"></i>
        </button>
    </div>
</div>
<br>
<div class="print-ready-section" id="print-area">
    <div class="rela">
        <div class="header mb-4">
            <div class="row" style="width: 100% !important; align-items: center !important;">
                <div class="col-6">
                    <div class="logo">
                        <img class="logo-img" alt="Logo" src="/storage/company_logos/meskay-logo-20250514082251.png" style="max-height: 60px;">
                    </div>
                </div>
                <div class="col-6 text-right">
                    <h3 class="font-weight-bold text-uppercase mb-0">JOB ORDER REQUEST</h3>
                    <p class="text-muted mb-0">Status: <strong class="text-uppercase">{{ $request->status }}</strong></p>
                </div>
            </div>
        </div>

        <div class="job-order-print-container p-0 bg-white">
            <div class="row mb-3">
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr class="bg-light">
                                <th colspan="2" class="text-center font-weight-bold">BASIC INFORMATION</th>
                            </tr>
                            <tr>
                                <th width="35%">Request No:</th>
                                <td><strong>{{ $request->request_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Request Date:</th>
                                <td>{{ \Carbon\Carbon::parse($request->request_date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>{{ $request->companyLocation->name ?? 'N/A' }}</td>
                            </tr>
                            @if($request->ref_no)
                            <tr>
                                <th>Ref No:</th>
                                <td>{{ $request->ref_no }}</td>
                            </tr>
                            @endif
                            @if($request->exportOrder)
                            <tr>
                                <th>Export Order:</th>
                                <td>{{ $request->exportOrder->voucher_no }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr class="bg-light">
                                <th colspan="2" class="text-center font-weight-bold">PRODUCT DETAILS</th>
                            </tr>
                            <tr>
                                <th width="35%">Product:</th>
                                <td><strong>{{ $request->product->name ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Crop Year:</th>
                                <td>{{ $request->cropYear->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Total Bags:</th>
                                <td>{{ number_format($request->total_bags) }}</td>
                            </tr>
                            <tr>
                                <th>Total Quantity:</th>
                                <td>{{ number_format($request->total_kgs, 2) }} KG ({{ number_format($request->total_metric_tons, 3) }} MT)</td>
                            </tr>
                            <tr>
                                <th>Containers:</th>
                                <td>{{ $request->total_containers }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Specifications -->
            @if($request->specifications->count() > 0)
            <div class="mb-3">
                <h6 class="font-weight-bold bg-light p-1 border">SPECIFICATIONS</h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="bg-light">
                            <th>Specification</th>
                            <th>Value</th>
                            <th>UOM</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($request->specifications as $spec)
                        <tr>
                            <td>{{ $spec->spec_name }}</td>
                            <td>{{ $spec->spec_value }}</td>
                            <td>{{ $spec->uom ?? 'N/A' }}</td>
                            <td class="text-uppercase">{{ $spec->value_type }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- Packing Items -->
            <div class="mb-3">
                <h6 class="font-weight-bold bg-light p-1 border">PACKING DETAILS</h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="bg-light">
                            <th>Brand</th>
                            <th>Bag Product</th>
                            <th>Condition</th>
                            <th>Size (kg)</th>
                            <th>No. of Bags</th>
                            <th>Extra</th>
                            <th>Total Bags</th>
                            <th>Total MT</th>
                            <th>Containers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($request->packingItems as $pi)
                        <tr>
                            <td>{{ $pi->brand->name ?? 'N/A' }}</td>
                            <td>{{ $pi->bagProduct->name ?? 'N/A' }}</td>
                            <td>{{ $pi->bagCondition->name ?? 'N/A' }}</td>
                            <td>{{ $pi->bag_size }}</td>
                            <td>{{ number_format($pi->no_of_bags) }}</td>
                            <td>{{ $pi->extra_bags }}</td>
                            <td><strong>{{ number_format($pi->total_bags) }}</strong></td>
                            <td>{{ number_format($pi->metric_tons, 3) }}</td>
                            <td>{{ $pi->no_of_containers }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Remarks & Description -->
            @if($request->remarks || $request->order_description || $request->other_specifications)
            <div class="row">
                @if($request->remarks)
                <div class="col-6 mb-2">
                    <div class="border p-2">
                        <strong>Remarks:</strong>
                        <p class="mb-0">{{ $request->remarks }}</p>
                    </div>
                </div>
                @endif
                @if($request->order_description)
                <div class="col-6 mb-2">
                    <div class="border p-2">
                        <strong>Order Description:</strong>
                        <p class="mb-0">{{ $request->order_description }}</p>
                    </div>
                </div>
                @endif
                @if($request->other_specifications)
                <div class="col-12 mb-2">
                    <div class="border p-2">
                        <strong>Other Specifications:</strong>
                        <p class="mb-0">{{ $request->other_specifications }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
