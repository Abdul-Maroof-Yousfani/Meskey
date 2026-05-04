@extends('management.layouts.master')
@section('title') Quotation @endsection
@section('content')
<div class="content-wrapper">
    <section id="extended">
        <div class="row w-100 mx-auto">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title">Quotations</h2>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                <button onclick="openModal(this,'{{ route('quotation.create') }}','Add Quotation',false,'90%')"
                    type="button" class="btn btn-primary position-relative">
                    Create Quotation
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
                                    <div class="row justify-content-end text-right">
                                        <div class="col-md-2">
                                            <label for="customers" class="form-label">Search</label>
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                            <input type="text" class="form-control" id="search"
                                                placeholder="Search here" name="search" value="">
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
                                        <th width="5%">S no.</th>
                                        <th width="10%">Exp. Sauda</th>
                                        <th width="15%">Buyer</th>
                                        <th width="15%">Company</th>
                                        <th width="15%">Product</th>
                                        <th width="10%">Currency</th>
                                        {{-- <th width="10%">Rate/MT</th> --}}
                                        <th width="10%">Total Amount</th>
                                        <th width="10%">Status</th>
                                        <th width="10%">Action</th>
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
        filterationCommon(`{{ route('get.quotation') }}`)
    });
</script>
@endsection
