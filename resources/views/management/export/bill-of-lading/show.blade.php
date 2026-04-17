<style>
    .bol-show-actions {
        margin-bottom: 16px;
        text-align: right;
    }

    .bol-preview {
        background: #fff;
        color: #111;
        border: 1px solid #d7dbe0;
        border-radius: 10px;
        padding: 24px;
    }

    .bol-preview .bol-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        border-bottom: 1px solid #bbb;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }

    .bol-preview .bol-title {
        text-align: center;
        flex: 1;
    }

    .bol-preview .bol-title h3 {
        margin: 0;
        font-size: 22px;
        letter-spacing: 1px;
    }

    .bol-box {
        border: 1px solid #bbb;
        min-height: 120px;
        padding: 12px;
        margin-bottom: 14px;
    }

    .bol-box h6 {
        font-size: 12px;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    .bol-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #666;
        letter-spacing: .5px;
        margin-bottom: 4px;
    }

    .bol-value {
        white-space: pre-line;
        font-size: 13px;
        line-height: 1.45;
    }

    .bol-table th,
    .bol-table td {
        vertical-align: top;
        font-size: 12px;
    }

    .bol-muted {
        color: #666;
        font-size: 12px;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #bolPreviewContainer,
        #bolPreviewContainer * {
            visibility: visible;
        }

        #bolPreviewContainer {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>

<div class="bol-show-actions">
    <button type="button" class="btn btn-secondary me-2" onclick="window.print()">Print</button>
    <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
</div>

<div id="bolPreviewContainer">
    @include('management.export.bill-of-lading.preview', [
        'preview' => $preview,
        'goodsSummary' => $goodsSummary,
    ])
</div>

<script>
    $(document).ready(function() {
        @if(request()->get('print'))
            setTimeout(function() {
                window.print();
            }, 1000);
        @endif
    });
</script>
