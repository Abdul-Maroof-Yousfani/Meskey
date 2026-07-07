@extends('management.layouts.master')
@section('title')
    Purchase Orders
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Orders</h2>
                </div>

                @canAccess("procurement-raw-purchase-order-create")
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('raw-material.purchase-order.create') }}','Add Purchase Contract (Raw Material)')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Purchase Contract/Order
                    </button>


                    <!-- Export CSV Button -->
                    <button onclick="exportCSV()" type="button" class="btn btn-success position-relative ml-2">
                        <i class="ft-file mr-1"></i> Export CSV
                    </button>
                </div>
                @endcanAccess
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-nd text">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id_f" id="company_location"
                                                        class="form-control ">
                                                        <option value="">Location</option>
                                                        @foreach ($companyLocations as $location)
                                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row justify-content-nd text">
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Suppliers:</label>
                                                    <select name="supplier_id_f" id="supplier_id_f" class="form-control ">
                                                        <option value="">Supplier</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Sauda Type:</label>
                                                    <select name="sauda_type_id_f" id="sauda_type" class="form-control ">
                                                        <option value="">Sauda Type Name</option>
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
                                            <th class="col-sm-2">Contract No</th>
                                            <th class="col-sm-3">Supplier</th>
                                            <th class="col-sm-2">Rate</th>
                                            <th class="col-sm-1">Contract Type</th>
                                            <th class="col-sm-1">Replacement</th>
                                            <th class="col-sm-1">Created</th>
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

        function exportCSV() {
            // Show processing SweetAlert
            Swal.fire({
                title: 'Processing Export...',
                html: `
                                    <div style="margin: 20px 0;">
                                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p style="margin-top: 15px; color: #6c757d;">
                                            Please wait while we prepare your data...
                                        </p>
                                    </div>
                                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                // showCancelButton: true,
                // cancelButtonText: 'Cancel',
                // cancelButtonColor: '#dc3545',
            });

            // Get filter values
            const params = {};
            $('#filterForm').serializeArray().forEach(item => {
                if (item.value) {
                    params[item.name] = item.value;
                }
            });

            const queryString = new URLSearchParams(params).toString();

            // Use AJAX to download file
            $.ajax({
                url: `{{ route('raw-material.purchase-order.export-csv') }}?${queryString}`,
                type: 'GET',
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (response, status, xhr) {
                    // Close SweetAlert
                    Swal.close();

                    // Get filename from headers
                    const disposition = xhr.getResponseHeader('content-disposition');
                    let filename = 'purchase-orders-' + new Date().toISOString().split('T')[0] + '.csv';

                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        filename = disposition.split('filename=')[1].replace(/["']/g, '');
                    }

                    // Auto download
                    const url = window.URL.createObjectURL(response);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Download Complete!',
                        text: 'Your file has been downloaded successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function (xhr, status, error) {
                    // Close SweetAlert
                    Swal.close();

                    let errorMessage = 'An error occurred while generating the export.';

                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) { }

                    Swal.fire({
                        icon: 'error',
                        title: 'Export Failed',
                        text: errorMessage,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Try Again'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            exportCSV();
                        }
                    });
                }
            });
        }
        $(document).ready(function () {
            filterationCommon(`{{ route('raw-material.get.purchase-order') }}`);

            initializeDynamicSelect2('#sauda_type', 'sauda_types', 'name', 'id', true, false, true, true);

            initializeDynamicDependentSelect2(
                '#company_location',
                '#supplier_id_f',
                'company_locations',
                'name',
                'id',
                'suppliers',
                'company_location_ids',
                'name',
                true,
                false,
                true,
                true,
            );




        });
    </script>
@endsection