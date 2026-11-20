{{--
    Plants of the selected Arrival Locations, assigned to the user (saved as plant_id[]).
    Each card needs a ".plant-wrapper" next to its ".arrival-wrapper".
    Optional: $selectedPlants (edit) - plant ids already assigned.
--}}
<script>
    (function () {
        const initialSelectedPlants = (@json($selectedPlants ?? [])).map(String);
        const loadTimers = {};
        const emptyText = '<small class="text-muted">Select an arrival location to load plants</small>';

        // Loads the plants of the card's selected arrival locations; keeps plants already chosen
        window.loadAssignPlants = function (card) {
            card = $(card);
            const key = card.index();
            clearTimeout(loadTimers[key]);
            loadTimers[key] = setTimeout(function () {
                const wrapper = card.find('.plant-wrapper');
                const arrivalIds = card.find('.arrival-select').val() || [];
                const current = wrapper.find('select.plant-select').val();
                const keep = (current && current.length ? current : initialSelectedPlants).map(String);

                if (!arrivalIds.length) {
                    wrapper.html(emptyText);
                    return;
                }

                $.get('/acl/get-arrival-plants', { arrival_ids: arrivalIds }, function (plants) {
                    if (!plants.length) {
                        wrapper.html('<small class="text-muted">No active plants for the selected arrival locations</small>');
                        return;
                    }

                    const select = $('<select class="form-control plant-select" name="plant_id[]" multiple></select>');
                    plants.forEach(function (plant) {
                        const option = $('<option></option>')
                            .val(plant.id)
                            .text(plant.name + (plant.arrival_name ? ' (' + plant.arrival_name + ')' : ''));
                        if (keep.includes(String(plant.id))) {
                            option.prop('selected', true);
                        }
                        select.append(option);
                    });

                    const box = $('<div class="single-plant mb-2"><label class="font-weight-bold">Plants</label></div>').append(select);
                    wrapper.html(box);
                    select.select2({ width: '100%', placeholder: 'Select Plants' });
                });
            }, 150);
        };

        // Bound once (namespaced), so reopening the form does not stack handlers
        $(document)
            .off('change.assignPlants', '.arrival-select')
            .on('change.assignPlants', '.arrival-select', function () {
                window.loadAssignPlants($(this).closest('.clonecard'));
            })
            .off('change.assignPlantsLocation', '.location-select')
            .on('change.assignPlantsLocation', '.location-select', function () {
                // Arrival list is rebuilt for the new locations, so plants start over
                $(this).closest('.clonecard').find('.plant-wrapper').html(emptyText);
            });
    })();
</script>
