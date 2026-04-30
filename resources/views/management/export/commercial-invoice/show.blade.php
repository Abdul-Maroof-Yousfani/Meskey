<style>
    .ci-show-actions {
        margin-bottom: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ci-show-actions .ci-meta-badge {
        background: #f0f4ff;
        border: 1px solid #cdd9f0;
        border-radius: 8px;
        padding: 4px 14px;
        font-size: 12px;
        color: #2d4580;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-right: auto;
    }

    @media print {
        body * { visibility: hidden; }
        #commercialInvoicePreviewContainer,
        #commercialInvoicePreviewContainer * { visibility: visible; }
        #commercialInvoicePreviewContainer {
            position: absolute;
            left: 0; top: 0;
            width: 100%;
        }
        .ci-show-actions { display: none !important; }
    }
</style>

<div class="ci-show-actions">
    <div class="ci-meta-badge">
        <i class="ft-file-text"></i>
        {{ $commercialInvoice->commercial_invoice_no ?? ($commercialInvoice->invoice_no ?? 'N/A') }}
        &nbsp;&mdash;&nbsp;
        {{ $commercialInvoice->invoice_date ? \Carbon\Carbon::parse($commercialInvoice->invoice_date)->format('d M Y') : 'N/A' }}
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
        <i class="ft-printer"></i> Print
    </button>
    <a type="button" class="btn btn-danger btn-sm modal-sidebar-close position-relative top-1 closebutton">
        <i class="ft-x"></i> Close
    </a>
</div>

<div id="commercialInvoicePreviewContainer">
    @include('management.export.commercial-invoice.preview', [
        'preview'      => $preview,
        'goodsSummary' => $goodsSummary,
    ])
</div>

<div class="row mt-5">
    <div class="col-12">
        <x-approval-status :model="$commercialInvoice" />
    </div>
</div>

<script>
    $(document).ready(function() {
        @if (request()->get('print'))
            setTimeout(function() { window.print(); }, 800);
        @endif
    });
</script>
