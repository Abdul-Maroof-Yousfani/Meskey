<script>
    $(document).ready(function() {
        $(document).on('click', '.add-range', function() {
            const slabTypeId = $(this).data('slab-type');
            const $container = $(`.slab-ranges-container[data-slab-type="${slabTypeId}"]`);
            const $template = $container.find('.slab-range-template').html();
            const newIndex = $container.find('.slab-range-row').length;
            console.log("asdasd");

            const $newRow = $($template.replace(/\[0\]/g, `[${newIndex}]`));
            $container.append($newRow);
        });

        $(document).on('click', '.remove-range', function() {
            if ($(this).closest('.slab-ranges-container').find('.slab-range-row').length > 1) {
                $(this).closest('.slab-range-row').remove();

                const $container = $(this).closest('.slab-ranges-container');
                $container.find('.slab-range-row').each(function(index) {
                    $(this).find('input').each(function() {
                        const name = $(this).attr('name').replace(/\[\d+\]/,
                            `[${index}]`);
                        $(this).attr('name', name);
                    });
                });
            } else {
                alert('At least one range must exist for each slab type.');
            }
        });

        $(document).on('input keyup', '.range-from, .range-to', function() {
            const $row = $(this).closest('.slab-range-row');
            const $container = $(this).closest('.slab-ranges-container');
            const $formGroup = $(this).closest('.form-group');
            const slabTypeId = $container.data('slab-type');
            const from = parseFloat($row.find('.range-from').val());
            const to = parseFloat($row.find('.range-to').val());

            $row.find('.range-from, .range-to').removeClass('is-invalid');
            $formGroup.find('.error-message').remove();

            if (!isNaN(from) && !isNaN(to)) {
                if (from >= to) {
                    $row.find('.range-to').addClass('is-invalid');
                    $formGroup.append(
                        '<div class="error-message text-danger">"To" value must be greater than "From" value</div>'
                    );
                    return;
                }

                let hasOverlap = false;
                $container.find('.slab-range-row').not($row).each(function() {
                    const otherFrom = parseFloat($(this).find('.range-from').val());
                    const otherTo = parseFloat($(this).find('.range-to').val());

                    if (!isNaN(otherFrom) && !isNaN(otherTo)) {
                        if ((from >= otherFrom && from <= otherTo) ||
                            (to >= otherFrom && to <= otherTo) ||
                            (from <= otherFrom && to >= otherTo)) {
                            hasOverlap = true;
                            return false; // break loop
                        }
                    }
                });

                if (hasOverlap) {
                    $row.find('.range-from, .range-to').addClass('is-invalid');
                    $formGroup.append(
                        '<div class="error-message text-danger">This range overlaps with another range</div>'
                    );
                }
            }
        });
    });
</script>
