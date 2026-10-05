<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" value="{{ $payment_intimation->customer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Sale Order</label>
                    <input type="text" class="form-control" value="{{ $payment_intimation->sale_order->reference_no ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label font-weight-bold">Bank & Payment Deposit Breakdown</label>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm m-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">#</th>
                                <th width="50%">Bank</th>
                                <th width="40%" class="text-right">Deposit Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($payment_intimation->deposits && $payment_intimation->deposits->count() > 0)
                                @foreach($payment_intimation->deposits as $idx => $dep)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $dep->bank->bank_name ?? 'N/A' }} {{ $dep->bank && $dep->bank->account_no ? '('.$dep->bank->account_no.')' : '' }}</td>
                                        <td class="text-right">{{ number_format($dep->payment_deposit, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>1</td>
                                    <td>
                                        @if($payment_intimation->bank_relation)
                                            {{ $payment_intimation->bank_relation->bank_name }} - {{ $payment_intimation->bank_relation->account_no }}
                                        @elseif(!empty($payment_intimation->bank))
                                            @php
                                                $decoded = json_decode($payment_intimation->bank, true);
                                            @endphp
                                            {{ is_array($decoded) ? implode(', ', $decoded) : $payment_intimation->bank }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($payment_intimation->payment_deposit, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="2" class="text-right">Total Deposit:</td>
                                <td class="text-right">{{ number_format($payment_intimation->payment_deposit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($payment_intimation->attachment)
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label d-block">Attachment</label>
                    <a href="{{ asset($payment_intimation->attachment) }}" target="_blank" class="btn btn-sm btn-info mt-1"><i class="ft-eye"></i> View Attachment</a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<div class="row bottom-button-bar mt-2">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>
