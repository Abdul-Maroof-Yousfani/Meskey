@extends('management.layouts.master')
@section('title')
    Purchase Quotation
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Quotation</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-quotation.create') }}','Add Purchase Quotations',false,'90%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Purchase Quotation
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-2 text-left">
                                                <label for="filter_supplier_id" class="form-label">Supplier</label>
                                                <select name="supplier_id" id="filter_supplier_id" class="form-control select2">
                                                    <option value="all">All Suppliers</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-2 text-left">
                                                <label for="item_id" class="form-label">Item</label>
                                                <select name="item_id" id="item_id" class="form-control select2">
                                                    <option value="all">All Items</option>
                                                    @foreach($items as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-1 text-left">
                                                <label for="uom_id" class="form-label">UOM</label>
                                                <select name="uom_id" id="uom_id" class="form-control select2">
                                                    <option value="all">All</option>
                                                    @foreach($uoms as $uom)
                                                        <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-1 text-left">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control text-sm px-0">
                                                    <option value="all">All</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                    <option value="reverted">Reverted</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="pr_no" class="form-label">PR No</label>
                                                <input type="text" class="form-control" id="pr_no"
                                                    placeholder="PR No" name="pr_no"
                                                    value="{{ request('pr_no', '') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="pq_no" class="form-label">PQ No</label>
                                                <input type="text" class="form-control" id="pq_no"
                                                    placeholder="PQ No" name="pq_no"
                                                    value="{{ request('pq_no', '') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            {{-- <a href="{{ route('export-roles') }}" class="btn btn-warning">Export Roles</a> --}}
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Purchase Quotation No </th>
                                            <th class="col-sm-2">Purchase Quotation Date</th>
                                            <th class="col-sm-2">Location</th>
                                            <th class="col-sm-2">Category</th>
                                            <th class="col-sm-2">Item</th>
                                            <th class="col-sm-2">Item UOM</th>
                                            <th class="col-sm-2">Supplier</th>
                                            {{-- <th class="col-sm-2">Qty</th> --}}
                                            <th class="col-sm-2">Rate</th>
                                            <th class="col-sm-2">Total Amount</th>
                                            {{-- <th class="col-sm-2">Item Status</th> --}}
                                            <th class="col-sm-1">Action</th>
                                        </tr>
                                    </thead>
                                </table>
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
            $('.select2').select2();
            filterationCommon(`{{ route('store.get.purchase-quotation') }}`)

            $('#filter_supplier_id, #item_id, #uom_id, #status').on('change', function(e) {
                if ($(this).data('updating')) return;

                let supplier_id = $('#filter_supplier_id').val();
                let item_id = $('#item_id').val();
                let uom_id = $('#uom_id').val();
                let status = $('#status').val();

                $.ajax({
                    url: "{{ route('store.purchase-quotation.filtered-options') }}",
                    type: "GET",
                    data: { supplier_id, item_id, uom_id, status },
                    success: function(response) {
                        updateDropdown('#item_id', response.items, item_id, 'Items');
                        updateDropdown('#filter_supplier_id', response.suppliers, supplier_id, 'Suppliers');
                        updateDropdown('#uom_id', response.uoms, uom_id, 'UOMs');
                    }
                });
            });

            function updateDropdown(selector, options, selectedValue, label) {
                let $el = $(selector);
                $el.data('updating', true);
                $el.empty().append(`<option value="all">All ${label}</option>`);
                options.forEach(opt => {
                    $el.append(`<option value="${opt.id}" ${opt.id == selectedValue ? 'selected' : ''}>${opt.name}</option>`);
                });
                $el.trigger('change.select2');
                $el.data('updating', false);
            }
        });
    </script>
@endsection
