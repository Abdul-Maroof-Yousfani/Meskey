{{--
    Notifications page ka compact pagination: 1 2 3 4 … 10
    (global vendor/pagination/bootstrap-5 ko nahi chheda, wo poori app me use hota hai)
    #paginationLinks a  -> scripts.js page change karta hai
    #per_page           -> scripts.js per-page change karta hai (id/name same rehne chahiye)
--}}
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // kaun se page numbers dikhane hain
    $pages = [1, $last, $current - 1, $current, $current + 1];
    if ($current <= 3) {
        $pages = array_merge($pages, [2, 3, 4]);
    }
    if ($current >= $last - 2) {
        $pages = array_merge($pages, [$last - 3, $last - 2, $last - 1]);
    }
    $pages = array_values(array_unique(array_filter($pages, fn($p) => $p >= 1 && $p <= $last)));
    sort($pages);

    $perPage = (int) request('per_page', 25);
@endphp

<div class="nt-pager">
    <div class="nt-pager-info">
        @if ($paginator->total() > 0)
            Showing <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b>
            of <b>{{ $paginator->total() }}</b>
        @else
            No results found
        @endif
    </div>

    <div class="nt-pager-right">
        @if ($paginator->hasPages())
            {{-- data-*: index.blade.php ka JS screen ke hisaab se numbers dobara banata hai
                 (jagah kam ho to kam numbers). Server wala render JS na chale to fallback hai. --}}
            <ul class="pagination nt-pages" data-current="{{ $current }}" data-last="{{ $last }}"
                data-url="{{ $paginator->url(987654321) }}">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-hidden="true"><i class="ft-chevron-left"></i></span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($current - 1) }}" rel="prev"
                            aria-label="@lang('pagination.previous')"><i class="ft-chevron-left"></i></a>
                    </li>
                @endif

                {{-- Numbers with … gaps --}}
                @php $prev = 0; @endphp
                @foreach ($pages as $page)
                    @if ($prev && $page - $prev == 2)
                        {{-- sirf ek page beech me chhoot raha ho to "…" ki jagah wahi number dikhao --}}
                        <li class="page-item nt-num"><a class="page-link" href="{{ $paginator->url($prev + 1) }}">{{ $prev + 1 }}</a></li>
                    @elseif ($prev && $page - $prev > 2)
                        <li class="page-item disabled nt-gap nt-num" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                    @endif

                    @if ($page == $current)
                        <li class="page-item active nt-num" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item nt-num"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                    @endif
                    @php $prev = $page; @endphp
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($current + 1) }}" rel="next"
                            aria-label="@lang('pagination.next')"><i class="ft-chevron-right"></i></a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-hidden="true"><i class="ft-chevron-right"></i></span>
                    </li>
                @endif
            </ul>
        @endif

        {{-- Per page --}}
        <div class="nt-perpage">
            <select name="per_page" id="per_page" class="form-control" aria-label="Items per page">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                @endforeach
            </select>
            <i class="ft-chevron-down"></i>
        </div>
    </div>
</div>
