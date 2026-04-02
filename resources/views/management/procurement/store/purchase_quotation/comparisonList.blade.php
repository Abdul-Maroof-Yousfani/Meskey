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
                        onclick="openModal(this,'{{ route('store.purchase-quotation.create') }}','Add Purchase Quotation',false,'90%')"
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
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="pr_no" class="form-label">PR No</label>
                                        <input type="text" class="form-control" id="pr_no"
                                            placeholder="PR No" name="pr_no"
                                            value="{{ request('pr_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="pq_no" class="form-label">PQ No</label>
                                        <input type="text" class="form-control" id="pq_no"
                                            placeholder="PQ No" name="pq_no"
                                            value="{{ request('pq_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="item_id" class="form-label">Item</label>
                                        <select name="item_id" id="item_id" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="filter_supplier_id" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="filter_supplier_id" class="form-control select2">
                                            <option value="all">All Suppliers</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 8%;">
                                        <label for="uom_id" class="form-label">UOM</label>
                                        <select name="uom_id" id="uom_id" class="form-control select2">
                                            <option value="all">All</option>
                                            @foreach($uoms as $uom)
                                                <option value="{{ $uom->id }}" @selected(request('uom_id') == $uom->id)>
                                                    {{ $uom->name }}
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
                                    <div class="px-1 text-left" style="width: 8%;">
                                        <label for="rate" class="form-label">Rate</label>
                                        <input type="text" class="form-control" id="rate"
                                            placeholder="Rate" name="rate"
                                            value="{{ request('rate', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 8%;">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="amount"
                                            placeholder="Amount" name="amount"
                                            value="{{ request('amount', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 8%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search" name="search"
                                            value="{{ request('search', '') }}">
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                    </div>
                                </div>
                            </form>
                            {{-- <a href="{{ route('export-roles') }}" class="btn btn-warning">Export Roles</a> --}}
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0" style="table-layout: fixed; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">PR No</th>
                                            <th style="width: 12%;">PQ No</th>
                                            <th style="width: 18%;">Category - Item</th>
                                            <th style="width: 18%;">Supplier</th>
                                            <th style="width: 8%;">UOM</th>
                                            <th style="width: 8%;">Qty</th>
                                            <th style="width: 8%;">Rate</th>
                                            <th style="width: 8%;">Amount</th>
                                            <th style="width: 8%;">Action</th>
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
            filterationCommon(`{{ route('store.purchase-quotation.comparison') }}`)

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