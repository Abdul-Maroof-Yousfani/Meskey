@extends('management.layouts.master')
@section('title')
    Purchase Order
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Order</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-order.create') }}','Add Purchase Order',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Purchase Order
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="po_no" class="form-label">PO No</label>
                                        <input type="text" class="form-control" id="po_no"
                                            placeholder="PO" name="po_no"
                                            value="{{ request('po_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="pr_no" class="form-label">PR No</label>
                                        <input type="text" class="form-control" id="pr_no"
                                            placeholder="PR" name="pr_no"
                                            value="{{ request('pr_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="pq_no" class="form-label">PQ No</label>
                                        <input type="text" class="form-control" id="pq_no"
                                            placeholder="PQ" name="pq_no"
                                            value="{{ request('pq_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select name="category_id" id="category_id" class="form-control select2">
                                            <option value="all">All</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="filter_supplier_id" class="form-label">Vendor</label>
                                        <select name="supplier_id" id="filter_supplier_id" class="form-control select2">
                                            <option value="all">All</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="px-1 text-left" style="width: 8%;">
                                        <label for="qty" class="form-label">Qty</label>
                                        <input type="text" class="form-control" id="qty"
                                            placeholder="Qty" name="qty"
                                            value="{{ request('qty', '') }}">
                                    </div>

                                    <div class="px-1 text-left" style="width: 6%;">
                                        <label for="rate" class="form-label">Rate</label>
                                        <input type="text" class="form-control" id="rate"
                                            placeholder="Rate" name="rate"
                                            value="{{ request('rate', '') }}">
                                    </div>

                                    <div class="px-1 text-left" style="width: 7%;">
                                        <label for="amount" class="form-label">Amt</label>
                                        <input type="text" class="form-control" id="amount"
                                            placeholder="Amt" name="amount"
                                            value="{{ request('amount', '') }}">
                                    </div>

                                    <div class="px-1 text-left" style="width: 7%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control p-0">
                                            <option value="all">All</option>
                                            <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                                            <option value="approved" @selected(request('status') == 'approved')>Approved</option>
                                            <option value="rejected" @selected(request('status') == 'rejected')>Rejected</option>
                                            <option value="reverted" @selected(request('status') == 'reverted')>Reverted</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 7%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search" name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                </div>
                            </form>

                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0" style="table-layout: fixed; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%;">Order No</th>
                                            <th style="width: 10%;">Request No</th>
                                            <th style="width: 10%;">Quotation No</th>
                                            <th style="width: 25%;">Category- item</th>
                                            <th style="width: 10%;">Supplier</th>
                                            <th style="width: 7%;">Qty</th>
                                            <th style="width: 6%;">Rate</th>
                                            <th style="width: 8%;">Amount</th>
                                            <th style="width: 7%;">Status</th>
                                            <th style="width: 7%;">Action</th>
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
        $(document).ready(function () {
            $('.select2').select2();
            filterationCommon(`{{ route('store.get.purchase-order') }}`);

            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('.select2').select2();
            });

            // Trigger filtering automatically on select change
            $(document).on('change', '#category_id, #filter_supplier_id', function() {
                filterationCommon(`{{ route('store.get.purchase-order') }}`);
            });
        });
    </script>
@endsection