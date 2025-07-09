  <table class="table table-striped table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Date</th>
                                    <th>Voucher No</th>
                                    <th>Description</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($transactions) > 0)
                                    @php
                                        $balance = 0;
                                        $openingBalance = $openingBalance ?? 0;
                                        $balance += $openingBalance;
                                    @endphp
                                    
                                    <!-- Opening Balance Row -->
                                    <tr class="font-weight-bold">
                                        <td class="text-center">{{ date('d-m-Y', strtotime(request('start_date') ?? date('Y-m-01'))) }}</td>
                                        <td colspan="2">OPENING BALANCE</td>
                                        <td class="text-right">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                                        <td class="text-right">{{ $openingBalance < 0 ? number_format(abs($openingBalance), 2) : '-' }}</td>
                                        <td class="text-right">{{ number_format($balance, 2) }}</td>
                                    </tr>
                                    
                                    @foreach($transactions as $transaction)
                                        @php
                                            if($transaction->type == 'debit') {
                                                $balance += $transaction->amount;
                                            } else {
                                                $balance -= $transaction->amount;
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $transaction->voucher_date->format('d-m-Y') }}</td>
                                            <td>{{ $transaction->voucher_no }}</td>
                                            <td>
                                                {{ $transaction->remarks }}
                                                @if($transaction->payment_against)
                                                    <br><small class="text-muted">Against: {{ $transaction->payment_against }} 
                                                    @if($transaction->against_reference_no)
                                                        ({{ $transaction->against_reference_no }})
                                                    @endif
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ $transaction->type == 'debit' ? number_format($transaction->amount, 2) : '-' }}</td>
                                            <td class="text-right">{{ $transaction->type == 'credit' ? number_format($transaction->amount, 2) : '-' }}</td>
                                            <td class="text-right">{{ number_format($balance, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                
                                @endif
                            </tbody>
                        </table>
                 