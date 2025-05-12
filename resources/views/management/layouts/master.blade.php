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
        </div>
    </div>
</div>
@include('management/layouts/footer')
@include('management/layouts/snippets/script')

@yield('scripts')
@include('management/layouts/modals')
