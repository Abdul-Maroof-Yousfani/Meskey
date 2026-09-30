<div class="sticky-table-container" data-left-sticky="{{ $leftSticky }}" data-right-sticky="{{ $rightSticky }}">
    <div class="sticky-table-scroller">
        <table class="table sticky-table" id="exportableTable">
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
        <div class="row d-flex" id="paginationLinks">
            <div class="col-md-12 text-right">
                {!! $pagination !!}
            </div>
        </div>
    @endif
</div>

@once

    <script>
        document.querySelectorAll('.sticky-table-container').forEach(container => {
            const leftSticky = parseInt(container.dataset.leftSticky) || 0;
            const rightSticky = parseInt(container.dataset.rightSticky) || 0;

            const table = container.querySelector('.sticky-table');
            if (!table) return;

            const headers = table.querySelectorAll('thead th:not(.normal)');
            const headerRow = table.querySelector('thead tr:not(.normal)');

            headers.forEach((th, index) => {
                if (index < leftSticky) {
                    th.classList.add('sticky-col-left');
                    if (index === leftSticky - 1) {
                        th.classList.add('sticky-col-left-last');
                    }
                } else if (index >= headers.length - rightSticky) {
                    th.classList.add('sticky-col-right');
                    if (index === headers.length - rightSticky) {
                        th.classList.add('sticky-col-right-first');
                    }
                }
            });

            const rows = table.querySelectorAll('tbody tr:not(.normal)');
            let activeRowspans = {};

            rows.forEach(row => {
                const cells = row.querySelectorAll('td:not(.normal)');
                let colIndex = 0;

                cells.forEach(td => {
                    while (activeRowspans[colIndex] > 0) {
                        activeRowspans[colIndex]--;
                        colIndex++;
                    }

                    const rowspan = parseInt(td.getAttribute('rowspan')) || 1;
                    const colspan = parseInt(td.getAttribute('colspan')) || 1;
                    if (rowspan > 1) {
                        for (let c = 0; c < colspan; c++) {
                            activeRowspans[colIndex + c] = rowspan - 1;
                        }
                    }

                    td.dataset.colIndex = colIndex;

                    if (colIndex < leftSticky) {
                        td.classList.add('sticky-col-left');
                        if (colIndex === leftSticky - 1 || colIndex + colspan >= leftSticky) {
                            td.classList.add('sticky-col-left-last');
                        }
                    } else if (colIndex >= headers.length - rightSticky) {
                        td.classList.add('sticky-col-right');
                        if (colIndex === headers.length - rightSticky) {
                            td.classList.add('sticky-col-right-first');
                        }
                    }

                    colIndex += colspan;
                });
            });

            if (leftSticky > 0) {
                let leftPositions = [];
                let cumulativeWidth = 0;

                const firstRowCells = rows[0] ? rows[0].querySelectorAll('td:not(.normal)') : [];

                for (let i = 0; i < leftSticky; i++) {
                    if (headers[i]) {
                        cumulativeWidth += headers[i].offsetWidth;
                    } else if (firstRowCells[i]) {
                        cumulativeWidth += firstRowCells[i].offsetWidth;
                    } else {
                        cumulativeWidth += 150;
                    }
                    leftPositions.push(cumulativeWidth);
                }

                const leftStickyCells = table.querySelectorAll('.sticky-col-left');
                leftStickyCells.forEach(cell => {
                    const colIndex = cell.dataset.colIndex !== undefined
                        ? parseInt(cell.dataset.colIndex)
                        : Array.from(cell.parentNode.children).indexOf(cell);
                    if (colIndex < leftSticky) {
                        cell.style.left = (colIndex === 0 ? 0 : leftPositions[colIndex - 1]) + 'px';
                    }
                });
            }

            if (rightSticky > 0) {
                let rightPositions = [];
                let cumulativeWidth = 0;
                const totalCols = headers.length;

                for (let i = totalCols - 1; i >= totalCols - rightSticky; i--) {
                    if (headers[i]) {
                        cumulativeWidth += headers[i].offsetWidth;
                    } else {
                        cumulativeWidth += 150;
                    }
                    rightPositions.unshift(cumulativeWidth);
                }

                const rightStickyCells = table.querySelectorAll('.sticky-col-right');
                rightStickyCells.forEach(cell => {
                    const colIndex = cell.dataset.colIndex !== undefined
                        ? parseInt(cell.dataset.colIndex)
                        : Array.from(cell.parentNode.children).indexOf(cell);
                    if (colIndex >= totalCols - rightSticky) {
                        const posIndex = rightSticky - (totalCols - colIndex);
                        cell.style.right = (posIndex === 0 ? 0 : rightPositions[posIndex - 1]) + 'px';
                    }
                });
            }
        });
    </script>
@endonce
