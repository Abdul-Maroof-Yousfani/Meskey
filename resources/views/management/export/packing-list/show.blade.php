<style>
    .pl-show-actions {
        margin-bottom: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pl-show-actions .pl-meta-badge {
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
        #packingListPreviewContainer,
        #packingListPreviewContainer * { visibility: visible; }
        #packingListPreviewContainer {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .pl-show-actions { display: none !important; }
    }
</style>

<div class="pl-show-actions">
    <div class="pl-meta-badge">
        <i class="ft-file-text"></i>
        {{ $preview['commercial_invoice_no'] ?? 'N/A' }}
        &nbsp;&mdash;&nbsp;
        {{ !empty($preview['commercial_invoice_date']) ? \Carbon\Carbon::parse($preview['commercial_invoice_date'])->format('d M Y') : 'N/A' }}
    </div>
    @if($packingList->am_approval_status === 'approved')
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
        <i class="ft-printer"></i> Print
    </button>
    @endif
    <a type="button" class="btn btn-danger btn-sm modal-sidebar-close position-relative top-1 closebutton">
        <i class="ft-x"></i> Close
    </a>
</div>

<div id="packingListPreviewContainer">
    @include('management.export.packing-list.preview', [
        'preview' => $preview,
        'goodsSummary' => $goodsSummary,
    ])
</div>

<div class="row mt-5">
    <div class="col-12">
        <x-approval-status :model="$packingList" />
    </div>
</div>

<script>
    $(document).ready(function() {
        @if (request()->get('print'))
            setTimeout(function() {
                window.print();
            }, 800);
        @endif
    });
</script>
