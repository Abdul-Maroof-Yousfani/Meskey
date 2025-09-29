@php
    $indent = $level * 20;
@endphp

<tr>
    <td class="dashed-indent position-relative" data-indent-w="{{ $indent * 0.7 }}px">
        <p class="m-0" style="padding-left: {{ $indent }}px">
            {{-- #{{ $account->unique_no }} --}}
            <br><small>
                {{ $account->hierarchy_path }}
            </small>
        </p>
    </td>
    <td>
        <p class="m-0" style="padding-left: {{ $indent }}px">
            {{ $account->name }}
            @if ($account->description)
                <br>
                <small class="text-muted">{{ $account->description }}</small>
            @endif
        </p>
    </td>
    <td>
        <span class="badge badge-{{ $account->account_type == 'debit' ? 'primary' : 'success' }}">
            {{ ucfirst($account->account_type) }}
        </span>
    </td>
    <td>
        <label class="badge text-uppercase m-0 {{ $account->status == 'active' ? 'badge-success' : 'badge-danger' }}">
            {{ $account->status }}
        </label>
    </td>
    <td>
        <p class="m-0">
            {{ \Carbon\Carbon::parse($account->created_at)->format('Y-m-d') }}<br>
            {{ \Carbon\Carbon::parse($account->created_at)->format('H:i A') }}
        </p>
    </td>
    <td>
        <a onclick="openModal(this,'{{ route('account.edit', $account->id) }}','Edit Account', true)"
            class="info p-1 text-center mr-2 position-relative">
            <i class="ft-edit font-medium-3"></i>
        </a>
    </td>
</tr>
<script>
    document.querySelectorAll('.dashed-indent').forEach(el => {
        const w = el.getAttribute('data-indent-w');
        el.style.setProperty('--indent-width', w);
    });
</script>

@foreach ($account->children as $child)
    @include('management.master.account.partials.account_row', [
        'account' => $child,
        'level' => $level + 1,
    ])
@endforeach
