<style>
    html, body {
        overflow-x: hidden;
    }
    #rejectionReturnTable .select2-container {
        width: 100% !important;
    }
    input:disabled, textarea:disabled, select:disabled {
        background-color: white !important;
        color: #495057 !important;
        cursor: not-allowed;
    }
</style>

<div class="row">
    <input type="hidden" id="listRefresh" value="{{ route('store.rejection-return.getList') }}" />
    <div class="col-12">
        <div class="row form-mar">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Selected GRN:</label>
                    <select class="form-control select2" disabled>
                        <option selected>{{ $rejectionReturn->grn->purchase_order_receiving_no ?? 'N/A' }} - {{ $rejectionReturn->supplier->name ?? '' }}</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Return Date:</label>
                    <input type="date" class="form-control" value="{{ $rejectionReturn->date }}" disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Reference No:</label>
                    <input type="text" class="form-control" value="{{ $rejectionReturn->reference_no }}" disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Truck No:</label>
                    <input type="text" class="form-control" value="{{ $rejectionReturn->truck_no }}" disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Created By:</label>
                    <input type="text" class="form-control" value="{{ $rejectionReturn->creator->name ?? 'N/A' }}" disabled>
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
                <div class="form-group">
                    <label class="form-label">Remarks:</label>
                    <textarea class="form-control" rows="2" disabled>{{ $rejectionReturn->remarks }}</textarea>
                </div>
            </div>
        </div>

        <div class="row form-mar" id="itemSection">
            <div class="col-md-12 mt-3">
                <div style="overflow-x: auto; width: 100%;">
                    <table class="table table-bordered text-left" id="rejectionReturnTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="min-width: 400px;" class="text-left">Item</th>
                                <th style="min-width: 200px;" class="text-left">Rejected Qty</th>
                                <th style="min-width: 200px;" class="text-left">Weight (grams)</th>
                            </tr>
                        </thead>
                        <tbody id="rejectionReturnBody">
                            @foreach($rejectionReturn->items as $item)
                            <tr>
                                <td class="text-left">
                                    <select class="form-control select2" disabled>
                                        <option selected>{{ $item->item->name ?? 'N/A' }}</option>
                                    </select>
                                </td>
                                <td class="text-left">
                                    <input type="text" class="form-control text-left" value="{{ $item->quantity }} {{ $item->item->unitOfMeasure->name ?? '' }}" disabled>
                                </td>
                                <td class="text-left">
                                    <input type="text" class="form-control text-left" value="{{ $item->weight ?? '-' }}" disabled>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-4">
    <div class="col-12 text-end">
        <a href="{{ route('store.rejection-return.gate-out', $rejectionReturn->id) }}" target="_blank" class="btn btn-success ml-2">
            <i class="fa fa-truck"></i> Gate Out Pass
        </a>
        <button type="button" class="btn btn-danger modal-sidebar-close">Close</button>
        <button type="button" class="btn btn-info ml-2" onclick="window.print()"><i class="fa fa-print"></i> Print Voucher</button>
    </div>
</div>
