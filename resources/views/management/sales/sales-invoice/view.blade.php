<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<div class="row form-mar">
    <div class="col-md-12">
        <!-- Row 1: Customer, Invoice Address, SI No -->
        <div class="row" style="margin-top: 10px">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Customer:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->customer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Invoice Address:</label>
                    <textarea class="form-control" rows="1" readonly>{{ $sales_invoice->invoice_address ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">SI No:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->si_no }}" readonly>
                </div>
            </div>
        </div>

        <!-- Row 2: Company Location, Arrival Location, Date -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Company Location:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->location->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Arrival Location:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->arrival_location->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Invoice Date:</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($sales_invoice->invoice_date)->format('d M Y') }}" readonly>
                </div>
            </div>
        </div>

        <!-- Row 3: Reference Number, Sauda Type, DO Numbers -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Reference Number:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->reference_number ?? '' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Sauda Type:</label>
                    <input type="text" class="form-control" value="{{ ucfirst($sales_invoice->sauda_type) }}" readonly>
                </div>
            </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DC Numbers:</label>
                        <input type="text" class="form-control" value="{{ $sales_invoice->delivery_challans->pluck('dc_no')->implode(', ') ?: 'N/A' }}" readonly>
                    </div>
                </div>
        </div>

        <!-- Row 4: Remarks, Status -->
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label class="form-label">Remarks:</label>
                    <textarea class="form-control" rows="2" readonly>{{ $sales_invoice->remarks ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Status:</label>
                    @php
                        $status = $sales_invoice->am_approval_status;
                        $badge = match(strtolower($status)) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending'  => 'badge-warning',
                            default    => 'badge-secondary',
                        };
                    @endphp
                    <div class="form-control" style="background-color: #e9ecef;">
                        <span class="badge {{ $badge }} px-3 py-2">
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
            <table class="table table-bordered" id="salesInvoiceTable" style="min-width:2200px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Packing</th>
                        <th>No of Bags</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Gross Amount</th>
                        <th>Discount %</th>
                        <th>Discount Amount</th>
                        <th>Amount</th>
                        <th>GST %</th>
                        <th>GST Amount</th>
                        <th>Net Amount</th>
                        <th>Line Desc</th>
                        <th>Truck No</th>
                    </tr>
                </thead>
                <tbody id="siTableBody">
                    @forelse($sales_invoice->sales_invoice_data as $index => $data)
                    <tr id="row_{{ $index }}">
                        <td style="min-width: 200px;">
                            <input type="text" class="form-control" value="{{ $data->item->name ?? 'N/A' }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->packing }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->no_of_bags }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->qty }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->rate }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="number" class="form-control" value="{{ $data->gross_amount }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->discount_percent }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="number" class="form-control" value="{{ $data->discount_amount }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="number" class="form-control" value="{{ $data->amount }}" readonly>
                        </td>
                        <td style="min-width: 100px;">
                            <input type="number" class="form-control" value="{{ $data->gst_percent }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="number" class="form-control" value="{{ $data->gst_amount }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="number" class="form-control" value="{{ $data->net_amount }}" readonly>
                        </td>
                        <td style="min-width: 150px;">
                            <input type="text" class="form-control" value="{{ $data->line_desc ?? '' }}" readonly>
                        </td>
                        <td style="min-width: 120px;">
                            <input type="text" class="form-control" value="{{ $data->truck_no ?? '' }}" readonly>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="text-center">No items found</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($sales_invoice->sales_invoice_data->count() > 0)
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="3" class="text-right">Totals:</th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('qty') }}" readonly>
                        </th>
                        <th></th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('gross_amount') }}" readonly>
                        </th>
                        <th></th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('discount_amount') }}" readonly>
                        </th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('amount') }}" readonly>
                        </th>
                        <th></th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('gst_amount') }}" readonly>
                        </th>
                        <th>
                            <input type="number" class="form-control" value="{{ $sales_invoice->sales_invoice_data->sum('net_amount') }}" readonly>
                        </th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
<x-approval-status :model="$sales_invoice" />
<div class="row bottom-button-bar">
    <div class="col-12 text-end">
        <a type="button"
            class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>
