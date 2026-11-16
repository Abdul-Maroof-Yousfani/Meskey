{{-- Sales dashboard boxes (same card markup/classes as the arrival dashboard).
     Box list and queries live in App\Services\SalesDashboardService. --}}
@php
    $listParams = http_build_query(array_filter([
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'location_id' => $location_id ?? null,
    ]));
@endphp

@foreach (\App\Services\SalesDashboardService::GROUPS as $group => [$groupTitle, $groupIcon])
    @continue(empty($salesBoxes[$group]))

    <div class="col-12">
        <h6 class="text-uppercase text-muted font-weight-bold mt-1 mb-1">
            <i class="{{ $groupIcon }}"></i> {{ $groupTitle }}
        </h6>
        <div class="dashboard-cards-grid mb-2">
            @foreach ($salesBoxes[$group] as $type => $box)
                @php
                    $value = $data[$type] ?? 0;
                    $tone = isset($box['tone']) && $value > 0 ? $box['tone'] : null;
                    $url = route('dashboard.sales.list-data') . '?type=' . $type . ($listParams ? '&' . $listParams : '');
                @endphp
                <div class="dashboard-card {{ $tone ? 'bg-' . $tone . '-light' : '' }}"
                    onclick="openModal(this,'{{ $url }}','{{ $box['title'] }}', true, '85%')">
                    <div class="card-icon">
                        <i class="{{ $box['icon'] }} {{ $tone ? 'text-' . $tone : '' }}"></i>
                    </div>
                    <div class="card-number {{ $tone ? 'text-' . $tone : '' }}">
                        @if (isset($box['sum']))
                            {{ number_format($value / 1000, 2) }} MT
                        @else
                            {{ $value }}
                        @endif
                    </div>
                    <div class="card-title">{{ $box['title'] }}</div>
                    <div class="card-subtitle">{{ $box['subtitle'] }}</div>

                    <button class="view-btn"
                        onclick="openModal(this,'{{ $url }}','{{ $box['title'] }}', true, '85%')">
                        View
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
