  <div class="mb-2">
      <span>
          <strong>Date Range:</strong> {{ $daterange }}
      </span>
      @if ($accountName)
          &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
          <span>
              <strong>Account:</strong> {{ ucwords($accountName) }}
          </span>
      @endif
  </div>
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
          @if (count($transactions) > 0)
              @php
                  $balance = 0;
                  $openingBalance = $openingBalance ?? 0;
                  $balance += $openingBalance;
              @endphp

              <tr class="font-weight-bold">
                  <td class="text-center">
                      {{ date('d-m-Y', strtotime(request('start_date') ?? date('Y-m-01'))) }}</td>
                  <td></td>
                  <td></td>
                  <td class="text-right">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                  <td class="text-right">{{ $openingBalance < 0 ? number_format(abs($openingBalance), 2) : '-' }}</td>
                  <td class="text-right">{{ number_format($balance, 2) }}</td>
              </tr>

              @foreach ($transactions as $transaction)
                  @php
                      if ($transaction->type == 'debit') {
                          $balance += $transaction->amount;
                      } else {
                          $balance -= $transaction->amount;
                      }
                  @endphp
                  <tr>
                      <td class="text-center">{{ $transaction->voucher_date->format('d-m-Y') }}</td>
                      <td>
                          {{ $transaction->voucher_no }} <br>
                          @if ($transaction->counter_account_id)
                              @php
                                  $startDate = $transaction->voucher_date->format('m/d/Y');

                                  $endDate = \Carbon\Carbon::now()->format('m/d/Y');
                                  $daterange = urlencode($startDate . ' - ' . $endDate);
                              @endphp
                              <a href="{{ url('transactions/report') }}?account_id={{ $transaction->counterAccount->id }}&daterange={{ $daterange }}&_f"
                                  target="_blank" data-toggle="tooltip" data-placement="top"
                                  title="Counter Account: {{ $transaction->counterAccount->name }}">
                                  <small>{{ $transaction->counterAccount->name }}</small>
                              </a>
                          @else
                              <span title="Counter Account"><small>N/A</small></span>
                          @endif
                      </td>
                      <td>
                          {{ $transaction->remarks }}
                          @if ($transaction->payment_against)
                              <br><small class="text-muted">Against: {{ $transaction->payment_against }}
                                  @if ($transaction->against_reference_no)
                                      ({{ $transaction->against_reference_no }})
                                  @endif
                                  ({{ formatEnumValue($transaction->purpose) }})
                              </small>
                          @endif
                      </td>
                      <td class="text-right">
                          {{ $transaction->type == 'debit' ? number_format($transaction->amount, 2) : '-' }}</td>
                      <td class="text-right">
                          {{ $transaction->type == 'credit' ? number_format($transaction->amount, 2) : '-' }}</td>
                      <td class="text-right">{{ number_format($balance, 2) }}</td>
                  </tr>
              @endforeach
          @else
              <tr class="ant-table-placeholder">
                  <td colspan="11" class="ant-table-cell text-center">
                      <div class="my-5">
                          <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                              <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                  <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                  </ellipse>
                                  <g fill-rule="nonzero" stroke="#d9d9d9">
                                      <path
                                          d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                      </path>
                                      <path
                                          d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                          fill="#fafafa"></path>
                                  </g>
                              </g>
                          </svg>
                          <p class="ant-empty-description">No data</p>
                      </div>
                  </td>
              </tr>
          @endif
      </tbody>
  </table>

  @section('scripts')
      <script>
          $(function() {
              $('[data-toggle="tooltip"]').tooltip();
          });
      </script>
  @endsection
