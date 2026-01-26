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
        <!-- Row 1: GRN, Bill -->
        <div class="row" style="margin-top: 10px">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">GRN:</label>
                    <input type="text" class="form-control" value="{{ $debitNote->grn->purchase_order_receiving_no ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Bill:</label>
                    <input type="text" class="form-control" value="{{ $debitNote->bill->bill_no ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row form-mar">
    <div class="col-md-12">
        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
            <table class="table table-bordered" style="min-width:1200px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>GRN Qty</th>
                        <th>Debit Note Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($debitNote->debit_note_data as $item)
                        <tr>
                            <td>
                                {{ $item->item->name ?? 'N/A' }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->grn_qty ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->debit_note_quantity ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->rate ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->amount ?? 0, 2) }}
                            </td>
                            <td>
                                @php
                                    $badgeClass = match (strtolower($item->am_approval_status ?? 'pending')) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'pending' => 'badge-warning',
                                        'reverted' => 'badge-info',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucwords($item->am_approval_status ?? 'pending') }}
                                </span>
                            </td>
                            <td>
                                {{ $debitNote->created_at->format('d-m-Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No items found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-approval-status :model="$debitNote" />
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
















