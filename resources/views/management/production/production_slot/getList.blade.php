@if (count($productionSlots) != 0)
    @foreach($productionSlots as $slot)
        <tr data-slot-id="{{ $slot->id }}">
            <td>{{ $slot->productionVoucher->prod_no ?? 'N/A' }}</td>
            <td>{{ $slot->date ? $slot->date->format('Y-m-d') : 'N/A' }}</td>
            <td>{{ $slot->start_time ?? 'N/A' }}</td>
            <td>{{ $slot->end_time ?? '-' }}</td>
            <td>
                @if($slot->breaks && count($slot->breaks) > 0)
                    <span class="badge badge-info">{{ count($slot->breaks) }} break(s)</span>
                @else
                    <span class="text-muted">No breaks</span>
                @endif
            </td>
            <td>
                @if($slot->status == 'active')
                    <span class="badge badge-success">Active</span>
                @elseif($slot->status == 'completed')
                    <span class="badge badge-primary">Completed</span>
                @else
                    <span class="badge badge-danger">Cancelled</span>
                @endif
            </td>
            <td>
                <button type="button" class="btn btn-outline-primary" 
                    onclick="openModal(this,'{{ route('production-slot.edit', $slot->id) }}','Edit Production Slot',false,'90%')" 
                    title="Edit">
                    <i class="ft-edit"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" 
                    onclick="deleteProductionSlot({{ $slot->id }})" 
                    title="Delete">
                    <i class="ft-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="7" class="text-center">No production slots found</td>
    </tr>
@endif

<script>
    function deleteProductionSlot(id) {
        if (confirm('Are you sure you want to delete this production slot?')) {
            $.ajax({
                url: '{{ route("production-slot.destroy", ":id") }}'.replace(':id', id),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    filterationCommon(`{{ route('get.production-slot') }}`);
                    showNotification('success', response.success);
                },
                error: function(xhr) {
                    showNotification('error', xhr.responseJSON?.error || 'Something went wrong');
                }
            });
        }
    }
</script>

