@extends('management.layouts.master')
@section('title')
    Roles
@endsection
@section('content')
    <div class="content-wrapper select-companies-page">
        <div class="row match-height">

            <div class="col-12 col-md-6 col-lg-4 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Companies</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body pt-1">
                            <p>Please choose a company to proceed.</p>
                            <ul class="list-group">
                                @foreach ($companies->sortByDesc(function ($company) {
            return $company->id == auth()->user()->current_company_id;
        }) as $key => $row)
                                    <li class="list-group-item my-1 ">
                                        <a {{ auth()->user()->current_company_id == $row->id ? 'disabled' : '' }}
                                            href="{{ route('select.company', $row->app_key) }}">
                                            <div class="row align-items-center">
                                                <div class="col-10">
                                                    <span class="badge badge-primary mr-2 avat0"
                                                        style="background:#27489a40;">
                                                        <img class="" width="40px" height="40px" style="object-fit: contain;"
                                                            src="{{ image_path($row->logo) }}">
                                                    </span>
                                                    {{ $row->name }}
                                                </div>
                                                <div class="col-2 ">
                                                    <i class="{{ auth()->user()->current_company_id == $row->id ? 'ft-check active' : 'ft-arrow-up-right primary' }}"
                                                        style="font-size: 30px;"></i>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
    <style>
        body {
            background-image: linear-gradient(43deg, rgba(105, 105, 105, 0.02) 0%, rgba(105, 105, 105, 0.02) 62%, rgba(227, 227, 227, 0.02) 62%, rgba(227, 227, 227, 0.02) 69%, rgba(24, 24, 24, 0.02) 69%, rgba(24, 24, 24, 0.02) 80%, rgba(13, 13, 13, 0.02) 80%, rgba(13, 13, 13, 0.02) 82%, rgba(13, 13, 13, 0.02) 82%, rgba(13, 13, 13, 0.02) 100%), linear-gradient(31deg, rgba(155, 155, 155, 0.02) 0%, rgba(155, 155, 155, 0.02) 29%, rgba(164, 164, 164, 0.02) 29%, rgba(164, 164, 164, 0.02) 41%, rgba(200, 200, 200, 0.02) 41%, rgba(200, 200, 200, 0.02) 74%, rgba(229, 229, 229, 0.02) 74%, rgba(229, 229, 229, 0.02) 79%, rgba(15, 15, 15, 0.02) 79%, rgba(15, 15, 15, 0.02) 100%), linear-gradient(319deg, rgba(39, 39, 39, 0.02) 0%, rgba(39, 39, 39, 0.02) 17%, rgba(49, 49, 49, 0.02) 17%, rgba(49, 49, 49, 0.02) 25%, rgba(59, 59, 59, 0.02) 25%, rgba(59, 59, 59, 0.02) 32%, rgba(194, 194, 194, 0.02) 32%, rgba(194, 194, 194, 0.02) 46%, rgba(220, 220, 220, 0.02) 46%, rgba(220, 220, 220, 0.02) 100%), linear-gradient(113deg, rgba(188, 188, 188, 0.02) 0%, rgba(188, 188, 188, 0.02) 15%, rgba(223, 223, 223, 0.02) 15%, rgba(223, 223, 223, 0.02) 46%, rgba(21, 21, 21, 0.02) 46%, rgba(21, 21, 21, 0.02) 88%, rgba(93, 93, 93, 0.02) 88%, rgba(93, 93, 93, 0.02) 94%, rgba(130, 130, 130, 0.02) 94%, rgba(130, 130, 130, 0.02) 100%), linear-gradient(29deg, rgba(134, 134, 134, 0.02) 0%, rgba(134, 134, 134, 0.02) 27%, rgba(181, 181, 181, 0.02) 27%, rgba(181, 181, 181, 0.02) 41%, rgba(81, 81, 81, 0.02) 41%, rgba(81, 81, 81, 0.02) 46%, rgba(253, 253, 253, 0.02) 46%, rgba(253, 253, 253, 0.02) 58%, rgba(74, 74, 74, 0.02) 58%, rgba(74, 74, 74, 0.02) 100%), linear-gradient(90deg, #e5e5e5, #e4e4e4);
            background-size: cover;
            background-repeat: no-repeat;
        }

        html body.layout-dark:not(.layout-transparent) {

    background-image: linear-gradient(43deg, rgba(105, 105, 105, 0.02) 0%, rgba(105, 105, 105, 0.02) 62%, rgba(227, 227, 227, 0.02) 62%, rgba(227, 227, 227, 0.02) 69%, rgba(24, 24, 24, 0.02) 69%, rgba(24, 24, 24, 0.02) 80%, rgba(13, 13, 13, 0.02) 80%, rgba(13, 13, 13, 0.02) 82%, rgba(13, 13, 13, 0.02) 82%, rgba(13, 13, 13, 0.02) 100%), linear-gradient(31deg, rgba(155, 155, 155, 0.02) 0%, rgba(155, 155, 155, 0.02) 29%, rgba(164, 164, 164, 0.02) 29%, rgba(164, 164, 164, 0.02) 41%, rgba(200, 200, 200, 0.02) 41%, rgba(200, 200, 200, 0.02) 74%, rgba(229, 229, 229, 0.02) 74%, rgba(229, 229, 229, 0.02) 79%, rgba(15, 15, 15, 0.02) 79%, rgba(15, 15, 15, 0.02) 100%), linear-gradient(319deg, rgba(39, 39, 39, 0.02) 0%, rgba(39, 39, 39, 0.02) 17%, rgba(49, 49, 49, 0.02) 17%, rgba(49, 49, 49, 0.02) 25%, rgba(59, 59, 59, 0.02) 25%, rgba(59, 59, 59, 0.02) 32%, rgba(194, 194, 194, 0.02) 32%, rgba(194, 194, 194, 0.02) 46%, rgba(220, 220, 220, 0.02) 46%, rgba(220, 220, 220, 0.02) 100%), linear-gradient(113deg, rgba(188, 188, 188, 0.02) 0%, rgba(188, 188, 188, 0.02) 15%, rgba(223, 223, 223, 0.02) 15%, rgba(223, 223, 223, 0.02) 46%, rgba(21, 21, 21, 0.02) 46%, rgba(21, 21, 21, 0.02) 88%, rgba(93, 93, 93, 0.02) 88%, rgba(93, 93, 93, 0.02) 94%, rgba(130, 130, 130, 0.02) 94%, rgba(130, 130, 130, 0.02) 100%), linear-gradient(29deg, rgba(134, 134, 134, 0.02) 0%, rgba(134, 134, 134, 0.02) 27%, rgba(181, 181, 181, 0.02) 27%, rgba(181, 181, 181, 0.02) 41%, rgba(81, 81, 81, 0.02) 41%, rgba(81, 81, 81, 0.02) 46%, rgba(253, 253, 253, 0.02) 46%, rgba(253, 253, 253, 0.02) 58%, rgba(74, 74, 74, 0.02) 58%, rgba(74, 74, 74, 0.02) 100%), linear-gradient(90deg, #000000, #000000);

}

        .card {

            position: relative;
            background: linear-gradient(135deg, #d3d3d396 0%, #264eab 200%);
            border-radius: 10px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .layout-dark .list-group-item {
            border: 1px solid #f05a2847 !important;
        }

        .list-group-item {
            background-color: transparent;
            border: 2px solid #27489a !important;
            border-radius: 5px;

        }

        .list-group-item a {
            color: #17102F !important;
        }

        .layout-dark .list-group-item a {
            color: #BFBEC2 !important;
        }

        .layout-dark .card {
            background: linear-gradient(135deg, #d3d3d300 0%, #264eabbd 220%) !important;

        }

        i.ft-check.active {
            background: green;
            padding: 5px;
            border-radius: 40px;
            color: white;
            font-size: 25px !important;
        }
    </style>
@endsection
@section('script')
@endsection
