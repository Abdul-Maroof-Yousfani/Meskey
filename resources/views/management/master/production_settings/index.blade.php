@extends('management.layouts.master')
@section('title')
Production Settings
@endsection
@section('content')
<div class="content-wrapper">
    <section id="extended">
        <div class="row w-100 mx-auto mb-2">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"><i class="ft-settings mr-2"></i> Production Settings</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom pb-0">
                        <ul class="nav nav-tabs card-header-tabs" id="productionSettingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="fumigation-types-tab" data-toggle="tab" href="#fumigation-types" role="tab" aria-controls="fumigation-types" aria-selected="true">
                                    <i class="ft-shield mr-1"></i> Fumigation Types
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="tab-content" id="productionSettingsTabContent">
                                <div class="tab-pane fade show active" id="fumigation-types" role="tabpanel" aria-labelledby="fumigation-types-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 text-muted">Manage Fumigation Types</h5>
                                        <button onclick="openModal(this, '{{ route('production-settings.fumigation-types.create') }}', 'Add Fumigation Type')"
                                            type="button" class="btn btn-primary position-relative">
                                            <i class="ft-plus mr-1"></i> Add Fumigation Type
                                        </button>
                                    </div>

                                    <div class="table-responsive" id="filteredData">
                                        @include('management.master.production_settings.partials.fumigation_types_table', ['fumigationTypes' => $fumigationTypes])
                                    </div>
                                </div>
                            </div>
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
    function reloadFumigationTypes() {
        filterationCommon("{{ route('production-settings.fumigation-types.list') }}");
    }
</script>
@endsection
