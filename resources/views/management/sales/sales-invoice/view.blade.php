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
        <!-- Row 1: Customer, DC Numbers, Sauda Type -->
        <div class="row" style="margin-top: 10px">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Customer:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->customer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">DC Numbers:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->delivery_challans->pluck('dc_no')->implode(', ') ?: 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Sauda Type:</label>
                    <input type="text" class="form-control" value="{{ ucfirst($sales_invoice->sauda_type) }}" readonly>
                </div>
            </div>
        </div>

        <!-- Row 2: SI No, Invoice Address, Invoice Date -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">SI No:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->si_no }}" readonly>
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
                    <label class="form-label">Invoice Date:</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($sales_invoice->invoice_date)->format('d M Y') }}" readonly>
                </div>
            </div>
        </div>

        <!-- Row 3: Company Location, Factory, Reference Number -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Company Location:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->location->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Factory:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->arrival_location->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Reference Number:</label>
                    <input type="text" class="form-control" value="{{ $sales_invoice->reference_number ?? '' }}" readonly>
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
                    <input type="text" class="form-control" value="{{ ucfirst($sales_invoice->am_approval_status) }}" readonly>
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
                            <span style="font-size: 14px;;">Used Quantity:
                                    {{ sales_invoice_bags_used($data->dc_data_id) }}</span>
                                <br />
                                <span style="font-size: 14px;">Balance:
                                    {{ sales_invoice_balance($data->dc_data_id) }}</span>
                                
                        </td>
                        <td style="min-width: 100px;">
                            @php
                                $qty = $data->qty;
                                if (($qty <= 0 || $qty == null) && $data->no_of_bags > 0 && $data->packing > 0) {
                                    $qty = $data->no_of_bags * $data->packing;
                                }
                            @endphp
                            <input type="number" class="form-control" value="{{ round($qty) }}" readonly>
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
                            <input type="number" class="form-control" value="{{ round($sales_invoice->sales_invoice_data->sum('qty'))  }}" readonly>
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

@php
    $siModule = $sales_invoice->getApprovalModule();
    $siApprovalLogs = $siModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $sales_invoice->id)->where('module_id', $siModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper">
    <x-approval-status :model="$sales_invoice" :list-refresh="route('sales.get.sales-invoice.list')" />
</div>

<style>
    .approval-view-wrapper .alert-primary,
    .approval-view-wrapper .alert-warning {
        display: none !important;
    }
    .current-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #047857;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }
    .current-status-dot {
        width: 5px;
        height: 5px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
</style>

@if ($siApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $siApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $siApprovalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 160px; width: 22%;">User</th>
                                <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                <th style="min-width: 300px; width: 40%;">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($siApprovalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        'partial_approved' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                        @if ($log->user_id === auth()->id())
                                            <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                        @if ($loop->first)
                                            <div class="mt-1">
                                                <span class="current-status-tag">
                                                    <span class="current-status-dot"></span> Current
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty(trim($log->comments ?? '')))
                                            <span class="text-dark">{{ $log->comments }}</span>
                                        @else
                                            <span class="text-muted fst-italic">No comments</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="height: 60px; width: 100%; clear: both;"></div>
    </div>
@endif

<div class="row bottom-button-bar">
    <div class="col-12 text-end">
        <a type="button"
            class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>
