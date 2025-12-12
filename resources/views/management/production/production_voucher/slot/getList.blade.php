@if (count($slots) != 0)
    @foreach($slots as $slot)
        <tr data-slot-id="{{ $slot->id }}">
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
                @if($slot->attachment)
                    <a href="{{ asset('storage/' . $slot->attachment) }}" target="_blank" class="btn btn-sm btn-info" title="View Attachment">
                        <i class="ft-eye"></i> View
                    </a>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-primary" onclick="openModal(this, '{{ route('production-voucher.slot.edit-form', [$productionVoucher->id, $slot->id]) }}', 'Edit Production Slot', false, '70%')">
                    <i class="ft-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductionSlot({{ $productionVoucher->id }}, {{ $slot->id }})">
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

