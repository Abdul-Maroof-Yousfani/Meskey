<form action="{{ route('category.heads.store', $category->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.category') }}" />
    <input type="hidden" name="category_id" value="{{ $category->id }}" />
    <div id="deleted-ids-holder"></div>

    {{-- Compact Category Summary Header --}}
    <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded border">
        <div class="d-flex align-items-center">
            <span class="badge badge-primary mr-2 px-2 py-1 font-small-3">
                <i class="ft-folder mr-1"></i>{{ $category->name }}
            </span>
            <small class="text-muted text-black-50">
                {{ $category->parent ? 'Parent: ' . $category->parent->name . ' • ' : '' }}
                {{ strtoupper(str_replace('_', ' ', $category->category_type ?? 'raw_finish')) }}
            </small>
        </div>
        <div>
            <span class="badge badge-pill badge-secondary font-small-2 shadow-xs">
                <span id="totalItemsCount">0</span> configured
            </span>
        </div>
    </div>

    {{-- Compact Cards Container (Dynamically driven by ProductHeadType schema) --}}
    <div class="row">
        @foreach ($sections as $flagKey => $config)
            @php
                $existingCount = $config['items']->count();
                $isEnabled = $existingCount > 0;
            @endphp
            <div class="col-12 mb-2">
                <div class="card border shadow-xs head-card-group" id="card_{{ $flagKey }}" style="border-radius: 6px;">
                    {{-- Compact Card Header --}}
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                        <div class="d-flex align-items-center">
                            <h6 class="mb-0 font-weight-bold text-dark d-inline-block font-medium-1">
                                {{ $config['title'] }}
                            </h6>
                            <span class="badge badge-pill badge-secondary font-small-2 ml-2 item-count-badge" id="count_badge_{{ $flagKey }}">
                                {{ $existingCount }} {{ Str::plural('item', $existingCount) }}
                            </span>
                        </div>

                        {{-- Perfectly Sized Custom Toggle Switch --}}
                        <div class="d-flex align-items-center">
                            <label class="toggle-switch-custom mb-0">
                                <input type="checkbox"
                                       class="category-flag-toggle"
                                       id="toggle_{{ $flagKey }}"
                                       name="flags[{{ $flagKey }}][enabled]"
                                       data-flag="{{ $flagKey }}"
                                       value="1"
                                       {{ $isEnabled ? 'checked' : '' }}>
                                <span class="toggle-slider-custom"></span>
                            </label>
                        </div>
                    </div>

                    {{-- Collapsible Card Body --}}
                    <div class="card-body p-2 flag-card-body" id="body_{{ $flagKey }}" style="{{ !$isEnabled ? 'display: none;' : '' }}">
                        <div class="table-responsive mb-2">
                            <table class="table table-bordered table-sm mb-0 align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th>Head Name (e.g. Husk, Dry, Broken) <span class="text-danger">*</span></th>
                                        <th style="width: 50px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="repeater-items-container" id="container_{{ $flagKey }}">
                                    @if ($existingCount > 0)
                                        @foreach ($config['items'] as $index => $item)
                                            <tr class="repeater-row" data-index="{{ $index }}">
                                                <td class="text-center font-weight-bold text-muted row-number">{{ $index + 1 }}</td>
                                                <td>
                                                    <input type="hidden" name="flags[{{ $flagKey }}][items][{{ $index }}][id]" value="{{ $item->id }}">
                                                    <input type="text"
                                                           name="flags[{{ $flagKey }}][items][{{ $index }}][name]"
                                                           class="form-control form-control-sm item-name-input"
                                                           placeholder="Enter head name (e.g. Husk, Dry)..."
                                                           value="{{ $item->name }}"
                                                           required>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm p-1 remove-row-btn" data-id="{{ $item->id }}" title="Remove">
                                                        <i class="ft-trash-2 font-medium-1"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Add More Button --}}
                        <div class="pt-1">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm add-more-btn"
                                    data-flag="{{ $flagKey }}">
                                <i class="ft-plus mr-1"></i> Add More 
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Button Bar --}}
    <div class="row bottom-button-bar mt-3 pt-2 border-top">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-secondary modal-sidebar-close position-relative top-1 closebutton mr-2">
                <i class="ft-x mr-1"></i> Close
            </a>
            <button type="submit" class="btn btn-primary submitbutton">
                <i class="ft-check mr-1"></i> Save Category Heads
            </button>
        </div>
    </div>
