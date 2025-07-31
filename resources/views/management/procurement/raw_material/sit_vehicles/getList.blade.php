 <table class="table m-0">
     <thead>
         <tr>
             <th class="col-sm-2 col-md-2 col-lg-2">Contract No.</th>
             <th class="col-sm-2 col-md-2 col-lg-2">Supplier</th>
             <th class="col-sm-2 col-md-2 col-lg-2">Broker</th>
             <th class="col-sm-2 col-md-2 col-lg-2">Product</th>
             <th class="col-sm-2 col-md-2 col-lg-2">Vehicle No</th>
             <th class="col-sm-1 col-md-1 col-lg-1">Status</th>
             <th class="col-sm-1 col-md-1 col-lg-1">Action</th>
         </tr>
     </thead>
     <tbody>
         @if (count($purchaseTickets) != 0)
             @foreach ($purchaseTickets as $ticket)
                 @php
                     $poHours = 0;
                     $purchaseFreightCreatedAt = null;
                     $allowedTime = null;
                     $freightCreated = null;
                     $isNowGreaterThanAllowedTime = null;

                     if ($ticket->purchaseOrder && $ticket->purchaseOrder->division) {
                         $poHours = $ticket->purchaseOrder->division->hours ?? 0;
                         $purchaseFreightCreatedAt = $ticket->purchaseFreight->created_at ?? null;

                         if ($purchaseFreightCreatedAt) {
                             $freightCreated = \Carbon\Carbon::parse($purchaseFreightCreatedAt);
                             $allowedTime = $freightCreated->copy()->addHours($poHours);

                             $isNowGreaterThanAllowedTime = \Carbon\Carbon::now()->greaterThan($allowedTime);
                         } else {
                             $isNowGreaterThanAllowedTime = null;
                         }
                     } else {
                         $isNowGreaterThanAllowedTime = null;
                     }
                 @endphp
                 <tr class="{{ $isNowGreaterThanAllowedTime ? 'bg-red' : '' }}">
                     <td>
                         #{{ $ticket->purchaseOrder->contract_no ?? 'N/A' }}
                         <br>#{{ $ticket->unique_no ?? 'N/A' }}
                         @if ($isNowGreaterThanAllowedTime)
                             <span style="cursor:pointer; margin-left:5px;" data-toggle="tooltip" data-placement="top"
                                 title="The arrival for this vehicle has not been recorded yet.">
                                 <i class="fa fa-exclamation-triangle text-warning"></i>
                             </span>
                         @endif
                     </td>
                     <td> {{ $ticket->purchaseOrder->supplier->name ?? 'N/A' }}</td>
                     <td> {{ $ticket->purchaseOrder->broker_one_name ?? ($ticket->purchaseOrder->broker_two_name ?? ($ticket->purchaseOrder->broker_three_name ?? 'N/A')) }}
                     </td>
                     <td> {{ $ticket->purchaseOrder->qcProduct->name ?? 'N/A' }}</td>
                     <td> Truck No: {{ $ticket->purchaseFreight->truck_no ?? 'N/A' }} <br> Bilty No:
                         {{ $ticket->purchaseFreight->bilty_no ?? 'N/A' }}</td>
                     <td>
                         <span class="badge badge-{{ $ticket->freight_status == 'pending' ? 'warning' : 'success' }}">
                             {{ ucfirst($ticket->freight_status ?? 'Pending') }}
                         </span>
                     </td>
                     <td>
                         <a onclick="openModal(this,'{{ route('raw-material.sit-vehicle.edit', [$ticket->id]) }}','Manage SIT Vehicle', true)"
                             class="success p-1 text-center mr-2 position-relative">
                             <i class="ft-edit font-medium-3"></i>
                         </a>
                     </td>
                 </tr>
             @endforeach
         @else
             <tr class="ant-table-placeholder">
                 <td colspan="7" class="ant-table-cell text-center">
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

 <div class="row d-flex" id="paginationLinks">
     <div class="col-md-12 text-right">
         {{ $purchaseTickets->links() }}
     </div>
 </div>
 <script>
     $(function() {
         $('[data-toggle="tooltip"]').tooltip();
     });
 </script>
