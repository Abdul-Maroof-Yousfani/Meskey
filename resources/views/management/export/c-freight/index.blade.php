@extends('management.layouts.master')
@section('title')
    C Freight Management
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">C Freight Management</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('c-freight.create') }}','Add Freight Request',false,'80%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Freight Request
                    </button>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab" aria-controls="pending" aria-selected="true">Pending Requests</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="booked-tab" data-toggle="tab" href="#booked" role="tab" aria-controls="booked" aria-selected="false">Bookings Record</a>
                                </li>
                            </ul>
                            
                            <form id="filterForm" class="form mt-2">
                                <div class="row justify-content-end text-right">
                                    <div class="col-md-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        <input type="text" class="form-control" id="search" placeholder="Search EO or Booking No" name="search" value="">
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-content mt-1">
                            <div class="card-body table-responsive" id="filteredData">
                                <!-- Data will be loaded via AJAX from getList -->
                                <div class="text-center">Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            loadFilteredData();

            $('#search').on('keyup', function() {
                loadFilteredData();
            });
        });

        function loadFilteredData() {
            var formData = $('#filterForm').serialize();
            $.ajax({
                url: "{{ route('get.c-freight') }}",
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#filteredData').html(response);
                    
                    // Re-apply active tab if one was selected
                    var activeTab = $('#myTab .nav-link.active').attr('href');
                    if (activeTab) {
                        $('#filteredData .tab-pane').removeClass('show active');
                        $('#filteredData ' + activeTab).addClass('show active');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }
    </script>
@endsection