</form>

<style>
    .head-card-group {
        transition: all 0.2s ease-in-out;
    }
    .head-card-group:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
    }
    .repeater-row {
        animation: fadeInRow 0.2s ease;
    }
    @keyframes fadeInRow {
        from { opacity: 0; transform: translateY(-3px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Pixel-perfect toggle switch */
    .toggle-switch-custom {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin: 0;
        cursor: pointer;
        vertical-align: middle;
    }
    .toggle-switch-custom input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .toggle-slider-custom {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: all 0.25s ease;
        border-radius: 24px;
    }
    .toggle-slider-custom:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff;
        transition: all 0.25s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .toggle-switch-custom input:checked + .toggle-slider-custom {
        background-color: #007bff;
    }
    .toggle-switch-custom input:checked + .toggle-slider-custom:before {
        transform: translateX(20px);
    }
    .item-name-input.is-invalid {
        border-color: #ea5455 !important;
        background-color: #fff8f8 !important;
    }
</style>

<script>
(function() {
    var deletedIds = [];

    // Validate type-wise duplicates within a specific flag container
    function validateTypeDuplicates(flagKey) {
        var $container = $('#container_' + flagKey);
        var $inputs = $container.find('.item-name-input');
        var counts = {};
        var hasDuplicate = false;

        // Count non-empty trimmed lowercase names
        $inputs.each(function() {
            var val = $(this).val().trim().toLowerCase();
            if (val !== '') {
                counts[val] = (counts[val] || 0) + 1;
            }
        });

        // Mark duplicates and remove error from unique rows
        $inputs.each(function() {
            var $input = $(this);
            var val = $input.val().trim().toLowerCase();
            var $cell = $input.closest('td');

            if (val !== '' && counts[val] > 1) {
                hasDuplicate = true;
                $input.addClass('is-invalid');
                if ($cell.find('.duplicate-name-feedback').length === 0) {
                    $cell.append('<div class="invalid-feedback d-block duplicate-name-feedback font-small-2 mt-1"><i class="ft-alert-circle mr-1"></i>Name must be unique in this section</div>');
                }
            } else {
                $cell.find('.duplicate-name-feedback').remove();
                if (val !== '' && $cell.find('.empty-name-feedback').length === 0) {
                    $input.removeClass('is-invalid');
                }
            }
        });

        return !hasDuplicate;
    }

    function updateRowNumbers(flagKey) {
        var $rows = $('#container_' + flagKey + ' .repeater-row');
        $rows.each(function(idx) {
            $(this).find('.row-number').text(idx + 1);
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[items\]\[\d+\]/, '[items][' + idx + ']');
                    $(this).attr('name', newName);
                }
            });
        });

        var count = $rows.length;
        $('#count_badge_' + flagKey).text(count + (count === 1 ? ' item' : ' items'));
        updateTotalSummary();
        validateTypeDuplicates(flagKey);
    }

    function updateTotalSummary() {
        var total = 0;
        $('.category-flag-toggle').each(function() {
            var flag = $(this).data('flag');
            if ($(this).is(':checked')) {
                total += $('#container_' + flag + ' .repeater-row').length;
            }
        });
        $('#totalItemsCount').text(total);
    }

    function addRow(flagKey) {
        var $container = $('#container_' + flagKey);
        var newIndex = $container.find('.repeater-row').length;

        var rowHtml = `
            <tr class="repeater-row" data-index="${newIndex}">
                <td class="text-center font-weight-bold text-muted row-number">${newIndex + 1}</td>
                <td>
                    <input type="text"
                           name="flags[${flagKey}][items][${newIndex}][name]"
                           class="form-control form-control-sm item-name-input"
                           placeholder="Enter head name (e.g. Husk, Dry)..."
                           required autocomplete="off">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 remove-row-btn" title="Remove">
                        <i class="ft-trash-2 font-medium-1"></i>
                    </button>
                </td>
            </tr>
        `;

        var $newRow = $(rowHtml);
        $container.append($newRow);
        updateRowNumbers(flagKey);

        $newRow.find('.item-name-input').focus();
    }

    // Real-time typing & blur validation for type-wise uniqueness
    $(document).off('input blur', '.item-name-input').on('input blur', '.item-name-input', function() {
        var $input = $(this);
        var val = $input.val().trim();
        var $cell = $input.closest('td');
        if (val !== '') {
            $cell.find('.empty-name-feedback').remove();
        }
        var $container = $input.closest('.repeater-items-container');
        if ($container.length) {
            var flagKey = $container.attr('id').replace('container_', '');
            validateTypeDuplicates(flagKey);
        }
    });

    // Toggle switch handler
    $(document).off('change', '.category-flag-toggle').on('change', '.category-flag-toggle', function() {
        var flagKey = $(this).data('flag');
        var isChecked = $(this).is(':checked');
        var $body = $('#body_' + flagKey);

        if (isChecked) {
            $body.slideDown(200);
            $body.find('input, button').prop('disabled', false);

            if ($('#container_' + flagKey + ' .repeater-row').length === 0) {
                addRow(flagKey);
            } else {
                validateTypeDuplicates(flagKey);
            }
        } else {
            $body.slideUp(180);
            $body.find('input, button').prop('disabled', true);
        }

        updateTotalSummary();
    });

    // Add More button handler
    $(document).off('click', '.add-more-btn').on('click', '.add-more-btn', function(e) {
        e.preventDefault();
        var flagKey = $(this).data('flag');
        addRow(flagKey);
    });

    // Remove row handler
    $(document).off('click', '.remove-row-btn').on('click', '.remove-row-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $row = $btn.closest('.repeater-row');
        var $tableBody = $row.closest('.repeater-items-container');
        var flagKey = $tableBody.attr('id').replace('container_', '');
        var headId = $btn.data('id');

        if (headId) {
            deletedIds.push(headId);
            $('#deleted-ids-holder').append('<input type="hidden" name="deleted_ids[]" value="' + headId + '">');
        }

        $row.fadeOut(150, function() {
            $(this).remove();
            updateRowNumbers(flagKey);
        });
    });

    // Client-side submit interceptor: guarantees uniqueness type-wise before AJAX processing
    $('#ajaxSubmit').off('submit.validate_heads').on('submit.validate_heads', function(e) {
        var hasError = false;
        var firstErrorInput = null;
        var errorMessage = '';

        $('.category-flag-toggle:checked').each(function() {
            var flagKey = $(this).data('flag');
            var cardTitle = $('#card_' + flagKey).find('h6').text().trim() || flagKey;

            // 1. Check type-wise uniqueness
            var isUnique = validateTypeDuplicates(flagKey);
            if (!isUnique) {
                hasError = true;
                if (!firstErrorInput) {
                    firstErrorInput = $('#container_' + flagKey).find('.item-name-input.is-invalid').first();
                    errorMessage = 'Duplicate head names found in "' + cardTitle + '". Each head name must be unique within its section.';
                }
            }

            // 2. Check empty head names
            $('#container_' + flagKey + ' .item-name-input').each(function() {
                var val = $(this).val().trim();
                if (val === '') {
                    $(this).addClass('is-invalid');
                    var $cell = $(this).closest('td');
                    if ($cell.find('.empty-name-feedback').length === 0) {
                        $cell.append('<div class="invalid-feedback d-block empty-name-feedback font-small-2 mt-1"><i class="ft-alert-circle mr-1"></i>Head name is required</div>');
                    }
                    hasError = true;
                    if (!firstErrorInput) {
                        firstErrorInput = $(this);
                        errorMessage = 'Please enter all head names in "' + cardTitle + '" or remove empty rows.';
                    }
                }
            });
        });

        if (hasError) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: errorMessage,
                    confirmButtonColor: '#D95000'
                });
            } else {
                alert(errorMessage);
            }

            if (firstErrorInput && firstErrorInput.length) {
                firstErrorInput.focus();
            }
            return false;
        }
    });

    // Initial check on modal load
    $('.category-flag-toggle:checked').each(function() {
        var flagKey = $(this).data('flag');
        validateTypeDuplicates(flagKey);
    });

    updateTotalSummary();
})();
</script>
