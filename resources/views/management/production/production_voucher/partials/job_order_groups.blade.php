{{--
    Job Order selects of the Production Voucher, grouped as Head / B2 / BY Products.
    Each group shows at least 3 selects; "Add More" on the heading adds another select to that group.

    Needs: $headJobOrders, $b2JobOrders, $byJobOrders (option lists).
    Optional: $selectedJobOrderGroups = ['head' => [ids], 'b2' => [ids], 'by' => [ids]] (edit),
              $jobOrders (all job orders, so a saved one missing from its list still shows).
--}}
@php
    $jobOrderGroupDefs = [
        'head' => ['title' => 'Head Products', 'list' => $headJobOrders ?? collect()],
        'b2' => ['title' => 'B2 Products', 'list' => $b2JobOrders ?? collect()],
        'by' => ['title' => 'BY Products', 'list' => $byJobOrders ?? collect()],
    ];
    $selectedJobOrderGroups = $selectedJobOrderGroups ?? [];
    $allJobOrdersById = collect($jobOrders ?? [])->keyBy('id');
    $jobOrderSelectNo = 0;
    $jobOrderText = function ($jo) {
        return $jo->job_order_no
            . ($jo->ref_no ? ' (' . $jo->ref_no . ')' : '')
            . ($jo->product ? ' - ' . $jo->product->name : '');
    };
@endphp

@foreach ($jobOrderGroupDefs as $category => $group)
    @php
        $selectedIds = array_values($selectedJobOrderGroups[$category] ?? []);
        $slotCount = max(3, count($selectedIds));
        $listIds = $group['list']->pluck('id')->map(fn($id) => (string) $id)->all();
    @endphp

    <div class="job-order-group" data-category="{{ $category }}">
        <div class="header-heading-sepration py-1 px-2 mb-1 {{ $loop->first ? '' : 'mt-1' }} d-flex align-items-center justify-content-between"
             style="background-color: #93c3f2;">
            <h6 class="m-0 font-weight-bold" style="font-size: 12px; color: #000;">{{ $group['title'] }}</h6>
            <button type="button" class="btn btn-sm btn-primary py-0 px-2 add-job-order-select" style="font-size: 11px;">
                <i class="ft-plus"></i> Add More
            </button>
        </div>
        <div class="row mb-1 job-order-group-selects">
            @for ($i = 0; $i < $slotCount; $i++)
                @php
                    $jobOrderSelectNo++;
                    $currentVal = isset($selectedIds[$i]) ? (string) $selectedIds[$i] : null;
                    $missingJo = ($currentVal && !in_array($currentVal, $listIds, true)) ? $allJobOrdersById->get($currentVal) : null;
                @endphp
                <div class="col-md-4 job-order-slot">
                    <div class="form-group mb-1">
                        <label class="mb-0 small font-weight-bold">Job Ord. <span class="job-order-slot-no">{{ $i + 1 }}</span>:</label>
                        @if ($i >= 3)
                            <a href="javascript:void(0)" class="text-danger float-right small remove-job-order-select" title="Remove">
                                <i class="ft-x"></i>
                            </a>
                        @endif
                        <select name="job_order_id[]" id="job_order_id_{{ $jobOrderSelectNo }}"
                            class="form-control select2 job-order-select job-order-{{ $category }}"
                            data-category="{{ $category }}"
                            onchange="onJobOrderChanged();">
                            <option value="">Select Job Order</option>
                            @foreach ($group['list'] as $jobOrder)
                                <option value="{{ $jobOrder->id }}" @selected($currentVal === (string) $jobOrder->id)>
                                    {{ $jobOrderText($jobOrder) }}
                                </option>
                            @endforeach
                            @if ($missingJo)
                                <option value="{{ $missingJo->id }}" selected>{{ $jobOrderText($missingJo) }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            @endfor
        </div>
    </div>
@endforeach

<script>
    $(function () {
        // Next id number for new selects (ids stay unique: job_order_id_N)
        let nextJobOrderSelectNo = {{ $jobOrderSelectNo }} + 1;

        function renumberGroup($group) {
            $group.find('.job-order-slot').each(function (index) {
                $(this).find('.job-order-slot-no').text(index + 1);
            });
        }

        $('.job-order-group').on('click', '.add-job-order-select', function () {
            const $group = $(this).closest('.job-order-group');
            const category = $group.data('category');
            const $firstSelect = $group.find('select.job-order-select').first();

            const $slot = $(
                '<div class="col-md-4 job-order-slot">' +
                    '<div class="form-group mb-1">' +
                        '<label class="mb-0 small font-weight-bold">Job Ord. <span class="job-order-slot-no"></span>:</label>' +
                        '<a href="javascript:void(0)" class="text-danger float-right small remove-job-order-select" title="Remove"><i class="ft-x"></i></a>' +
                    '</div>' +
                '</div>'
            );

            // Same options as the group's current list (it may have been reloaded by location/product)
            const $select = $('<select name="job_order_id[]" onchange="onJobOrderChanged();"></select>')
                .attr('id', 'job_order_id_' + (nextJobOrderSelectNo++))
                .attr('data-category', category)
                .addClass('form-control select2 job-order-select job-order-' + category);
            $firstSelect.find('option').each(function () {
                $select.append($('<option></option>').attr('value', $(this).attr('value')).text($(this).text()));
            });
            $select.val('');
            $select.prop('disabled', $firstSelect.prop('disabled'));

            $slot.find('.form-group').append($select);
            $group.find('.job-order-group-selects').append($slot);
            if ($.fn.select2) {
                $select.select2();
            }
            renumberGroup($group);
        });

        $('.job-order-group').on('click', '.remove-job-order-select', function () {
            const $slot = $(this).closest('.job-order-slot');
            const $group = $slot.closest('.job-order-group');
            const $select = $slot.find('select.job-order-select');
            const hadValue = !!$select.val();

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $slot.remove();
            renumberGroup($group);

            // Removing a chosen job order changes the voucher's job orders
            if (hadValue && typeof onJobOrderChanged === 'function') {
                onJobOrderChanged();
            }
        });
    });
</script>
