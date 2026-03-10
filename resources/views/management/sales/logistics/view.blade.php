<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">Document Information</h6>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Loading Request (Sale Order)</label>
                    <input type="text" class="form-control" value="{{ $logistics->loading_request }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="text" class="form-control" value="{{ $logistics->date }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">SO #</label>
                    <input type="text" class="form-control" value="{{ $logistics->so_no }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Sales Order Qty (kg)</label>
                    <input type="text" class="form-control" value="{{ number_format($logistics->so_qty, 2) }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Commodity</label>
                    <input type="text" class="form-control" value="{{ $logistics->commodity }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Sauda Type</label>
                    <input type="text" class="form-control" value="{{ ucfirst($logistics->sauda_type) }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Delivery Address</label>
                    <input type="text" class="form-control" value="{{ $logistics->delivery_address }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" value="{{ $logistics->location }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Factory</label>
                    <input type="text" class="form-control" value="{{ $logistics->factory }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Section</label>
                    <input type="text" class="form-control" value="{{ $logistics->section }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" value="{{ $logistics->customer }}" readonly>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h6 class="header-heading-sepration">Logistics Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Rate Type</th>
                                <th>Rate</th>
                                <th>Transporter</th>
                                <th>Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logistics->items as $item)
                                <tr>
                                    <td>{{ $item->rate_type }}</td>
                                    <td>{{ number_format($item->rate, 2) }}</td>
                                    <td>{{ $item->transporter }}</td>
                                    <td>{{ number_format($item->qty, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<x-approval-status :model="$logistics" />

<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
