@extends('management.layouts.master')
@section('title')
    Users
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="extended">

            <div class="row w-100 mx-auto">

                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Edit User - {{ ucfirst($user->name) }}<small
                            class="text-black-50">({{ $user->username }})</small> </h2>
                </div>
            </div>

            <div class="row justify-content-center mt-3">
                <div class="col-12">
                    <div class="card p-4">
                        <div class="card-header">

                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData"></div>
                            {!! Form::model($user, [
                                'route' => ['users-test.update', $user->id],
                                'method' => 'PUT',
                                'id' => 'ajaxSubmit',
                            ]) !!}
                            <input type="hidden" id="url" value="{{ route('users-test.edit', $user->id) }}" />

                            <div class="row align-items-center">
                                <div class="col-md-4 -auto">
                                    <div class="avatar-upload">
                                        <div class="avatar-edit">
                                            <input type='file' id="imageUpload" name="profile_image"
                                                accept=".png, .jpg, .jpeg" />
                                            <label for="imageUpload">
                                                <i class="ft-camera"></i>
                                            </label>
                                        </div>
                                        <div class="avatar-preview">
                                            <div id="imagePreview"
                                                style="background-image: url('{{ image_path($user->profile_image ?? '') }}');">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Name:</label>
                                                {!! Form::text('name', null, [
                                                    'placeholder' => 'Name',
                                                    'class' => 'form-control',
                                                    'id' => 'name',
                                                ]) !!}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Username:</label>
                                                {!! Form::text('username', null, [
                                                    'placeholder' => 'Username',
                                                    'class' => 'form-control',
                                                    'id' => 'username',
                                                ]) !!}
                                                <small id="username-help" class="form-text text-muted">Will be generated
                                                    automatically from name</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Email:</label>
                                                {!! Form::text('email', null, [
                                                    'placeholder' => 'Email',
                                                    'class' => 'form-control',
                                                    'id' => 'email',
                                                ]) !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Password Fields -->
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Password:</label>
                                                {!! Form::password('password', ['placeholder' => 'Leave blank to keep current', 'class' => 'form-control']) !!}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Confirm Password:</label>
                                                {!! Form::password('password_confirmation', ['placeholder' => 'Confirm Password', 'class' => 'form-control']) !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- User Type Field -->
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>User Type:</label>
                                                <input type="hidden" name="user_type" value="{{ $user->user_type }}"
                                                    class="form-control">
                                                <select name="user_type_d" disabled id="user_type" class="form-control">
                                                    <option value="user"
                                                        {{ $user->user_type === 'user' ? 'selected' : '' }}>
                                                        User
                                                    </option>
                                                    <option value="super-admin"
                                                        {{ $user->user_type === 'super-admin' ? 'selected' : '' }}>Super
                                                        Admin
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Parent User (Optional):</label>
                                                <select name="parent_user_id" id="parent_user_id" class="form-control">
                                                    <option value="">Select Parent User</option>
                                                    @foreach ($users as $parentUser)
                                                        @if ($parentUser->id != $user->id)
                                                            <option value="{{ $parentUser->id }}"
                                                                {{ $user->parent_user_id == $parentUser->id ? 'selected' : '' }}>
                                                                {{ $parentUser->name }} ({{ $parentUser->username }})
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row bottom-button-bar mt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-primary submitbutton">Update Info</button>
                                    @canAccess('assign-company')
                                    <button
                                        onclick="openModal(this,'{{ route('users-test.assign', $user->id) }}','Assign Company')"
                                        type="button" class="btn btn-primary position-relative ">
                                        Assign Companies
                                    </button>
                                    @endcanAccess
                                </div>
                            </div>
                            {!! Form::close() !!}

                        </div>
                    </div>
                </div>
            </div>

            @if ($user->companies)
                <div class="row mt-5">
                    <div class="col-md-12">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold mb-0">Assigned Companies & Locations</h4>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">

                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Company</th>
                                            {{-- <th>Role</th> --}}
                                            <th>Locations & Arrivals</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($user->companies as $comp)
                                            @php
                                                $pivot = $comp->pivot;

                                                $savedLocations = json_decode($pivot->locations, true) ?? [];
                                                $flatArrivalList = json_decode($pivot->arrival_locations, true) ?? [];

                                                // Convert flat arrival list → location wise mapping
                                                $savedArrivals = [];

                                                foreach ($flatArrivalList as $arrId) {
                                                    $arr = \App\Models\Master\ArrivalLocation::find($arrId);

                                                    if ($arr && $arr->company_location_id) {
                                                        $savedArrivals[$arr->company_location_id][] = $arrId;
                                                    }
                                                }

                                                $role = \Spatie\Permission\Models\Role::find($pivot->role_id);
                                            @endphp

                                            <tr>
                                                <!-- Company -->
                                                <td class="align-middle">
                                                    <strong>{{ $comp->name }}</strong>
                                                </td>

                                                {{-- <td>
                                                    {{ $role->name }}
                                                </td> --}}

                                                <!-- Locations + Arrivals -->
                                                <td class="align-middle">
                                                    <div class="border rounded p-2 bg-transparent"
                                                        style="max-height:150px; overflow-y:auto;">

                                                        @if (count($savedLocations))
                                                            @foreach ($savedLocations as $locId)
                                                                @php
                                                                    $loc = \App\Models\Master\CompanyLocation::find(
                                                                        $locId,
                                                                    );
                                                                    $arrivalList = $savedArrivals[$locId] ?? [];
                                                                @endphp

                                                                <div class="mb-2">
                                                                    <strong
                                                                        class="text-dark">{{ $loc?->name ?? 'Location N/A' }}</strong>

                                                                    @if (count($arrivalList))
                                                                        <ul class="mt-1 ps-3 mb-1">
                                                                            @foreach ($arrivalList as $arrId)
                                                                                @php
                                                                                    $arr = \App\Models\Master\ArrivalLocation::find(
                                                                                        $arrId,
                                                                                    );
                                                                                @endphp

                                                                                <li class="small">
                                                                                    {{ $arr?->name ?? 'Arrival N/A' }}</li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @else
                                                                        <small class="text-muted ps-3">No Arrival
                                                                            Locations</small>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No Locations Assigned</span>
                                                        @endif

                                                    </div>
                                                </td>

                                                <!-- Actions -->
                                                <td class="text-center align-middle">
                                                    @canAccess('edit-assign-company')
                                                    <a onclick="openModal(this,'{{ route('users-test.edit-assign', ['userId' => $user->id, 'companyId' => $comp->id, 'roleId' => $role->id]) }}','Edit Company')"
                                                        class="info p-1 text-center mr-2 position-relative ">
                                                        <i class="ft-edit font-medium-3"></i>
                                                    </a>
                                                    @endcanAccess

                                                    @canAccess('delete-assign-company')
                                                    <a onclick="deletemodal(
                                                            '{{ route('users-test.delete-assign', ['userId' => $user->id, 'companyId' => $comp->id, 'roleId' => $role->id]) }}',
                                                            '{{ route('get.users.test') }}'
                                                        )"
                                                        class="danger p-1 text-center mr-2 position-relative">
                                                        <i class="ft-x font-medium-3"></i>
                                                    </a>
                                                    @endcanAccess

                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        </div>

                    </div>
                </div>
            @endif

    </div>
    </section>

    </div>
@endsection


@section('script')
    <script>
        $(document).ready(function() {
            // Username generation and validation
            $('#name').on('keyup', function() {
                const name = $(this).val().trim();
                const username = name.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9-]/g, '');
                $('#username').val(username);
                checkUsernameAvailability(username);
            });

            $('#username').on('keyup', function() {
                checkUsernameAvailability($(this).val());
            });

            function checkUsernameAvailability(username) {
                if (username) {
                    $.get('/acl/check-username', {
                        username: username,
                        user_id: {{ $user->id ?? 'null' }}
                    }, function(data) {
                        if (data.available) {
                            $('#username').removeClass('is-invalid').addClass('is-valid');
                            $('#username-help').text('Username is available').removeClass('text-danger')
                                .addClass('text-success');
                        } else {
                            $('#username').removeClass('is-valid').addClass('is-invalid');
                            $('#username-help').text('Username is not available').removeClass(
                                'text-success').addClass('text-danger');
                        }
                    });
                }
            }

            // Card management functions
            function toggleRemoveButton() {
                if ($('#card-container .clonecard').length === 1) {
                    $('#card-container .clonecard .remove-card').hide();
                } else {
                    $('#card-container .clonecard .remove-card').show();
                }
            }

            toggleRemoveButton();

            $('body').on('click', '.add-more', function() {
                var newCard = $('#card-container .original-card').clone();
                newCard.removeClass('original-card');
                newCard.find('select').val('');
                $('#card-container').append(newCard);
                toggleRemoveButton();
            });

            $(document).on('click', '.remove-card', function() {
                if ($('#card-container .clonecard').length > 1) {
                    $(this).closest('.clonecard').remove();
                    toggleRemoveButton();
                }
            });
        });
    </script>
@endsection
