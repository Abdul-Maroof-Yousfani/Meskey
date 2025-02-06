@extends('management/layouts/master')
@section('title')
    Users
@endsection
@section('content')

    @php
        use App\Models\User;
        use Carbon\Carbon;
        $users_data = App\Models\User::loginHistory();
    @endphp
    <div class="content-wrapper">

        <!-- Account Settings starts -->
        <div class="row">
            <div class="col-md-3 mt-3">
                <div class="row">
                    <div class="col-12 mb-2">
                        <div class="content-header mt-0">Account Settings</div>
                        <p class="content-sub-header mb-1">Configure account settings to your needs.</p>
                    </div>
                </div>


                <!-- Nav tabs -->
                <ul class="nav flex-column nav-pills" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"
                            aria-controls="general" aria-selected="true">
                            <i class="ft-settings mr-1 align-middle"></i>
                            <span class="align-middle">General</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="change-password-tab" data-toggle="tab" href="#change-password"
                            role="tab" aria-controls="change-password" aria-selected="false">
                            <i class="ft-lock mr-1 align-middle"></i>
                            <span class="align-middle">Change Password</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="connections-tab" data-toggle="tab" href="#connections" role="tab"
                            aria-controls="connections" aria-selected="false">
                            <i class="ft-link mr-1 align-middle"></i>
                            <span class="align-middle">Login History</span>
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link" id="notifications-tab" data-toggle="tab" href="#notifications" role="tab"
                            aria-controls="notifications" aria-selected="false">
                            <i class="ft-bell mr-1 align-middle"></i>
                            <span class="align-middle">Notifications</span>
                        </a>
                    </li> --}}
                </ul>
            </div>
            <div class="col-md-9">
                <!-- Tab panes -->
                <div class="card">
                    <div class="card-content">
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- General Tab -->
                                <div class="tab-pane active" id="general" role="tabpanel" aria-labelledby="general-tab">



                                    <form class="form-l" id="ajaxSubmit" method="post"
                                        action="{{ route('profile-settings', auth()->user()->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <div class="col-md-12 -auto">
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
                                                            style="background-image: url('{{ image_path(auth()->user()->profile_image) }}');">
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-center mt-2">{{'@'.auth()->user()->username}}</p>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label>Name</label>
                                                            <input type="text" value="{{ auth()->user()->name ?: '' }}"
                                                                name="name" class="form-control"
                                                                placeholder="First Name">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label>Email</label>
                                                            <input type="email" value="{{ auth()->user()->email ?: '' }}"
                                                                name="email" class="form-control" placeholder="Email">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 text-right">
                                                <button type="submit"
                                                    class="btn btn-primary mr-sm-2 mb-1 position-relative">Save
                                                    Changes</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>

                                <!-- Change Password Tab -->
                                <div class="tab-pane" id="change-password" role="tabpanel"
                                    aria-labelledby="change-password-tab">
                                    <form class="form-l" id="ajaxSubmit" data-reset="true" method="post"
                                        action="{{ route('updatePassword', auth()->user()->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <h2>Edit your password</h2>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group errorappend">
                                                    <label>Old Password</label>
                                                    <input type="text" name="old_password" class="form-control"
                                                        placeholder="Old Password">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>New Password</label>
                                                    <input type="text" name="new_password" class="form-control"
                                                        placeholder="New Password">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Confirmed New Password</label>
                                                    <input type="text" name="confirm_password" class="form-control"
                                                        placeholder="Confirmed New Password">
                                                </div>
                                            </div>
                                            <div class="col-md-12 text-">
                                                <button type="submit"
                                                    class="btn btn-primary position-relative">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>




                                <!-- Connections Tab -->
                                <div class="tab-pane" id="connections" role="tabpanel"
                                    aria-labelledby="connections-tab">

                                    <form class="form-l">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h3>Recent activity</h3>
                                            </div>
                                            <div class="col-md-12">
                                                <table style="font-size: 12px" class="table tab-des login-history">
                                                    <thead>
                                                        <tr>
                                                            <th>Access Type <span>(Browser, Mobile, etc)</span></th>
                                                            <th>Location</th>
                                                            <th>Date/Time <span>(Displayed in your time zone)</span></th>
                                                        </tr>

                                                    </thead>
                                                    <tbody>
                                                        @if (isset($users_data->loginHistories))
                                                            @foreach ($users_data->loginHistories as $item)
                                                                <tr>
                                                                    <td>
                                                                        <p>{{ $item->header }}</p>
                                                                    </td>
                                                                    <td>{{ $item->location }}</td>
                                                                    @php
                                                                        $createdAt = Carbon::parse($item->created_at);
                                                                        $currentTime = Carbon::now();

                                                                        // Calculate the difference in seconds, minutes, or hours
                                                                        $differenceInSeconds = $createdAt->diffInSeconds(
                                                                            $currentTime,
                                                                        );
                                                                        $differenceInMinutes = $createdAt->diffInMinutes(
                                                                            $currentTime,
                                                                        );
                                                                        $differenceInHours = $createdAt->diffInHours(
                                                                            $currentTime,
                                                                        );

                                                                        // Format the timestamp as "h:i A" (e.g., 1:59 PM)
                                                                        $formattedTime = $createdAt->format('h:i A');

                                                                        // Display the result based on the actual difference
                                                                        $timeDifference = '';
                                                                        if ($differenceInHours > 0) {
                                                                            $timeDifference =
                                                                                $differenceInHours .
                                                                                ' hour' .
                                                                                ($differenceInHours > 1 ? 's' : '');
                                                                        } elseif ($differenceInMinutes > 0) {
                                                                            $timeDifference =
                                                                                $differenceInMinutes .
                                                                                ' minute' .
                                                                                ($differenceInMinutes > 1 ? 's' : '');
                                                                        } else {
                                                                            $timeDifference =
                                                                                $differenceInSeconds .
                                                                                ' second' .
                                                                                ($differenceInSeconds > 1 ? 's' : '');
                                                                        }
                                                                    @endphp

                                                                    <td>{{ $formattedTime }} ({{ $timeDifference }} ago)
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Notifications Tab -->
                                <div class="tab-pane" id="notifications" role="tabpanel"
                                    aria-labelledby="notifications-tab">
                                    <div class="row">
                                        <h6 class="col-12 text-bold-400 pl-0">Activity</h6>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch1" type="checkbox" class="custom-control-input"
                                                    checked>
                                                <label for="switch1" class="custom-control-label">Email me when someone
                                                    comments on my
                                                    article</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch2" type="checkbox" class="custom-control-input"
                                                    checked>
                                                <label for="switch2" class="custom-control-label">Email me when someone
                                                    answers on my form</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch3" type="checkbox" class="custom-control-input"
                                                    disabled>
                                                <label for="switch3" class="custom-control-label">Email me when someone
                                                    follows me</label>
                                            </div>
                                        </div>
                                        <h6 class="col-12 text-bold-400 pl-0 mt-3">Application</h6>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch4" type="checkbox" class="custom-control-input"
                                                    checked>
                                                <label for="switch4" class="custom-control-label">News and
                                                    announcements</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch5" type="checkbox" class="custom-control-input"
                                                    disabled>
                                                <label for="switch5" class="custom-control-label">Weekly product
                                                    updates</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="custom-control custom-switch custom-control-inline">
                                                <input id="switch6" type="checkbox" class="custom-control-input"
                                                    checked>
                                                <label for="switch6" class="custom-control-label">Weekly blog
                                                    digest</label>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end">
                                            <button type="button" class="btn btn-primary mr-sm-2 mb-1">Save
                                                changes</button>
                                            <button type="button" class="btn btn-secondary mb-1">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Account Settings ends -->

    </div>




@endsection
