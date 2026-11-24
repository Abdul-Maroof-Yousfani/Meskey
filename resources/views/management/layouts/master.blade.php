@include('management/layouts/header')
<div class="main-panel">
    <div class="wrapper">
        @if (getCurrentCompany())
            @include('management/layouts/navigation')
        @endif
        <!-- BEGIN : Main Content-->
        <div class="main-content py-3">
            <div class="content-overlay"></div>
            @yield('content')
            {{-- Mobile page header: har page ka "page-title" header mobile par app-bar (desktop par kuch nahi) --}}
            @include('management.layouts.partials.mobile-page-head')
        </div>
    </div>
</div>
@include('management/layouts/footer')
@include('management/layouts/snippets/script')

@yield('scripts')
@include('management/layouts/modals')
@include('management/layouts/secondary-modal')
