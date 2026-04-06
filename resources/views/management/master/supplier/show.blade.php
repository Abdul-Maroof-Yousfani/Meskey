@extends('management.layouts.master')
@section('title', 'Supplier Details')
@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Supplier Details: {{ $supplier->company_name }}</h4>
                    <a href="{{ route('supplier.index') }}" class="btn btn-secondary">Back to List</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Unique No:</strong> {{ $supplier->unique_no }}</div>
                        <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($supplier->status) }}</div>
                        <div class="col-md-4"><strong>Type:</strong> {{ str_replace('_', ' ', ucfirst($supplier->type)) }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Owner Name:</strong> {{ $supplier->owner_name }}</div>
                        <div class="col-md-4"><strong>Mobile:</strong> {{ $supplier->owner_mobile_no }}</div>
                        <div class="col-md-4"><strong>CNIC:</strong> {{ $supplier->owner_cnic_no }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12"><strong>Address:</strong> {{ $supplier->address ?: 'N/A' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Email:</strong> {{ $supplier->email ?: 'N/A' }}</div>
                        <div class="col-md-4"><strong>Phone:</strong> {{ $supplier->phone ?: 'N/A' }}</div>
                        <div class="col-md-4"><strong>NTN:</strong> {{ $supplier->ntn ?: 'N/A' }}</div>
                    </div>

                    <h5 class="mt-4 border-bottom pb-2">Bank Details</h5>
                    <div class="row mt-2">
                        <div class="col-md-6 border-right">
                            <h6>Company Bank Detail</h6>
                            @forelse($supplier->companyBankDetails as $bank)
                                <div class="mb-2 p-2 bg-light border">
                                    <div><strong>Bank:</strong> {{ $bank->bank_name }}</div>
                                    <div><strong>Branch:</strong> {{ $bank->branch_name }} ({{ $bank->branch_code }})</div>
                                    <div><strong>Title:</strong> {{ $bank->account_title }}</div>
                                    <div><strong>Acc No:</strong> {{ $bank->account_number }}</div>
                                </div>
                            @empty
                                <p class="text-muted">No company bank detail available.</p>
                            @endforelse
                        </div>
                        <div class="col-md-6">
                            <h6>Owner Bank Detail</h6>
                            @forelse($supplier->ownerBankDetails as $bank)
                                <div class="mb-2 p-2 bg-light border">
                                    <div><strong>Bank:</strong> {{ $bank->bank_name }}</div>
                                    <div><strong>Branch:</strong> {{ $bank->branch_name }} ({{ $bank->branch_code }})</div>
                                    <div><strong>Title:</strong> {{ $bank->account_title }}</div>
                                    <div><strong>Acc No:</strong> {{ $bank->account_number }}</div>
                                </div>
                            @empty
                                <p class="text-muted">No owner bank detail available.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
