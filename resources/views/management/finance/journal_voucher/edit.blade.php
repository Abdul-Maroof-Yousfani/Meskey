@extends('management.layouts.master')
@section('title')
    Edit Journal Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Journal Voucher #{{ $journalVoucher->jv_no }}</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-info mr-2"
                                onclick="openModal(this, '{{ route('journal-voucher.audit-logs', $journalVoucher->id) }}', 'Audit Logs: Journal Voucher #{{ $journalVoucher->jv_no }}', false, '55%')">
                                <i class="fa fa-history mr-1"></i> Audit Logs
                            </button>
                            <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(strtolower($journalVoucher->am_approval_status ?? '') === 'reverted')
                            @php
                                $latestLog = $journalVoucher->approvalLogs()->latest()->first();
                            @endphp
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-undo me-2"></i>
                                <strong>Status: Reverted</strong> - This Journal Voucher has been reverted. Please make the necessary changes and save to resubmit for approval.
                                @if($latestLog && !empty(trim($latestLog->comments ?? '')))
                                    <div class="mt-1 small">
                                        <strong>Revert Comment ({{ $latestLog->user->name ?? 'Approver' }}):</strong> {{ $latestLog->comments }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form id="ajaxSubmit" action="{{ route('journal-voucher.update', $journalVoucher->id) }}">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="url" value="{{ route('journal-voucher.index') }}">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_date">Date</label>
                                        <input type="date" name="jv_date" id="jv_date" class="form-control" 
                                            value="{{ $journalVoucher->jv_date ? $journalVoucher->jv_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_no">JV Number</label>
                                        <input type="text" name="jv_no" id="jv_no" class="form-control" 
                                            value="{{ $journalVoucher->jv_no }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3">{{ $journalVoucher->description }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="mb-0">Journal Entries</label>
                                            <div class="custom-control custom-switch">
                                                @php
                                                    $isReceiving = false;
                                                    foreach($journalVoucher->journalVoucherDetails as $detail) {
                                                        if(!empty($detail->receipt_voucher_id) || !empty($detail->customer_advance_id) || !empty($detail->delivery_order_id) || !empty($detail->reference_jv_id) || (!empty($detail->sales_order_id) && empty($detail->voucher_type))) {
                                                            $isReceiving = true;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                <input type="checkbox" class="custom-control-input" id="receivingToggle" {{ $isReceiving ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="receivingToggle">Receiving</label>
                                            </div>
                                        </div>
                                        <style>
                                            /* Fixed Table Layout & Container */
                                            #journalEntriesTable {
                                                table-layout: fixed !important;
                                                width: 100% !important;
                                                margin-bottom: 0;
                                                border-collapse: collapse;
                                            }

                                            /* Fixed Min-Widths to avoid unreadable squishing on small screens */
                                            #journalEntriesTable.receiving-active {
                                                min-width: 1250px;
                                            }
                                            #journalEntriesTable:not(.receiving-active) {
                                                min-width: 1050px;
                                            }

                                            /* Column Widths - Standard Mode (Receiving OFF: 6 columns) */
                                            #journalEntriesTable:not(.receiving-active) th.col-account { width: 26% !important; }
                                            #journalEntriesTable:not(.receiving-active) th.col-order { width: 22% !important; }
                                            #journalEntriesTable:not(.receiving-active) th.col-description { width: 26% !important; }
                                            #journalEntriesTable:not(.receiving-active) th.col-debit { width: 11% !important; }
                                            #journalEntriesTable:not(.receiving-active) th.col-credit { width: 11% !important; }
                                            #journalEntriesTable:not(.receiving-active) th.col-action { width: 4% !important; }

                                            /* Column Widths - Receiving Mode (Receiving ON: 8 columns) */
                                            #journalEntriesTable.receiving-active th.col-account { width: 21% !important; }
                                            #journalEntriesTable.receiving-active th.col-adj-type { width: 12% !important; }
                                            #journalEntriesTable.receiving-active th.col-ref-so { width: 18% !important; }
                                            #journalEntriesTable.receiving-active th.col-do { width: 17% !important; }
                                            #journalEntriesTable.receiving-active th.col-description { width: 15% !important; }
                                            #journalEntriesTable.receiving-active th.col-debit { width: 8.5% !important; }
                                            #journalEntriesTable.receiving-active th.col-credit { width: 8.5% !important; }
                                            #journalEntriesTable.receiving-active th.col-action { width: 4% !important; }

                                            /* Header styling */
                                            #journalEntriesTable thead th {
                                                font-weight: 600;
                                                font-size: 11.5px;
                                                text-transform: uppercase;
                                                letter-spacing: 0.5px;
                                                background-color: #f4f5fa;
                                                color: #495057;
                                                vertical-align: middle;
                                                padding: 10px 8px;
                                                white-space: nowrap;
                                                overflow: hidden;
                                                text-overflow: ellipsis;
                                                border-bottom: 2px solid #dee2e6;
                                            }

                                            /* Body cell styling */
                                            #journalEntriesTable tbody td {
                                                vertical-align: middle !important;
                                                padding: 6px 6px !important;
                                                overflow: hidden;
                                            }

                                            /* Select2 full-width & text truncation inside fixed cells */
                                            #journalEntriesTable .select2-container {
                                                width: 100% !important;
                                                max-width: 100% !important;
                                            }

                                            #journalEntriesTable .select2-container .select2-selection--single {
                                                height: 38px !important;
                                                border: 1px solid #d9d9d9;
                                                border-radius: 4px;
                                                display: flex !important;
                                                align-items: center;
                                            }

                                            #journalEntriesTable .select2-container .select2-selection--single .select2-selection__rendered {
                                                line-height: 36px !important;
                                                padding-left: 8px !important;
                                                padding-right: 22px !important;
                                                text-overflow: ellipsis !important;
                                                overflow: hidden !important;
                                                white-space: nowrap !important;
                                                display: block !important;
                                                width: 100% !important;
                                                color: #495057;
                                                font-size: 13px;
                                            }

                                            #journalEntriesTable .select2-container .select2-selection--single .select2-selection__arrow {
                                                height: 36px !important;
                                                right: 4px !important;
                                                top: 0 !important;
                                            }

                                            /* Inputs inside fixed cells */
                                            #journalEntriesTable .form-control {
                                                height: 38px !important;
                                                font-size: 13px;
                                                padding: 6px 8px;
                                                border-radius: 4px;
                                                width: 100% !important;
                                                box-sizing: border-box;
                                            }

                                            #journalEntriesTable .debit-input,
                                            #journalEntriesTable .credit-input {
                                                text-align: right;
                                                font-weight: 600;
                                            }

                                            #journalEntriesTable td:last-child {
                                                text-align: center;
                                            }

                                            #journalEntriesTable .remove-row {
                                                width: 32px;
                                                height: 32px;
                                                padding: 0;
                                                display: inline-flex;
                                                align-items: center;
                                                justify-content: center;
                                            }

                                            /* Summary / Footer styling */
                                            #journalEntriesTable tfoot td {
                                                padding: 8px 10px !important;
                                                font-size: 13px;
                                                vertical-align: middle;
                                                background-color: #fafbfe;
                                            }

                                            #journalEntriesTable tfoot strong {
                                                font-size: 13.5px;
                                            }

                                            #journalEntriesTable tfoot td.text-right {
                                                text-align: right !important;
                                            }

                                            #journalEntriesTable tfoot #totalDebits,
                                            #journalEntriesTable tfoot #totalCredits,
                                            #journalEntriesTable tfoot #difference {
                                                display: block;
                                                text-align: right;
                                                font-weight: 700;
                                            }
                                        </style>
                                        <div class="table-responsive">
                                            <table class="table table-bordered {{ $isReceiving ? 'receiving-active' : '' }}" id="journalEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th class="col-account">Account</th>
                                                        <th class="receiving-col col-adj-type" style="{{ $isReceiving ? '' : 'display: none;' }};">Adjustment Type</th>
                                                        <th class="receiving-col col-ref-so" style="{{ $isReceiving ? '' : 'display: none;' }};">Reference / SO</th>
                                                        <th class="receiving-col col-do" style="{{ $isReceiving ? '' : 'display: none;' }};">Delivery Order</th>
                                                        <th class="order-col col-order" style="{{ $isReceiving ? 'display: none;' : '' }};">Orders</th>
                                                        <th class="col-description">Description</th>
                                                        <th class="col-debit text-right">Debit</th>
                                                        <th class="col-credit text-right">Credit</th>
                                                        <th class="col-action text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="journalEntriesBody">
                                                    @foreach ($journalVoucher->journalVoucherDetails as $index => $detail)
                                                        @php
                                                            $rowType = '';
                                                            if (!empty($detail->receipt_voucher_id)) {
                                                                $rowType = 'receipt_voucher';
                                                            } elseif (!empty($detail->customer_advance_id) || ($detail->voucher_type === 'customer_advance')) {
                                                                $rowType = 'excess_amount';
                                                            } elseif (!empty($detail->reference_jv_id) || ($detail->voucher_type === 'journal_voucher')) {
                                                                $rowType = 'journal_voucher';
                                                            } elseif (!empty($detail->sales_order_id) || !empty($detail->delivery_order_id) || ($detail->voucher_type === 'delivery_order') || ($detail->voucher_type === 'sales_order' && empty($detail->order_id))) {
                                                                $rowType = 'so_do';
                                                            }
                                                        @endphp
                                                        <tr data-initial-rvs='@json($rowRvs[$index] ?? [])'
                                                            data-initial-advances='@json($rowAdvances[$index] ?? [])'
                                                            data-initial-sos='@json($rowSos[$index] ?? [])'
                                                            data-initial-jvs='@json($rowJvs[$index] ?? [])'
                                                            data-initial-grns='@json($rowOrders[$index] ?? [])'>
                                                            <td>
                                                                <select name="details[{{ $index }}][acc_id]" class="form-control select2 account-select" required>
                                                                    <option value="">Select Account</option>
                                                                    @foreach ($accounts as $account)
                                                                        <option value="{{ $account->id }}" 
                                                                            data-table-name="{{ strtolower($account->table_name ?? '') }}"
                                                                            {{ $detail->acc_id == $account->id ? 'selected' : '' }}>
                                                                            {{ $account->name }} ({{ $account->unique_no }}){{ $account->type_label ? " - [" . $account->type_label . "]" : "" }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}">
                                                                <select class="form-control select2 adjustment-type-select" style="width: 100%;">
                                                                    <option value="">Select Type</option>
                                                                    <option value="receipt_voucher" {{ $rowType === 'receipt_voucher' ? 'selected' : '' }}>Receipt Voucher (RV)</option>
                                                                    <option value="excess_amount" {{ $rowType === 'excess_amount' ? 'selected' : '' }}>Excess Amount</option>
                                                                    <option value="so_do" {{ $rowType === 'so_do' ? 'selected' : '' }}>SO / DO</option>
                                                                    <option value="journal_voucher" {{ $rowType === 'journal_voucher' ? 'selected' : '' }}>Journal Voucher (JV)</option>
                                                                </select>
                                                            </td>
                                                            <td class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}">
                                                                <select class="form-control select2 ref-doc-select" style="width: 100%;" {{ empty($rowType) ? 'disabled' : '' }}>
                                                                    <option value="">{{ empty($rowType) ? 'Select Type First' : 'Select Reference' }}</option>
                                                                    @if($rowType === 'receipt_voucher' && isset($rowRvs[$index]))
                                                                        @foreach ($rowRvs[$index] as $rv)
                                                                            <option value="{{ $rv['id'] }}" 
                                                                                data-remaining-amount="{{ $rv['remaining_amount'] }}"
                                                                                data-unique-no="{{ $rv['unique_no'] ?? '' }}"
                                                                                @selected($detail->receipt_voucher_id == $rv['id'])>
                                                                                {{ $rv['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @elseif($rowType === 'excess_amount' && isset($rowAdvances[$index]))
                                                                        @foreach ($rowAdvances[$index] as $adv)
                                                                            @php
                                                                                $isAdvSelected = ($detail->customer_advance_id == $adv['id']) || ($detail->voucher_type === 'customer_advance' && $detail->voucher_id == $adv['id']);
                                                                            @endphp
                                                                            <option value="{{ $adv['id'] }}" 
                                                                                data-remaining-amount="{{ $adv['remaining_amount'] }}"
                                                                                data-voucher-no="{{ $adv['voucher_no'] ?? '' }}"
                                                                                @selected($isAdvSelected)>
                                                                                {{ $adv['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @elseif($rowType === 'so_do' && isset($rowSos[$index]))
                                                                        @foreach ($rowSos[$index] as $so)
                                                                            <option value="{{ $so['id'] }}" 
                                                                                @selected($detail->sales_order_id == $so['id'])>
                                                                                {{ $so['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @elseif($rowType === 'journal_voucher' && isset($rowJvs[$index]))
                                                                        @foreach ($rowJvs[$index] as $jv)
                                                                            <option value="{{ $jv['id'] }}" 
                                                                                data-remaining-amount="{{ $jv['remaining_amount'] }}"
                                                                                data-unique-no="{{ $jv['jv_no'] ?? '' }}"
                                                                                data-detail-id="{{ $jv['detail_id'] ?? '' }}"
                                                                                @selected($detail->reference_jv_id == $jv['id'])>
                                                                                {{ $jv['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                            </td>
                                                            <td class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}">
                                                                @php
                                                                    $hasDos = isset($rowDos[$index]) && count($rowDos[$index]) > 0;
                                                                    $isSoDo = ($rowType === 'so_do');
                                                                @endphp
                                                                <select class="form-control select2 do-select" style="width: 100%;" {{ (!$isSoDo || empty($detail->sales_order_id)) ? 'disabled' : '' }}>
                                                                    @if(!$isSoDo)
                                                                        <option value="">N/A</option>
                                                                    @else
                                                                        @if($hasDos)
                                                                            <option value="">Select Delivery Order</option>
                                                                            @foreach ($rowDos[$index] as $do)
                                                                                <option value="{{ $do['id'] }}"
                                                                                    data-remaining-amount="{{ $do['remaining_amount'] }}"
                                                                                    data-reference-no="{{ $do['reference_no'] ?? '' }}"
                                                                                    @selected($detail->delivery_order_id == $do['id'])>
                                                                                    {{ $do['text'] }}
                                                                                </option>
                                                                            @endforeach
                                                                            <option value="direct_so" @selected(empty($detail->delivery_order_id) && !empty($detail->sales_order_id))>
                                                                                Direct SO Advance
                                                                            </option>
                                                                        @else
                                                                            <option value="direct_so" selected>
                                                                                Direct SO Advance
                                                                            </option>
                                                                        @endif
                                                                    @endif
                                                                </select>
                                                            </td>
                                                            <td class="order-col" style="{{ $isReceiving ? 'display: none;' : '' }}">
                                                                <select name="details[{{ $index }}][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                                                    @php
                                                                        $acc = $accounts->firstWhere('id', $detail->acc_id) ?? $detail->account;
                                                                        $tableName = strtolower($acc->table_name ?? '');
                                                                        $orderCount = isset($rowOrders[$index]) ? count($rowOrders[$index]) : 0;
                                                                        if ($tableName === 'customers') {
                                                                            $orderPlaceholder = $orderCount > 0 ? 'Select Sale Order' : 'No Sale Orders Available';
                                                                        } elseif ($tableName === 'suppliers' || $tableName === 'vendors' || $tableName === 'vendor') {
                                                                            $orderPlaceholder = $orderCount > 0 ? 'Select GRN' : 'No GRNs Available';
                                                                        } elseif (!empty($detail->acc_id)) {
                                                                            $orderPlaceholder = 'No Orders Available';
                                                                        } else {
                                                                            $orderPlaceholder = 'Select Order (Select Account First)';
                                                                        }
                                                                    @endphp
                                                                    <option value="">{{ $orderPlaceholder }}</option>
                                                                    @if(isset($rowOrders[$index]))
                                                                        @foreach ($rowOrders[$index] as $order)
                                                                            @php
                                                                                $isSelected = ($detail->voucher_id && $detail->voucher_id == $order['id'])
                                                                                    || ($detail->sales_order_id && $detail->sales_order_id == $order['id'])
                                                                                    || ($detail->voucher_no && $detail->voucher_no == ($order['unique_no'] ?? ''));
                                                                            @endphp
                                                                            <option value="{{ $order['id'] }}" 
                                                                                data-unique-no="{{ $order['unique_no'] ?? ($order['reference_no'] ?? '') }}"
                                                                                data-type="{{ $order['type'] ?? '' }}"
                                                                                data-remaining-amount="{{ $order['remaining_amount'] ?? '' }}"
                                                                                @selected($isSelected)>
                                                                                {{ $order['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                                <input type="hidden" name="details[{{ $index }}][voucher_id]" class="voucher-id-input" value="{{ $detail->voucher_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][voucher_no]" class="voucher-no-input" value="{{ $detail->voucher_no }}">
                                                                <input type="hidden" name="details[{{ $index }}][voucher_type]" class="voucher-type-input" value="{{ $detail->voucher_type }}">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="details[{{ $index }}][description]" class="form-control description-input" placeholder="Line description" value="{{ $detail->description }}">
                                                                <input type="hidden" name="details[{{ $index }}][receipt_voucher_id]" class="receipt-voucher-id-input" value="{{ $detail->receipt_voucher_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][customer_advance_id]" class="customer-advance-id-input" value="{{ $detail->customer_advance_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][sales_order_id]" class="sales-order-id-input" value="{{ $detail->sales_order_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][delivery_order_id]" class="delivery-order-id-input" value="{{ $detail->delivery_order_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][reference_jv_id]" class="reference-jv-id-input" value="{{ $detail->reference_jv_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][reference_jv_detail_id]" class="reference-jv-detail-id-input" value="{{ $detail->reference_jv_detail_id }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="details[{{ $index }}][debit_amount]" 
                                                                    class="form-control debit-input" step="0.01" min="0" 
                                                                    value="{{ $detail->debit_amount > 0 ? $detail->debit_amount : '' }}" placeholder="0.00">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="details[{{ $index }}][credit_amount]" 
                                                                    class="form-control credit-input" step="0.01" min="0" 
                                                                    value="{{ $detail->credit_amount > 0 ? $detail->credit_amount : '' }}" placeholder="0.00">
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger remove-row" 
                                                                    style="display: {{ count($journalVoucher->journalVoucherDetails) > 2 ? 'block' : 'none' }};">
                                                                    <i class="ft-trash-2"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 5 : 3 }}" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td class="text-right"><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 5 : 3 }}" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td></td>
                                                        <td class="text-right"><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 5 : 3 }}" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                                        <td class="text-right"><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 8 : 6 }}">
                                                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                                                <i class="ft-plus"></i> Add Row
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Update Journal Voucher</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            let rowCount = $('#journalEntriesBody tr').length;
            const currentJvId = '{{ $journalVoucher->id }}';

            // Global maps for fast lookup
            window.rvMap = window.rvMap || {};
            @if(isset($rowRvs))
                @foreach($rowRvs as $rvList)
                    @foreach($rvList as $rvItem)
                        window.rvMap[{{ $rvItem['id'] }}] = {
                            id: {{ $rvItem['id'] }},
                            remaining_amount: {{ $rvItem['remaining_amount'] ?? 0 }},
                            unique_no: "{{ addslashes($rvItem['unique_no'] ?? '') }}",
                            text: "{{ addslashes($rvItem['text'] ?? '') }}"
                        };
                    @endforeach
                @endforeach
            @endif

            window.advMap = window.advMap || {};
            @if(isset($rowAdvances))
                @foreach($rowAdvances as $advList)
                    @foreach($advList as $advItem)
                        window.advMap[{{ $advItem['id'] }}] = {
                            id: {{ $advItem['id'] }},
                            voucher_no: "{{ addslashes($advItem['voucher_no'] ?? '') }}",
                            remaining_amount: {{ $advItem['remaining_amount'] ?? 0 }},
                            text: "{{ addslashes($advItem['text'] ?? '') }}"
                        };
                    @endforeach
                @endforeach
            @endif

            window.soMap = window.soMap || {};
            @if(isset($rowSos))
                @foreach($rowSos as $soList)
                    @foreach($soList as $soItem)
                        window.soMap[{{ $soItem['id'] }}] = {
                            id: {{ $soItem['id'] }},
                            reference_no: "{{ addslashes($soItem['reference_no'] ?? '') }}",
                            text: "{{ addslashes($soItem['text'] ?? '') }}"
                        };
                    @endforeach
                @endforeach
            @endif

            window.jvMap = window.jvMap || {};
            @if(isset($rowJvs))
                @foreach($rowJvs as $jvList)
                    @foreach($jvList as $jvItem)
                        window.jvMap[{{ $jvItem['id'] }}] = {
                            id: {{ $jvItem['id'] }},
                            detail_id: {{ $jvItem['detail_id'] ?? 'null' }},
                            jv_no: "{{ addslashes($jvItem['jv_no'] ?? '') }}",
                            remaining_amount: {{ $jvItem['remaining_amount'] ?? 0 }},
                            text: "{{ addslashes($jvItem['text'] ?? '') }}"
                        };
                    @endforeach
                @endforeach
            @endif

            window.grnMap = window.grnMap || {};
            @if(isset($rowOrders))
                @foreach($rowOrders as $orderList)
                    @foreach($orderList as $orderItem)
                        @if(($orderItem['type'] ?? '') === 'grn')
                            window.grnMap[{{ $orderItem['id'] }}] = {
                                id: {{ $orderItem['id'] }},
                                unique_no: "{{ addslashes($orderItem['unique_no'] ?? '') }}",
                                remaining_amount: {{ $orderItem['remaining_amount'] ?? 0 }},
                                text: "{{ addslashes($orderItem['text'] ?? '') }}"
                            };
                            @if(!empty($orderItem['unique_no']))
                                window.grnMap["{{ addslashes($orderItem['unique_no']) }}"] = window.grnMap[{{ $orderItem['id'] }}];
                            @endif
                        @endif
                    @endforeach
                @endforeach
            @endif

            // Initialize select2
            $('.select2').select2({ width: '100%' });

            // Attach pre-loaded initial account data to rows
            $('#journalEntriesBody tr').each(function() {
                const $row = $(this);
                $row.data('accountData', {
                    receipt_vouchers: $row.data('initial-rvs') || [],
                    customer_advances: $row.data('initial-advances') || [],
                    sales_orders: $row.data('initial-sos') || [],
                    journal_vouchers: $row.data('initial-jvs') || [],
                    grns: $row.data('initial-grns') || []
                });
            });

            function toggleReceivingColumns(isUserAction = false) {
                if ($('#receivingToggle').is(':checked')) {
                    $('#journalEntriesTable').addClass('receiving-active');
                    $('.receiving-col').show();
                    $('.order-col').hide();
                    if (isUserAction) {
                        $('.order-select').val('').trigger('change.select2');
                        $('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    }
                    $('#journalEntriesTable tfoot tr td:first-child').attr('colspan', 5);
                    $('#addRow').closest('td').attr('colspan', 8);
                    $('.receiving-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                } else {
                    $('#journalEntriesTable').removeClass('receiving-active');
                    $('.receiving-col').hide();
                    $('.order-col').show();
                    if (isUserAction) {
                        $('.adjustment-type-select').val('').trigger('change.select2');
                        $('.ref-doc-select').empty().append(new Option('Select Type First', '')).prop('disabled', true).trigger('change.select2');
                        $('.do-select').empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                        $('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                    }
                    $('#journalEntriesTable tfoot tr td:first-child').attr('colspan', 3);
                    $('#addRow').closest('td').attr('colspan', 6);
                    $('.order-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                }
            }

            $('#receivingToggle').change(function () {
                toggleReceivingColumns(true);
            });

            function showWarningPopup(title, text) {
                if (typeof Swal !== 'undefined' && !Swal.isVisible()) {
                    Swal.fire({
                        icon: 'warning',
                        title: title,
                        text: text,
                        confirmButtonColor: '#D95000'
                    });
                }
            }

            function updateSelect2Dropdown($select, items, placeholder, selectedId, isLoading = false) {
                if ($select.hasClass("select2-hidden-accessible") || $select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.empty();
                $select.append(new Option(placeholder, '', false, false));

                let hasSelected = false;
                if (items && items.length > 0) {
                    items.forEach(function (item) {
                        const isSel = Boolean(selectedId && selectedId == item.id);
                        if (isSel) hasSelected = true;
                        const opt = new Option(item.text, item.id, false, isSel);
                        if (item.remaining_amount !== undefined) {
                            $(opt).attr('data-remaining-amount', item.remaining_amount);
                        }
                        if (item.unique_no !== undefined) {
                            $(opt).attr('data-unique-no', item.unique_no);
                        } else if (item.reference_no !== undefined) {
                            $(opt).attr('data-unique-no', item.reference_no);
                        } else if (item.jv_no !== undefined) {
                            $(opt).attr('data-unique-no', item.jv_no);
                        } else if (item.voucher_no !== undefined) {
                            $(opt).attr('data-unique-no', item.voucher_no);
                        }
                        if (item.voucher_no !== undefined) {
                            $(opt).attr('data-voucher-no', item.voucher_no);
                        }
                        if (item.detail_id !== undefined) {
                            $(opt).attr('data-detail-id', item.detail_id);
                        }
                        if (item.type !== undefined) {
                            $(opt).attr('data-type', item.type);
                        }
                        $select.append(opt);
                    });
                }

                if (hasSelected && selectedId) {
                    $select.val(selectedId);
                } else {
                    $select.val('');
                }

                $select.prop('disabled', isLoading);
                $select.select2({ width: '100%' });

                if (hasSelected) {
                    $select.trigger('change');
                }
            }

            function loadAccountData($row, accId) {
                const $orderSelect = $row.find('.order-select');

                if (!accId) {
                    $row.removeData('accountData');
                    if ($orderSelect.length) {
                        updateSelect2Dropdown($orderSelect, [], 'Select Order (Select Account First)', null);
                    }
                    populateRefDocDropdown($row);
                    return;
                }

                if ($orderSelect.length) {
                    updateSelect2Dropdown($orderSelect, [], 'Loading Orders...', null, true);
                }

                $.ajax({
                    url: '{{ route("journal-voucher.get-account-related-data") }}',
                    type: 'GET',
                    data: {
                        acc_id: accId,
                        jv_id: currentJvId
                    },
                    success: function (res) {
                        $row.data('accountData', res);

                        if (res.receipt_vouchers) {
                            res.receipt_vouchers.forEach(function (rv) {
                                window.rvMap[rv.id] = rv;
                            });
                        }
                        if (res.customer_advances) {
                            res.customer_advances.forEach(function (adv) {
                                window.advMap[adv.id] = adv;
                            });
                        }
                        if (res.sales_orders) {
                            res.sales_orders.forEach(function (so) {
                                window.soMap[so.id] = so;
                            });
                        }
                        if (res.journal_vouchers) {
                            res.journal_vouchers.forEach(function (jv) {
                                window.jvMap[jv.id] = jv;
                            });
                        }
                        if (res.grns) {
                            res.grns.forEach(function (grn) {
                                window.grnMap[grn.id] = grn;
                                if (grn.unique_no) {
                                    window.grnMap[grn.unique_no] = grn;
                                }
                            });
                        }

                        // Populate Order dropdown for Receiving Toggle OFF mode
                        if ($orderSelect.length) {
                            const tableName = (res.table_name || '').toLowerCase();
                            if (tableName === 'customers') {
                                const orders = res.sales_orders || [];
                                const orderPlaceholder = orders.length > 0 ? 'Select Sale Order' : 'No Sale Orders Available';
                                updateSelect2Dropdown($orderSelect, orders, orderPlaceholder, null);
                            } else if (tableName === 'suppliers' || tableName === 'vendors' || tableName === 'vendor') {
                                const grns = res.grns || [];
                                const grnPlaceholder = grns.length > 0 ? 'Select GRN' : 'No GRNs Available';
                                updateSelect2Dropdown($orderSelect, grns, grnPlaceholder, null);
                            } else {
                                updateSelect2Dropdown($orderSelect, [], 'No Orders Available', null);
                            }
                        }

                        // If adjustment type is already selected, populate reference dropdown
                        populateRefDocDropdown($row);
                        syncAllDropdownDisabledStates();
                    },
                    error: function () {
                        if ($orderSelect.length) {
                            updateSelect2Dropdown($orderSelect, [], 'Error loading Orders', null);
                        }
                    }
                });
            }

            function syncAllDropdownDisabledStates() {
                const isReceiving = $('#receivingToggle').is(':checked');

                const selectedRvs = {};
                const selectedAdvs = {};
                const selectedSos = {};
                const selectedDos = {};
                const selectedJvs = {};
                const selectedOrders = {};

                $('#journalEntriesBody tr').each(function(idx) {
                    const lineNo = idx + 1;
                    const type = $(this).find('.adjustment-type-select').val();

                    if (isReceiving) {
                        if (type === 'receipt_voucher') {
                            const id = $(this).find('.receipt-voucher-id-input').val();
                            if (id) selectedRvs[id] = lineNo;
                        } else if (type === 'excess_amount') {
                            const id = $(this).find('.customer-advance-id-input').val();
                            if (id) selectedAdvs[id] = lineNo;
                        } else if (type === 'journal_voucher') {
                            const id = $(this).find('.reference-jv-id-input').val();
                            if (id) selectedJvs[id] = lineNo;
                        } else if (type === 'so_do') {
                            const soId = $(this).find('.sales-order-id-input').val();
                            if (soId) selectedSos[soId] = lineNo;
                            const doId = $(this).find('.delivery-order-id-input').val();
                            if (doId && doId !== 'direct_so') selectedDos[doId] = lineNo;
                        }
                    } else {
                        const orderVal = $(this).find('.order-select').val();
                        const accId = $(this).find('.account-select').val();
                        if (orderVal && accId) selectedOrders[accId + '_' + orderVal] = lineNo;
                    }
                });

                $('#journalEntriesBody tr').each(function(idx) {
                    const $row = $(this);
                    const thisLineNo = idx + 1;

                    if (isReceiving) {
                        const type = $row.find('.adjustment-type-select').val();
                        const $refSelect = $row.find('.ref-doc-select');
                        const $doSelect = $row.find('.do-select');

                        if (type === 'receipt_voucher') {
                            $refSelect.find('option').each(function() {
                                const optVal = $(this).val();
                                if (!optVal) return;
                                const pickedIn = selectedRvs[optVal];
                                const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                                if (pickedIn && pickedIn !== thisLineNo) {
                                    $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                                } else {
                                    $(this).prop('disabled', false).text(baseText);
                                }
                            });
                        } else if (type === 'excess_amount') {
                            $refSelect.find('option').each(function() {
                                const optVal = $(this).val();
                                if (!optVal) return;
                                const pickedIn = selectedAdvs[optVal];
                                const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                                if (pickedIn && pickedIn !== thisLineNo) {
                                    $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                                } else {
                                    $(this).prop('disabled', false).text(baseText);
                                }
                            });
                        } else if (type === 'journal_voucher') {
                            $refSelect.find('option').each(function() {
                                const optVal = $(this).val();
                                if (!optVal) return;
                                const pickedIn = selectedJvs[optVal];
                                const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                                if (pickedIn && pickedIn !== thisLineNo) {
                                    $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                                } else {
                                    $(this).prop('disabled', false).text(baseText);
                                }
                            });
                        } else if (type === 'so_do') {
                            $refSelect.find('option').each(function() {
                                const optVal = $(this).val();
                                if (!optVal) return;
                                const pickedIn = selectedSos[optVal];
                                const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                                if (pickedIn && pickedIn !== thisLineNo) {
                                    $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                                } else {
                                    $(this).prop('disabled', false).text(baseText);
                                }
                            });

                            $doSelect.find('option').each(function() {
                                const optVal = $(this).val();
                                if (!optVal || optVal === 'direct_so') return;
                                const pickedIn = selectedDos[optVal];
                                const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                                if (pickedIn && pickedIn !== thisLineNo) {
                                    $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                                } else {
                                    $(this).prop('disabled', false).text(baseText);
                                }
                            });
                        }
                    } else {
                        const $orderSelect = $row.find('.order-select');
                        const thisAccId = $row.find('.account-select').val();
                        $orderSelect.find('option').each(function() {
                            const optVal = $(this).val();
                            if (!optVal) return;
                            const pickedIn = thisAccId ? selectedOrders[thisAccId + '_' + optVal] : null;
                            const baseText = $(this).text().replace(/\s*\[Already Selected in Line \d+\]/, '');
                            if (pickedIn && pickedIn !== thisLineNo) {
                                $(this).prop('disabled', true).text(baseText + ' [Already Selected in Line ' + pickedIn + ']');
                            } else {
                                $(this).prop('disabled', false).text(baseText);
                            }
                        });
                    }
                });
            }

            function populateRefDocDropdown($row) {
                const type = $row.find('.adjustment-type-select').val();
                const $refSelect = $row.find('.ref-doc-select');
                const $doSelect = $row.find('.do-select');
                const accData = $row.data('accountData') || {};

                if (!type) {
                    updateSelect2Dropdown($refSelect, [], 'Select Type First', null, true);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                    syncAllDropdownDisabledStates();
                    return;
                }

                if (type === 'receipt_voucher') {
                    const items = accData.receipt_vouchers || [];
                    const placeholder = items.length ? 'Select Receipt Voucher' : 'No Receipt Vouchers Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'excess_amount') {
                    const items = accData.customer_advances || [];
                    const placeholder = items.length ? 'Select Excess Amount' : 'No Excess Amounts Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'so_do') {
                    const items = accData.sales_orders || [];
                    const placeholder = items.length ? 'Select Sales Order' : 'No Sales Orders Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('Select SO First', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'journal_voucher') {
                    const items = accData.journal_vouchers || [];
                    const placeholder = items.length ? 'Select Journal Voucher' : 'No Credit JVs Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                }
                syncAllDropdownDisabledStates();
            }

            function setRowDebitAmount($row, amount) {
                const num = parseFloat(amount);
                const type = $row.find('.adjustment-type-select').val();
                const $allRows = $('#journalEntriesBody tr');

                // Advance withdrawals (RV or Excess payment) must ALWAYS be debited to customer account
                if (type === 'receipt_voucher' || type === 'excess_amount') {
                    if (!isNaN(num) && num > 0) {
                        $row.find('.debit-input').attr('max', num.toFixed(2)).val(num.toFixed(2));
                        $row.find('.credit-input').val('').removeAttr('max');

                        if ($allRows.length === 2) {
                            const $otherRow = $allRows.not($row);
                            const otherCredit = parseFloat($otherRow.find('.credit-input').val()) || 0;
                            const otherDebit = parseFloat($otherRow.find('.debit-input').val()) || 0;
                            if (otherCredit > 0 || otherDebit === 0) {
                                $otherRow.find('.credit-input').val(num.toFixed(2));
                                $otherRow.find('.debit-input').val('');
                            }
                        }
                    } else {
                        $row.find('.debit-input').removeAttr('max').val('');
                        $row.find('.credit-input').removeAttr('max').val('');
                    }
                    calculateTotals();
                    return;
                }

                const currentCredit = parseFloat($row.find('.credit-input').val()) || 0;
                const currentDebit = parseFloat($row.find('.debit-input').val()) || 0;

                // 1. If this row already has a credit amount and no debit, it's explicitly a CREDIT row: preserve credit!
                if (currentCredit > 0 && currentDebit === 0) {
                    $row.find('.debit-input').removeAttr('max').val('');
                    calculateTotals();
                    return;
                }

                // 2. Check if other rows in the voucher have excess debit (meaning this row is needed as CREDIT)
                let totalOtherDebit = 0;
                let totalOtherCredit = 0;
                $allRows.not($row).each(function () {
                    totalOtherDebit += parseFloat($(this).find('.debit-input').val()) || 0;
                    totalOtherCredit += parseFloat($(this).find('.credit-input').val()) || 0;
                });

                if (totalOtherDebit > totalOtherCredit) {
                    // Counter/receiving row (CREDIT side)
                    const balanceNeeded = totalOtherDebit - totalOtherCredit;
                    $row.find('.debit-input').removeAttr('max').val('');
                    if (!$row.find('.credit-input').val() || parseFloat($row.find('.credit-input').val()) === 0) {
                        $row.find('.credit-input').val(balanceNeeded.toFixed(2));
                    }
                } else {
                    // This row is a DEBIT row (withdrawing advance)
                    if (!isNaN(num) && num > 0) {
                        $row.find('.debit-input').attr('max', num.toFixed(2)).val(num.toFixed(2));
                        $row.find('.credit-input').val('').removeAttr('max');

                        if ($allRows.length === 2) {
                            const $otherRow = $allRows.not($row);
                            const otherCredit = parseFloat($otherRow.find('.credit-input').val()) || 0;
                            const otherDebit = parseFloat($otherRow.find('.debit-input').val()) || 0;
                            if (otherCredit > 0 || otherDebit === 0) {
                                $otherRow.find('.credit-input').val(num.toFixed(2));
                                $otherRow.find('.debit-input').val('');
                            }
                        }
                    } else {
                        $row.find('.debit-input').removeAttr('max').val('');
                        if (currentCredit <= 0) {
                            $row.find('.credit-input').val('');
                        }
                    }
                }
                calculateTotals();
            }

            $(document).on('change', '.account-select', function () {
                const $row = $(this).closest('tr');
                const accId = $(this).val();
                $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                $row.find('.debit-input').removeAttr('max');
                calculateTotals();
                loadAccountData($row, accId);
            });

            $(document).on('change', '.adjustment-type-select', function () {
                const $row = $(this).closest('tr');
                $row.find('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                $row.find('.debit-input').removeAttr('max').val('');
                $row.find('.credit-input').removeAttr('max').val('');
                populateRefDocDropdown($row);
                calculateTotals();
                syncAllDropdownDisabledStates();
            });

            $(document).on('change', '.ref-doc-select', function () {
                const $refSelect = $(this);
                const $row = $refSelect.closest('tr');
                const type = $row.find('.adjustment-type-select').val();
                const val = $refSelect.val();
                const $doSelect = $row.find('.do-select');

                if (!val) {
                    $row.find('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max').val('');
                    if (type === 'so_do') {
                        $doSelect.empty().append(new Option('Select SO First', '')).prop('disabled', true).trigger('change.select2');
                    }
                    calculateTotals();
                    syncAllDropdownDisabledStates();
                    return;
                }

                // Check duplicate document selection across other rows
                let duplicateRowIndex = null;
                $('#journalEntriesBody tr').not($row).each(function () {
                    const otherType = $(this).find('.adjustment-type-select').val();
                    if (otherType === type) {
                        let otherId = null;
                        if (type === 'receipt_voucher') otherId = $(this).find('.receipt-voucher-id-input').val();
                        else if (type === 'excess_amount') otherId = $(this).find('.customer-advance-id-input').val();
                        else if (type === 'journal_voucher') otherId = $(this).find('.reference-jv-id-input').val();
                        else if (type === 'so_do') otherId = $(this).find('.sales-order-id-input').val();

                        if (otherId && String(otherId) === String(val)) {
                            duplicateRowIndex = $(this).index() + 1;
                            return false;
                        }
                    }
                });

                if (duplicateRowIndex !== null) {
                    let typeLabel = 'Document';
                    if (type === 'receipt_voucher') typeLabel = 'Receipt Voucher';
                    else if (type === 'excess_amount') typeLabel = 'Excess Payment';
                    else if (type === 'journal_voucher') typeLabel = 'Journal Voucher';
                    else if (type === 'so_do') typeLabel = 'Sales Order';

                    const docNo = $refSelect.find('option:selected').attr('data-unique-no') || $refSelect.find('option:selected').text();

                    $refSelect.val('').trigger('change.select2');
                    $row.find('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max').val('');
                    $row.find('.credit-input').removeAttr('max').val('');
                    calculateTotals();
                    syncAllDropdownDisabledStates();

                    showWarningPopup(
                        `${typeLabel} Already Selected`,
                        `${typeLabel} (${docNo}) is already selected in Line ${duplicateRowIndex}. A document cannot be selected multiple times.`
                    );
                    return;
                }

                const $selectedOpt = $refSelect.find('option:selected');
                const remAmountAttr = $selectedOpt.attr('data-remaining-amount');
                const remAmount = remAmountAttr !== undefined ? parseFloat(remAmountAttr) : 0;

                if (type === 'receipt_voucher') {
                    $row.find('.receipt-voucher-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-unique-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('receipt_voucher');
                    setRowDebitAmount($row, remAmount);
                    syncAllDropdownDisabledStates();
                } else if (type === 'excess_amount') {
                    $row.find('.customer-advance-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-voucher-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('customer_advance');
                    setRowDebitAmount($row, remAmount);
                    syncAllDropdownDisabledStates();
                } else if (type === 'journal_voucher') {
                    const detailId = $selectedOpt.attr('data-detail-id') || (window.jvMap && window.jvMap[val] ? window.jvMap[val].detail_id : null);
                    $row.find('.reference-jv-id-input').val(val);
                    $row.find('.reference-jv-detail-id-input').val(detailId || '');
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-unique-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('journal_voucher');
                    setRowDebitAmount($row, remAmount);
                    syncAllDropdownDisabledStates();
                } else if (type === 'so_do') {
                    $row.find('.sales-order-id-input').val(val);
                    syncAllDropdownDisabledStates();
                    $doSelect.empty().append(new Option('Loading Delivery Orders...', '')).prop('disabled', true).trigger('change.select2');

                    const soDosUrl = '{{ route("journal-voucher.so-dos", ":soId") }}'.replace(':soId', val);
                    $.ajax({
                        url: soDosUrl,
                        type: 'GET',
                        data: {
                            exclude_jv_id: currentJvId
                        },
                        success: function (res) {
                            if (res.success) {
                                $doSelect.empty();
                                if (res.delivery_orders && res.delivery_orders.length > 0) {
                                    $doSelect.append(new Option('Select Delivery Order', ''));
                                    res.delivery_orders.forEach(function (doItem) {
                                        const opt = new Option(doItem.text, doItem.id);
                                        $(opt).attr('data-remaining-amount', doItem.remaining_amount);
                                        $(opt).attr('data-reference-no', doItem.reference_no);
                                        $doSelect.append(opt);
                                    });

                                    const directLabel = res.so_remaining_amount > 0.01
                                        ? `Direct SO Advance (Rem: Rs. ${res.so_formatted_remaining})`
                                        : `Direct SO (Order: ${res.so_reference_no})`;
                                    const optDirect = new Option(directLabel, 'direct_so');
                                    $(optDirect).attr('data-remaining-amount', res.so_remaining_amount);
                                    $(optDirect).attr('data-reference-no', res.so_reference_no);
                                    $doSelect.append(optDirect);

                                    $doSelect.prop('disabled', false).trigger('change.select2');
                                    syncAllDropdownDisabledStates();
                                } else {
                                    // No DOs created for this SO -> Auto adjust direct SO advance
                                    const directText = res.so_remaining_amount > 0.01
                                        ? `No DOs (Direct SO Rem: Rs. ${res.so_formatted_remaining})`
                                        : `No DOs (Direct SO: ${res.so_reference_no})`;
                                    const opt = new Option(directText, 'direct_so', true, true);
                                    $(opt).attr('data-remaining-amount', res.so_remaining_amount);
                                    $(opt).attr('data-reference-no', res.so_reference_no);
                                    $doSelect.append(opt).prop('disabled', true).trigger('change.select2');

                                    $row.find('.delivery-order-id-input').val('');
                                    $row.find('.voucher-id-input').val(val);
                                    $row.find('.voucher-no-input').val(res.so_reference_no);
                                    $row.find('.voucher-type-input').val('sales_order');

                                    setRowDebitAmount($row, res.so_remaining_amount);
                                    syncAllDropdownDisabledStates();
                                }
                            }
                        },
                        error: function () {
                            $doSelect.empty().append(new Option('Error loading DOs', '')).prop('disabled', true).trigger('change.select2');
                        }
                    });
                }
            });

            $(document).on('change', '.do-select', function () {
                const $doSelect = $(this);
                const $row = $doSelect.closest('tr');
                const val = $doSelect.val();

                if (!val) {
                    $row.find('.delivery-order-id-input').val('');
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max').val('');
                    calculateTotals();
                    syncAllDropdownDisabledStates();
                    return;
                }

                // Check duplicate Delivery Order across other rows
                if (val !== 'direct_so') {
                    let duplicateRowIndex = null;
                    $('#journalEntriesBody tr').not($row).each(function () {
                        const otherDoId = $(this).find('.delivery-order-id-input').val();
                        if (otherDoId && String(otherDoId) === String(val)) {
                            duplicateRowIndex = $(this).index() + 1;
                            return false;
                        }
                    });

                    if (duplicateRowIndex !== null) {
                        const doNo = $doSelect.find('option:selected').attr('data-reference-no') || $doSelect.find('option:selected').text();
                        $doSelect.val('').trigger('change.select2');
                        $row.find('.delivery-order-id-input').val('');
                        $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                        $row.find('.debit-input').removeAttr('max').val('');
                        $row.find('.credit-input').removeAttr('max').val('');
                        calculateTotals();
                        syncAllDropdownDisabledStates();

                        showWarningPopup(
                            'Delivery Order Already Selected',
                            `Delivery Order (${doNo}) is already selected in Line ${duplicateRowIndex}. You cannot select the same Delivery Order multiple times.`
                        );
                        return;
                    }
                }

                const $selectedOpt = $doSelect.find('option:selected');
                const remAmountAttr = $selectedOpt.attr('data-remaining-amount');
                const remAmount = remAmountAttr !== undefined ? parseFloat(remAmountAttr) : 0;
                const refNo = $selectedOpt.attr('data-reference-no') || $selectedOpt.text();

                if (val === 'direct_so') {
                    const soId = $row.find('.sales-order-id-input').val();
                    $row.find('.delivery-order-id-input').val('');
                    $row.find('.voucher-id-input').val(soId);
                    $row.find('.voucher-no-input').val(refNo);
                    $row.find('.voucher-type-input').val('sales_order');
                    setRowDebitAmount($row, remAmount);
                } else {
                    $row.find('.delivery-order-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val(refNo);
                    $row.find('.voucher-type-input').val('delivery_order');
                    setRowDebitAmount($row, remAmount);
                }
                syncAllDropdownDisabledStates();
            });

            // Listen for order selection change (Toggle OFF mode)
            $(document).on('change', '.order-select', function () {
                const $row = $(this).closest('tr');
                const $selected = $(this).find('option:selected');
                const val = $(this).val();

                if (val) {
                    // Check duplicate order selection across other rows for the same account
                    let duplicateRowIndex = null;
                    const thisAccId = $row.find('.account-select').val();
                    $('#journalEntriesBody tr').not($row).each(function () {
                        const otherAccId = $(this).find('.account-select').val();
                        const otherOrderId = $(this).find('.order-select').val();
                        if (thisAccId && otherAccId && String(thisAccId) === String(otherAccId) && otherOrderId && String(otherOrderId) === String(val)) {
                            duplicateRowIndex = $(this).index() + 1;
                            return false;
                        }
                    });

                    if (duplicateRowIndex !== null) {
                        const orderNo = $selected.attr('data-unique-no') || $selected.text();
                        $(this).val('').trigger('change.select2');
                        $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                        $row.find('.debit-input').removeAttr('max');
                        calculateTotals();
                        syncAllDropdownDisabledStates();

                        showWarningPopup(
                            'Order Already Selected',
                            `Order/GRN (${orderNo}) is already selected in Line ${duplicateRowIndex} for this account. You cannot select the same Order/GRN multiple times for the same account.`
                        );
                        return;
                    }

                    const uniqueNo = $selected.attr('data-unique-no') || $selected.text() || '';
                    const type = $selected.attr('data-type') || '';
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val(uniqueNo);
                    $row.find('.voucher-type-input').val(type);

                    if (type === 'grn') {
                        const currentDebit = parseFloat($row.find('.debit-input').val()) || null;
                        $.ajax({
                            url: '{{ route("journal-voucher.check-grn-limit") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                grn_id: val,
                                jv_id: currentJvId,
                                debit_amount: currentDebit,
                                acc_id: $row.find('.account-select').val()
                            },
                            success: function (res) {
                                if (res.success) {
                                    window.grnMap[res.grn_id] = res;
                                    window.grnMap[res.unique_no] = res;
                                    $selected.attr('data-remaining-amount', res.available_amount);
                                    $row.find('.debit-input').attr('max', res.available_amount.toFixed(2));
                                    const enteredDebit = parseFloat($row.find('.debit-input').val()) || 0;
                                    if (enteredDebit > (res.available_amount + 0.001)) {
                                        $row.find('.debit-input').val(res.available_amount > 0 ? res.available_amount.toFixed(2) : '');
                                        showWarningPopup('Debit Exceeds GRN Limit (' + res.unique_no + ')', 'Debit amount cannot exceed approved GRN available balance of ' + Number(res.available_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }));
                                        calculateTotals();
                                    }
                                }
                            }
                        });
                    } else {
                        $row.find('.debit-input').removeAttr('max');
                    }
                    syncAllDropdownDisabledStates();
                } else {
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max');
                    syncAllDropdownDisabledStates();
                }
            });


            function getRowLimit($row) {
                const isReceiving = $('#receivingToggle').is(':checked');
                if (isReceiving) {
                    const type = $row.find('.adjustment-type-select').val();
                    if (type === 'so_do') {
                        const doVal = $row.find('.do-select').val();
                        if (doVal) {
                            const opt = $row.find('.do-select option:selected');
                            const rem = parseFloat(opt.attr('data-remaining-amount'));
                            if (!isNaN(rem)) return { limit: rem, label: doVal === 'direct_so' ? 'Sales Order' : 'Delivery Order' };
                        }
                    } else if (type) {
                        const opt = $row.find('.ref-doc-select option:selected');
                        const rem = parseFloat(opt.attr('data-remaining-amount'));
                        let label = 'Document';
                        if (type === 'receipt_voucher') label = 'Receipt Voucher';
                        else if (type === 'excess_amount') label = 'Excess Amount';
                        else if (type === 'journal_voucher') label = 'Journal Voucher';
                        if (!isNaN(rem)) return { limit: rem, label: label };
                    }
                }
                return null;
            }

            function validateAndClampInput($input) {
                const $row = $input.closest('tr');
                const limitInfo = getRowLimit($row);

                if (limitInfo && limitInfo.limit !== null && limitInfo.limit > 0) {
                    $input.attr('max', limitInfo.limit.toFixed(2));
                    const enteredVal = parseFloat($input.val()) || 0;
                    if (enteredVal > (limitInfo.limit + 0.001)) {
                        $input.val(limitInfo.limit.toFixed(2));
                        const formatted = Number(limitInfo.limit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        showWarningPopup(`Amount Exceeds ${limitInfo.label} Balance`, `Amount cannot exceed ${limitInfo.label} remaining balance of ${formatted}`);
                    }
                }
            }

            // Add new row
            $('#addRow').click(function () {
                const isReceiving = $('#receivingToggle').is(':checked');
                const receivingDisplayStyle = isReceiving ? '' : 'display: none;';
                const orderDisplayStyle = isReceiving ? 'display: none;' : '';

                const newRow = `
                    <tr>
                        <td>
                            <select name="details[${rowCount}][acc_id]" class="form-control select2 account-select" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" data-table-name="{{ strtolower($account->table_name ?? '') }}">{{ $account->name }} ({{ $account->unique_no }}){{ $account->type_label ? " - [" . $account->type_label . "]" : "" }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 adjustment-type-select" style="width: 100%;">
                                <option value="">Select Type</option>
                                <option value="receipt_voucher">Receipt Voucher (RV)</option>
                                <option value="excess_amount">Excess Amount</option>
                                <option value="so_do">SO / DO</option>
                                <option value="journal_voucher">Journal Voucher (JV)</option>
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 ref-doc-select" style="width: 100%;" disabled>
                                <option value="">Select Type First</option>
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 do-select" style="width: 100%;" disabled>
                                <option value="">N/A</option>
                            </select>
                        </td>
                        <td class="order-col" style="${orderDisplayStyle}">
                            <select name="details[${rowCount}][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                <option value="">Select Order (Select Account First)</option>
                            </select>
                            <input type="hidden" name="details[${rowCount}][voucher_id]" class="voucher-id-input">
                            <input type="hidden" name="details[${rowCount}][voucher_no]" class="voucher-no-input">
                            <input type="hidden" name="details[${rowCount}][voucher_type]" class="voucher-type-input">
                        </td>
                        <td>
                            <input type="text" name="details[${rowCount}][description]" class="form-control description-input" placeholder="Line description">
                            <input type="hidden" name="details[${rowCount}][receipt_voucher_id]" class="receipt-voucher-id-input">
                            <input type="hidden" name="details[${rowCount}][customer_advance_id]" class="customer-advance-id-input">
                            <input type="hidden" name="details[${rowCount}][sales_order_id]" class="sales-order-id-input">
                            <input type="hidden" name="details[${rowCount}][delivery_order_id]" class="delivery-order-id-input">
                            <input type="hidden" name="details[${rowCount}][reference_jv_id]" class="reference-jv-id-input">
                            <input type="hidden" name="details[${rowCount}][reference_jv_detail_id]" class="reference-jv-detail-id-input">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                <i class="ft-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#journalEntriesBody').append(newRow);
                $('#journalEntriesBody tr:last .select2').select2({ width: '100%' });
                rowCount++;
                updateRemoveButtons();
                calculateTotals();
                syncAllDropdownDisabledStates();
            });

            // Remove row
            $(document).on('click', '.remove-row', function () {
                if ($('#journalEntriesBody tr').length > 2) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    calculateTotals();
                    syncAllDropdownDisabledStates();
                }
            });

            $(document).on('input', '.debit-input', function () {
                const $row = $(this).closest('tr');
                const debitValue = parseFloat($(this).val()) || 0;
                if (debitValue > 0) {
                    $row.find('.credit-input').val('');
                }
                validateAndClampInput($(this));
                calculateTotals();
            });

            $(document).on('input', '.credit-input', function () {
                const $row = $(this).closest('tr');
                const creditValue = parseFloat($(this).val()) || 0;
                if (creditValue > 0) {
                    $row.find('.debit-input').val('');
                }
                validateAndClampInput($(this));
                calculateTotals();
            });

            $(document).on('blur change', '.debit-input, .credit-input', function () {
                validateAndClampInput($(this));
                calculateTotals();
            });

            function updateRemoveButtons() {
                const count = $('#journalEntriesBody tr').length;
                if (count > 2) {
                    $('.remove-row').show();
                } else {
                    $('.remove-row').hide();
                }
            }

            function calculateTotals() {
                let totalDebits = 0;
                let totalCredits = 0;

                $('#journalEntriesBody tr').each(function () {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    totalDebits += debitAmount;
                    totalCredits += creditAmount;
                });

                $('#totalDebits').text(totalDebits.toFixed(2));
                $('#totalCredits').text(totalCredits.toFixed(2));

                const difference = totalDebits - totalCredits;
                $('#difference').text(difference.toFixed(2));

                if (Math.abs(difference) > 0.01) {
                    $('#difference').css('color', 'red');
                } else {
                    $('#difference').css('color', 'green');
                }
            }

            // Form submission validation
            $('#ajaxSubmit').on('submit', function (e) {
                calculateTotals();

                const totalDebits = parseFloat($('#totalDebits').text()) || 0;
                const totalCredits = parseFloat($('#totalCredits').text()) || 0;

                let invalidLine = null;

                $('#journalEntriesBody tr').each(function (index) {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    if ((debitAmount <= 0 && creditAmount <= 0) || (debitAmount > 0 && creditAmount > 0)) {
                        invalidLine = index + 1;
                        return false;
                    }
                });

                if (invalidLine !== null) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Line ' + invalidLine + ' must contain either a debit or a credit amount greater than zero (but not both).',
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }

                if (Math.abs(totalDebits - totalCredits) > 0.01) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Total debits must equal total credits. Current difference: ' + (totalDebits - totalCredits).toFixed(2),
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }

                // Check duplicate document selections across lines
                const isReceiving = $('#receivingToggle').is(':checked');
                const seenRvs = {};
                const seenAdvs = {};
                const seenDos = {};
                const seenSos = {};
                const seenJvs = {};
                const seenOrders = {};
                let duplicateError = null;

                $('#journalEntriesBody tr').each(function (index) {
                    const lineNo = index + 1;
                    const rvId = $(this).find('.receipt-voucher-id-input').val();
                    const advId = $(this).find('.customer-advance-id-input').val();
                    const doId = $(this).find('.delivery-order-id-input').val();
                    const soId = $(this).find('.sales-order-id-input').val();
                    const jvId = $(this).find('.reference-jv-id-input').val();
                    const orderVal = $(this).find('.order-select').val();
                    const voucherNo = $(this).find('.voucher-no-input').val() || '';

                    if (isReceiving) {
                        if (rvId) {
                            if (seenRvs[rvId]) {
                                duplicateError = `Receipt Voucher (${voucherNo || rvId}) is selected in multiple lines (Line ${seenRvs[rvId]} and Line ${lineNo}). Each Receipt Voucher can only be selected once.`;
                                return false;
                            }
                            seenRvs[rvId] = lineNo;
                        }
                        if (advId) {
                            if (seenAdvs[advId]) {
                                duplicateError = `Excess Payment (${voucherNo || advId}) is selected in multiple lines (Line ${seenAdvs[advId]} and Line ${lineNo}). Each Excess Payment can only be selected once.`;
                                return false;
                            }
                            seenAdvs[advId] = lineNo;
                        }
                        if (doId) {
                            if (seenDos[doId]) {
                                duplicateError = `Delivery Order (${voucherNo || doId}) is selected in multiple lines (Line ${seenDos[doId]} and Line ${lineNo}). Each Delivery Order can only be selected once.`;
                                return false;
                            }
                            seenDos[doId] = lineNo;
                        }
                        if (soId && !doId) {
                            if (seenSos[soId]) {
                                duplicateError = `Sales Order (${voucherNo || soId}) is selected in multiple lines (Line ${seenSos[soId]} and Line ${lineNo}). Each Sales Order can only be selected once.`;
                                return false;
                            }
                            seenSos[soId] = lineNo;
                        }
                        if (jvId) {
                            if (seenJvs[jvId]) {
                                duplicateError = `Journal Voucher (${voucherNo || jvId}) is selected in multiple lines (Line ${seenJvs[jvId]} and Line ${lineNo}). Each Journal Voucher can only be selected once.`;
                                return false;
                            }
                            seenJvs[jvId] = lineNo;
                        }
                    } else {
                        if (orderVal) {
                            const accId = $(this).find('.account-select').val();
                            const orderKey = (accId ? accId : '0') + '_' + orderVal;
                            if (seenOrders[orderKey]) {
                                duplicateError = `Order/GRN is selected in multiple lines for the same account (Line ${seenOrders[orderKey]} and Line ${lineNo}). Each Order/GRN can only be selected once per account.`;
                                return false;
                            }
                            seenOrders[orderKey] = lineNo;
                        }
                    }
                });

                if (duplicateError) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    Swal.fire({
                        icon: 'error',
                        title: 'Duplicate Selection Error',
                        text: duplicateError,
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }
            });

            // Set initial state
            toggleReceivingColumns();
            updateRemoveButtons();
            calculateTotals();
            syncAllDropdownDisabledStates();
        });
    </script>
@endsection
