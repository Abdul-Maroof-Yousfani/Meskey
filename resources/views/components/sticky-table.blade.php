<div class="sticky-table-container" data-left-sticky="{{ $leftSticky }}" data-right-sticky="{{ $rightSticky }}">
    <div class="sticky-table-scroller">
        <table class="table sticky-table">
            <thead>
                <tr>
                    {{ $head }}
                </tr>
            </thead>
            <tbody>
                @if (count($items) > 0)
                    {{ $body }}
                @else
                    <tr class="ant-table-placeholder">
                        <td colspan="{{ count(explode('</th>', $head)) }}" class="ant-table-cell text-center">
                            <div class="my-5">
                                <svg width="64" height="41" viewBox="0 0 64 41"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                        <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32"
                                            ry="7">
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
                                <p>{{ $emptyMessage }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($pagination)
        <div class="sticky-table-pagination">
            {!! $pagination !!}
        </div>
    @endif
</div>

<script>
    document.querySelectorAll('.sticky-table-container').forEach(container => {
        const leftSticky = parseInt(container.dataset.leftSticky) || 0;
        const rightSticky = parseInt(container.dataset.rightSticky) || 0;

        const table = container.querySelector('.sticky-table');

        if (!table) return;

        const headers = table.querySelectorAll('thead th');
        headers.forEach((th, index) => {
            if (index < leftSticky) {
                th.setAttribute('data-sticky', '');
                th.setAttribute('data-sticky-left', '');
                th.style.left = calculateLeftPosition(index, leftSticky);
            } else if (index >= headers.length - rightSticky) {
                th.setAttribute('data-sticky', '');
                th.setAttribute('data-sticky-right', '');
                th.style.right = calculateRightPosition(headers.length - index - 1,
                    rightSticky);
            }
        });

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((td, index) => {
                if (index < leftSticky) {
                    td.setAttribute('data-sticky', '');
                    td.setAttribute('data-sticky-left', '');
                    td.style.left = calculateLeftPosition(index, leftSticky);

                    if (index === leftSticky - 1) {
                        td.setAttribute('data-sticky-left-last', '');
                    }
                } else if (index >= cells.length - rightSticky) {
                    td.setAttribute('data-sticky', '');
                    td.setAttribute('data-sticky-right', '');
                    td.style.right = calculateRightPosition(cells.length - index - 1,
                        rightSticky);

                    if (index === cells.length - rightSticky) {
                        td.setAttribute('data-sticky-right-first', '');
                    }
                }
            });
        });

    });

    function calculateLeftPosition(index, total) {
        return `${index * 150}px`;
    }

    function calculateRightPosition(index, total) {
        return `${index * 150}px`;
    }
</script>
