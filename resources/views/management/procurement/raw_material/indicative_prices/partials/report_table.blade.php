<table class="table table-bordered">
    <thead>
        <tr>
            <th rowspan="2">Commodity Name</th>
            <th rowspan="2">Location</th>
            <th rowspan="2">Type</th>
            <th rowspan="2">Crop Year</th>
            <th rowspan="2">Delivery Condition</th>
            <th colspan="2" class="text-center">Details</th>
            <th colspan="2" class="text-center">Cash</th>
            <th colspan="2" class="text-center">Credit</th>
            <th rowspan="2">Others</th>
            <th rowspan="2">Remarks</th>
        </tr>
        <tr>
            <th>Time</th>
            <th>Purchaser</th>
            <th>Rate</th>
            <th>Days</th>
            <th>Rate</th>
            <th>Days</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reports as $report)
            <tr data-commodity="{{ $report['commodity_name'] }}" data-location="{{ $report['location_name'] }}">
                @if ($report['show_commodity'])
                    <td rowspan="{{ $report['commodity_rowspan'] }}" class="align-middle text-center commodity-cell"
                        data-commodity="{{ $report['commodity_name'] }}">
                        {{ $report['commodity_name'] }}
                    </td>
                @endif

                @if ($report['show_location'])
                    <td rowspan="{{ $report['location_rowspan'] }}" class="align-middle location-cell"
                        data-commodity="{{ $report['commodity_name'] }}" data-location="{{ $report['location_name'] }}">
                        {{ $report['location_name'] }}
                    </td>
                @endif

                <td>{{ $report['type'] }}</td>
                <td>{{ $report['crop_year'] }}</td>
                <td>{{ $report['delivery_condition'] }}</td>
                <td>{{ $report['time'] }}</td>
                <td>{{ $report['purchaser'] }}</td>
                <td>{{ $report['cash_rate'] }}</td>
                <td>{{ $report['cash_days'] }}</td>
                <td>{{ $report['credit_rate'] }}</td>
                <td>{{ $report['credit_days'] }}</td>
                <td>{{ $report['others'] }}</td>
                <td>{{ $report['remarks'] }}</td>
            </tr>
        @empty
            <tr class="ant-table-placeholder">
                <td colspan="14" class="ant-table-cell text-center">
                    <div class="my-5">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                </ellipse>
                                <g fill-rule="nonzero" stroke="#d9d9d9">
                                    <path
                                        d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                    </path>
                                    <path
                                        d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                        fill="#fafafa"></path>
                                </g>
                            </g>
                        </svg>
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
