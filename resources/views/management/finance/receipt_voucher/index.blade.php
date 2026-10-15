 @extends('management.layouts.master')
@section('title')
    Receipt Voucher
@endsection
 @section('content')
     <div class="content-wrapper">
         <section id="extended">
             <div class="row w-100 mx-auto">
                 <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Bank/Cash Receipt Voucher</h2>
                 </div>

                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    @canAccess('receipt-voucher-create')
                     <a href="{{ route('receipt-voucher.create') }}" class="btn btn-primary position-relative ">
                        Create Receipt Voucher
                     </a>
                    @endcanAccess
                    @canAccess('direct-receipt-voucher-create')
                    <a href="{{ route('direct.receipt-voucher') }}" class="btn btn-success position-relative">
                        <i class="ft-plus-circle"></i> Create Direct Receipt Voucher
                    </a>
                    @endcanAccess
                </div>
             </div>
             <div class="row">
                 <div class="col-12">
                     <div class="card">
                         <div class="card-header">
                             <form id="filterForm" class="form">
                                 <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                 <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                                 <div class="row mx-0 align-items-end">
                                     <div class="px-1 text-left" style="width: 12%;">
                                         <label for="rv_no" class="form-label">RV No</label>
                                         <input type="text" class="form-control" id="rv_no" name="rv_no"
                                             placeholder="RV No" value="{{ request('rv_no', '') }}">
                                     </div>
                                     <div class="px-1 text-left" style="width: 14%;">
                                         <label for="daterange" class="form-label">Date / Range</label>
                                         <input type="text" class="form-control" id="daterange" name="daterange"
                                             placeholder="Select Date" value="{{ request('daterange', '') }}" autocomplete="off">
                                     </div>
                                     <div class="px-1 text-left" style="width: 12%;">
                                         <label for="type" class="form-label">Type</label>
                                         <select name="type" id="type" class="form-control select2">
                                             <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                                             <option value="bank_payment_voucher" {{ request('type') == 'bank_payment_voucher' ? 'selected' : '' }}>Bank Receipt</option>
                                             <option value="cash_payment_voucher" {{ request('type') == 'cash_payment_voucher' ? 'selected' : '' }}>Cash Receipt</option>
                                         </select>
                                     </div>
                                     <div class="px-1 text-left" style="width: 21%;">
                                         <label for="account_id" class="form-label">Account</label>
                                         <select name="account_id" id="account_id" class="form-control select2">
                                             <option value="all">All Accounts</option>
                                             @foreach ($accounts ?? [] as $acc)
                                                 <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                                     {{ $acc->name }} ({{ $acc->unique_no }})
                                                 </option>
                                             @endforeach
                                         </select>
                                     </div>
                                     <div class="px-1 text-left" style="width: 11%;">
                                         <label for="bill" class="form-label">Bill No</label>
                                         <input type="text" class="form-control" id="bill" name="bill"
                                             placeholder="Bill No" value="{{ request('bill', '') }}">
                                     </div>
                                     <div class="px-1 text-left" style="width: 12%;">
                                         <label for="ref_no" class="form-label">Ref No / Cheque</label>
                                         <input type="text" class="form-control" id="ref_no" name="ref_no"
                                             placeholder="Ref / Cheque" value="{{ request('ref_no', '') }}">
                                     </div>
                                     <div class="px-1 text-left" style="width: 13%;">
                                         <label for="status" class="form-label">Status</label>
                                         <select name="status" id="status" class="form-control select2">
                                             <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                             <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                             <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                             <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                             <option value="reverted" {{ request('status') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                         </select>
                                     </div>
                                     <div class="px-1 text-center" style="width: 5%;">
                                         <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" id="resetFilters" title="Reset Filters" style="height: 38px; width: 100%; padding: 0;">
                                             <i class="ft-rotate-ccw font-medium-1"></i>
                                         </button>
                                     </div>
                                 </div>
                             </form>
                         </div>
                         <div class="card-content">
                             <div class="card-body table-responsive" id="filteredData">
                                 {{-- Filled via AJAX --}}
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </section>
     </div>
 @endsection

 @section('script')
     <script>
         $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

            $('#daterange').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $(this).trigger('change');
            });

            $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $(this).trigger('change');
            });

            filterationCommon(`{{ route('get.receipt-vouchers') }}`);

            $('#filterForm select').on('select2:select select2:clear', function() {
                $(this).trigger('change');
            });

            $('#resetFilters').on('click', function() {
                $('#filterForm')[0].reset();
                $('#filterForm select').val('all').trigger('change.select2').trigger('change');
                $('#filterForm input[type="text"]').val('');
                $('#filterForm input[name="page"]').val(1);
                $('#daterange').val('').trigger('change');
                $('#filterForm input:first').trigger('keyup');
            });
         });
     </script>
 @endsection
