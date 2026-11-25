{{--
    Users list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $users ke loop me include karta hai: @include(..., ['user' => $user])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $roles = $user->getRoleNames();
    $userCompanies = getUserAllCompanies($user->id);
    // har company ki locations ke naam (ek line me)
    $locationNames = [];
    foreach ($user->companies as $comp) {
        foreach (json_decode($comp->pivot->locations, true) ?? [] as $locId) {
            $name = \App\Models\Master\CompanyLocation::find($locId)?->name;
            if ($name) {
                $locationNames[] = $name;
            }
        }
    }
    $locationNames = array_values(array_unique($locationNames));
    $editUrl = route('users-test.edit', $user->id);
@endphp

<div class="tkc">
    <div class="tkc-main" role="button" tabindex="0"
        @canAccess('user-edit') onclick="window.location.href='{{ $editUrl }}'" @endcanAccess>

        <div class="tkc-top">
            <div class="tkc-no">{{ $user->name }}</div>
            <span class="tkc-qc {{ $user->purchase_order_approval ? 'is-approved' : 'is-pending' }}"
                title="PO Approval">PO {{ $user->purchase_order_approval ? 'Enabled' : 'Disabled' }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-user"></i>
            {{ '@' . $user->username }}
        </div>

        @if ($roles->isNotEmpty())
            <div class="tkc-tags">
                @foreach ($roles as $roleName)
                    <span class="tkc-tag is-inner">{{ $roleName }}</span>
                @endforeach
            </div>
        @endif

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Parent</span>
                <span class="tkc-val">{{ $user->parent?->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">COA</span>
                <span class="tkc-val">
                    @if ($user->account)
                        {{ $user->account->unique_no ?? '--' }}
                        @if ($user->account->status !== 'active')
                            <small class="d-block text-warning">Inactive</small>
                        @endif
                    @else
                        --
                    @endif
                </span>
            </div>
        </div>

        @if (count($userCompanies) || count($locationNames))
            <div class="tkc-chips">
                @foreach ($userCompanies as $v)
                    <span class="tkc-chip"><i class="ft-briefcase"></i>{{ $v->name }}</span>
                @endforeach
                @foreach ($locationNames as $locName)
                    <span class="tkc-chip"><i class="ft-map-pin"></i>{{ $locName }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- saare actions 3-dots sheet me (mobile-card-sheet.js) --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-more" aria-label="Actions" title="Actions" data-title="{{ $user->name }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        @canAccess('user-edit')
        <a href="{{ $editUrl }}" class="tkc-sheet-item">
            <i class="ft-edit"></i><span>Edit</span>
        </a>
        @endcanAccess
        @canAccess('user-list')
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('users-test.audit-logs', $user->id) }}','Audit Logs: User {{ $user->name }} ({{ $user->username }})', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
        @endcanAccess
        @canAccess('user-delete')
        <a href="javascript:;" class="tkc-sheet-item is-danger"
            onclick="deletemodal('{{ route('users-test.destroy', $user->id) }}','{{ route('get.users.test') }}')">
            <i class="ft-trash-2"></i><span>Delete</span>
        </a>
        @endcanAccess
    </template>
</div>
