@extends('management.layouts.master')
@section('title')
    Output QC Alerts
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-12">
                    <h2 class="page-title">Output QC Alerts</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-3 text-left">
                                        <label for="custom_date_range" class="form-label">Date</label>
                                        <input type="text" class="form-control" name="date_range" id="custom_date_range" value="{{ date('Y-m-d', strtotime('-30 days')) }} - {{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3 text-left">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2-filter">
                                            <option value="">All</option>
                                            <option value="pending">Pending Remarks</option>
                                            <option value="resolved">Remarks Submitted</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 text-left">
                                        <label for="plant_ids" class="form-label">Plant</label>
                                        <select name="plant_ids[]" id="plant_ids" class="form-control select2-filter" multiple data-placeholder="Select Plant(s)">
                                            @foreach($plants as $plant)
                                                <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData" style="margin-top: 30px;">
                                {{-- Loaded via AJAX --}}
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
            filterationCommon(`{{ route('get.production-output-alert') }}`)

            $('.select2-filter').select2({
                width: '100%'
            });

            $('#custom_date_range').daterangepicker({
                locale: { format: 'YYYY-MM-DD' },
                autoUpdateInput: true
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#status').trigger('change');
            });

            // Opened from a notification: show that alert right away
            var viewId = new URLSearchParams(window.location.search).get('view_id');
            if (viewId) {
                setTimeout(function () {
                    openModal(null, "{{ url('production/production-output-alert') }}/" + viewId, 'Output QC Alert');
                }, 400);
            }
        });
    </script>
@endsection
