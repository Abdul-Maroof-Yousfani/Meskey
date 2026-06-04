@extends('management.layouts.master')
@section('title')
    All Notifications
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">All Notifications</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button class="btn btn-primary mark-all-read-btn position-relative">
                        Mark All as Read
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
                                            <div class="col-md-3">
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
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Notification</th>
                                            <th class="text-right">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
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
            filterationCommon(`{{ route('notifications.getList') }}`);

            // The mark-all-read-btn logic is already handled in header.blade.php globally,
            // but we can refresh the list if needed
            $(document).on('click', '.mark-all-read-btn', function() {
                setTimeout(function() {
                    filterationCommon(`{{ route('notifications.getList') }}`);
                }, 500); // give time for the header ajax to finish
            });
        });
    </script>
@endsection
