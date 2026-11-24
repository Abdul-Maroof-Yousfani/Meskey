{{--
    One category row, followed by its sub-categories (recursive).
    $row: category, $depth: 0 for a main category, $parentName: name of the parent (null for main),
    $childrenByParent: categories grouped by parent_id, $visibleIds: ids to show while searching (null = all),
    $seen: ids already printed (guards against a parent loop in the data).
--}}
@php
    $seen[$row->id] = true;
    $children = collect($childrenByParent->get($row->id, []))
        ->filter(fn ($c) => !isset($seen[$c->id]) && ($visibleIds === null || isset($visibleIds[$c->id])));
    $isMain = $depth === 0;
@endphp
<tr @if ($isMain) style="background: #f7f7f7;" @endif>
    <td @if ($isMain) style="font-weight: bold;" @endif>
        <p class="m-0" style="padding-left: {{ $depth * 22 }}px;">
            @if ($depth > 0)
                <span class="text-muted">{{ str_repeat('——', $depth) }}</span>
            @endif
            {{ $row->name }}
        </p>
    </td>
    <td>
        <p class="m-0">
            <small>{{ $parentName ?? '—' }}</small>
        </p>
    </td>
    <td style="text-transform: uppercase">
        <small>{{ Str::replace('_', ' ', $row->category_type) }}</small>
    </td>
    <td>
        <a onclick="openModal(this,'{{ route('category.heads.create', $row->id) }}','Configure Product Heads - {{ addslashes($row->name) }}', false, '50%')"
            class="success p-1 text-center mr-2 position-relative" title="Configure Head, B2 & By Products">
            <i class="ft-layers font-medium-3 text-success"></i>
        </a>
        @if ($row->is_protected !== 'yes')
            @can('role-edit')
                <a onclick="openModal(this,'{{ route('category.edit', $row->id) }}','Edit Category')"
                    class="info p-1 text-center mr-2 position-relative">
                    <i class="ft-edit font-medium-3"></i>
                </a>
            @endcan
            @can('role-delete')
                <a onclick="deletemodal('{{ route('category.destroy', $row->id) }}','{{ route('get.category') }}')"
                    class="danger p-1 text-center mr-2 position-relative">
                    <i class="ft-x font-medium-3"></i>
                </a>
            @endcan
        @endif
    </td>
</tr>
@if ($depth < 20)
    @foreach ($children as $child)
        @include('management.master.category.partials.row', [
            'row' => $child,
            'depth' => $depth + 1,
            'parentName' => $row->name,
            'seen' => $seen,
        ])
    @endforeach
@endif
