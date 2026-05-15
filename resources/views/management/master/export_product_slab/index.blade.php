@extends('management.layouts.master')
@section('title')
    Export Product Slabs
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-md-6">
                    <h2 class="page-title">Export Product Slabs</h2>
                </div>
                <div class="col-md-6 text-right">
                    <button onclick="openModal(this,'{{ route('export-product-slab.create') }}','Add Export Product Slab')" type="button" class="btn btn-primary position-relative">
                        Create Export Product Slab
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-left">
                                            <div class="col-md-2 d-none">
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Products:</label>
                                                    <select class="form-control" name="product_id" id="product_id">
                                                        <option value="">Select Product</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Slab Types:</label>
                                                    <select class="form-control" name="product_slab_type_id" id="product_slab_type_id">
                                                        <option value="">Select Slab Types</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Product</th>
                                            <th class="col-sm-5">Slab Types</th>
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
            filterationCommon(`{{ route('get.export-product-slab') }}`);
            initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
            initializeDynamicSelect2('#product_slab_type_id', 'product_slab_types', 'name', 'id', false, false);
        });
    </script>
@endsection
