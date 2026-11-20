{{--
    Payment Request Approval list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $paymentRequests ke loop me include karta hai:
        @include(..., ['request' => $request])
    Styling: mobile-ui.css section 6 (tkc-*); ⋯ sheet: public/management/assets/js/mobile-card-sheet.js
--}}
@php
    $prd = $request->paymentRequestData;
    $po = $prd?->purchaseOrder ?? ($request->purchaseOrder ?? null);

    if ($request->payment_type !== null) {
        $ticketNo = $po->purchase_order_no ?? ($request->grn->grn_number ?? 'N/A');
        $contractNo = null;
    } else {
        if ($request->is_advance_payment == 0) {
            $ticketNo = $prd->purchaseTicket->unique_no ?? ($prd->arrivalTicket->unique_no ?? 'N/A');
            $contractNo = $prd->purchaseTicket->purchaseOrder->contract_no ?? ($prd->arrivalTicket->purchaseOrder->contract_no ?? null);
        } else {
            $ticketNo = null;
            $contractNo = $prd->purchaseOrder->contract_no ?? 'N/A';
        }
    }

    $supplierName = $prd->supplier_name ?? ($request->purchaseOrder->purchaseOrderData[0]->supplier->name ?? 'N/A');
    $truckNo = $prd->arrivalTicket->truck_no ?? ($prd->truck_no ?? ($request->freight_data->truck_no ?? 'N/A'));
    $biltyNo = $prd->arrivalTicket->bilty_no ?? ($prd->bilty_no ?? ($request->freight_data->bilty_no ?? 'N/A'));

    $status = $request->status;
    $statusClass = match ($status) {
        'approved' => 'is-approved',
        'rejected' => 'is-rejected',
        default => 'is-pending',
    };

    $isApproved = ($status === 'approved');
    $amountDisplay = !$prd?->is_paid_by_supplier ? number_format($request->amount, 2) : 'Paid by supplier';

    $manageUrl = route($request->payment_type !== null ? 'raw-material.purchase-order-payment-request-approval.edit' : ($request->is_advance_payment == 1 ? 'raw-material.advance-payment-request-approval.edit' : 'raw-material.payment-request-approval.edit'), $request->id);
    $auditUrl = route('raw-material.payment-request-approval.audit-logs', $request->id);
    $rowLogTitle = $prd->purchaseTicket->unique_no ?? ($prd->arrivalTicket->unique_no ?? ($prd->purchaseOrder->contract_no ?? $request->id));

    $primaryNo = $ticketNo ?: ('Contract #' . $contractNo);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Manage / View --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $manageUrl }}','Manage Payment Request'{{ $isApproved ? ', true' : ', false' }}, '80%')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $primaryNo }}</div>
            <span class="tkc-qc {{ $statusClass }}">{{ ucfirst($status) }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ $request->created_at->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-tags">
            @if ($contractNo && $ticketNo)
                <span class="tkc-tag">Contract #{{ $contractNo }}</span>
            @endif
            @if ($request->payment_type !== null)
                <span class="tkc-tag {{ $request->payment_type == 'advance' ? 'is-inner' : 'is-approved' }}">
                    {{ ucfirst($request->payment_type) }}
                </span>
            @else
                <span class="tkc-tag is-inner">
                    {{ $request->module_type == 'purchase_order' ? 'Contract' : 'Ticket' }}: {{ formatEnumValue($request->request_type) }}
                </span>
                @if (($prd->purchaseOrder->purchase_type ?? null) == 'gate_buying')
                    <span class="tkc-tag is-danger">Gate Buying</span>
                @endif
                @if ($request->is_advance_payment !== 0)
                    <span class="tkc-tag is-warn">Advance Payment</span>
                @endif
                @if ($request->module_type == 'freight_payment')
                    <span class="tkc-tag is-warn">Arrival Freight</span>
                @endif
            @endif
            <span class="tkc-tag is-inner">
                Amount: {{ $amountDisplay }}
            </span>
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $supplierName }}</span>
            </div>
            <div>
                <span class="tkc-label">Sauda Type</span>
                <span class="tkc-val">{{ $request->sauda_type ?: 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Amount</span>
                <span class="tkc-val">{{ $amountDisplay }}</span>
            </div>
            <div>
                <span class="tkc-label">Status</span>
                <span class="tkc-val">{{ ucfirst($status) }}</span>
            </div>
        </div>

        @if ($truckNo !== 'N/A' || $biltyNo !== 'N/A')
            <div class="tkc-chips">
                @if ($truckNo !== 'N/A')
                    <span class="tkc-chip"><i class="ft-truck"></i>{{ $truckNo }}</span>
                @endif
                @if ($biltyNo !== 'N/A')
                    <span class="tkc-chip"><i class="ft-file-text"></i>{{ $biltyNo }}</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Actions bar --}}
    <div class="tkc-actions">
        @canAccess('p-r-m-payment-request-approvals-action')
            <button type="button" class="tkc-btn"
                onclick="openModal(this,'{{ $manageUrl }}','Manage Payment Request'{{ $isApproved ? ', true' : ', false' }}, '80%')"
                title="Manage Payment Request" aria-label="Manage Payment Request">
                <i class="ft-edit"></i>
            </button>
        @endcanAccess
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $manageUrl }}','Manage Payment Request', true, '80%')"
            title="View Details" aria-label="View Details">
            <i class="ft-eye"></i>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
            data-title="#{{ $primaryNo }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ $auditUrl }}','Audit Logs: #{{ $rowLogTitle }}', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
    </template>
</div>
