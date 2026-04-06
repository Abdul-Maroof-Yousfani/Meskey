@extends('management.layouts.master')
@section('title')
    Goods Received Note
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Goods Received Note</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-order-receiving.create') }}','Add GRN',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create GRN
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0 align-items-end flex-nowrap" style="overflow-x: auto; padding-bottom: 10px;">
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="receiving_no" class="form-label text-nowrap">GRN No</label>
                                        <input type="text" class="form-control" name="search" id="receiving_no" placeholder="GRN No">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="pr_no" class="form-label text-nowrap">PR No</label>
                                        <input type="text" class="form-control" name="purchase_request_no" id="pr_no" placeholder="PR No">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="po_no" class="form-label text-nowrap">PO No</label>
                                        <input type="text" class="form-control" name="purchase_order_no" id="po_no" placeholder="PO No">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="dc_no" class="form-label text-nowrap">DC No</label>
                                        <input type="text" class="form-control" name="dc_no" id="dc_no" placeholder="DC No">
                                    </div>

                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="category_id" class="form-label">Item / Category</label>
                                        <select name="category_id" id="category_id" class="form-control select2">
                                            <option value="">All Categories</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="filter_supplier_id" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="filter_supplier_id" class="form-control select2">
                                            <option value="all">All Vendors</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="qty" class="form-label">Qty</label>
                                        <input type="number" class="form-control" name="qty" id="qty" placeholder="Qty">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="rate" class="form-label">Rate</label>
                                        <input type="number" class="form-control" name="rate" id="rate" placeholder="Rate">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="total" class="form-label">Total</label>
                                        <input type="number" class="form-control" name="total" id="total" placeholder="Total">
                                    </div>

                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="qc_status" class="form-label">QC Status</label>
                                        <select name="qc_status" id="qc_status" class="form-control select2">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData" style="overflow-x: auto;">
                                <table class="table m-0" style="width: 100%; min-width: 1710px;">
                                    <thead>
                                        <tr>
                                            <th style="width: 180px; min-width: 180px;">PO Receiving No</th>
                                            <th style="width: 180px; min-width: 180px;">Purchase Request No</th>
                                            <th style="width: 180px; min-width: 180px;">Purchase Order No</th>
                                            <th style="width: 100px; min-width: 100px;">DC No</th>
                                            <th style="width: 250px; min-width: 250px;">Category- item</th>
                                            <th style="width: 200px; min-width: 200px;">Supplier</th>
                                            <th style="width: 90px; min-width: 90px;">Qty</th>
                                            <th style="width: 90px; min-width: 90px;">Rate</th>
                                            <th style="width: 100px; min-width: 100px;">Total Amount</th>
                                            <th style="width: 110px; min-width: 110px;">QC</th>
                                            <th style="width: 110px; min-width: 110px;">QC Status</th>
                                            <th style="min-width: 120px;">Action</th>
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
            filterationCommon(`{{ route('store.get.purchase-order-receiving') }}`);

            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('.select2').select2();
            });

            // Trigger filtering automatically on select change
            $(document).on('change', '#category_id, #filter_supplier_id, #qc_status', function() {
                filterationCommon(`{{ route('store.get.purchase-order-receiving') }}`);
            });
        });
    </script>
@endsection