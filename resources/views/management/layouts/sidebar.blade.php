<div>
    <!-- Left Sidebar -->
    <aside id="leftsidebar" class="sidebar">
        <!-- Menu -->
        <div class="menu">
            <ul class="list pt-4">
                <li class="sidebar-user-panel active">
                    <div class="contact-photo">
                        {{-- @if (auth()->user()->erp_image == null) --}}
                        {{-- <div class="user-panel"> --}}
                        {{-- <div class=" image"> --}}
                        {{-- <a href="{{url('myprofile')}}" class="p-0 mr-5" style="width:80px;" > --}}
                        {{-- <img  src="{{asset('management/images/profile.png')}}" class="img-circle user-img-circle" alt="User Image"/> --}}
                        {{-- </a> --}}
                        {{-- </div> --}}
                        {{-- </div> --}}
                        {{-- @else --}}
                        {{-- <div class="user-panel"> --}}
                        {{-- <div class=" image"> --}}
                        {{-- <a href="{{url('myprofile')}}" class="p-0 mr-5" style="width:80px;" > --}}
                        {{-- <img  src="{{asset('image/announcement'.'/'.auth()->user()->erp_image)}}" class="img-circle user-img-circle" alt="User Image"/> --}}
                        {{-- </a> --}}
                        {{-- </div> --}}
                        {{-- </div> --}}
                        {{-- @endif --}}
                        <div class="profile-usertitle ml-4">
                            {{-- <div class="sidebar-userpic-name">{{App\Helpers\Sc::getUserProfile()->name}}
                        </div> --}}
                        {{-- <div class="profile-usertitle-job ">{{App\Helpers\Sc::getUserProfile()->user_type}}
                    </div> --}}
        </div>
</div>
</li>

{{-- <li> --}}
{{-- <a href="{{url('/')}}" target="_blank"> --}}
{{-- <i class="fas fa-eye"></i> --}}
{{-- <span>View Store</span> --}}
{{-- </a> --}}
{{-- </li> --}}
<li class="sidebar-user-panel {{ Request::routeIs('admin.dashboard') ? 'active' : '' }}">
    <a href="{{ url('admin/dashboard') }}">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
</li>

@canAccess('event')
<li class="{{ Request::routeIs('event.index', 'event.*') ? 'active' : '' }}">
    <a href="{{ route('event.index') }}">
        <i class="fas fa-edit"></i>
        <span>Events</span>
    </a>
</li>
@endcanAccess

@canAccess('gallery')
<li class="{{ Request::routeIs('gallery.index', 'gallery.*') ? 'active' : '' }}">
    <a href="{{ route('gallery.index') }}">
        <i class="fas fa-square"></i>
        <span>Gallery</span>
    </a>
</li>
@endcanAccess



@canAccess('order')
<li class="{{ Request::routeIs('space.index', 'space.*') ? 'active' : '' }}">
    <a href="{{ route('space.index') }}">
        <i class="fas fa-square"></i>
        <span>Types</span>
    </a>
</li>
@endcanAccess

@canAccess('discount')
<li class="{{ Request::routeIs('discount.index', 'discount.*') ? 'active' : '' }}">
    <a href="{{ route('discount.index') }}">
        <i class="fas fa-tags"></i>
        <span>Coupon Code</span>
    </a>
</li>
@endcanAccess

@canAccess('newsletter')
<li class="{{ Request::routeIs('newsletter.index', 'newsletter.*') ? 'active' : '' }}">
    <a href="{{ route('newsletter.index') }}">
        <i class="fas fa-envelope"></i>
        <span>Newsletters</span>
    </a>
</li>
@endcanAccess

@canAccess('role')
<li class="{{ Request::routeIs('roles.index', 'roles.*') ? 'active' : '' }}">
    <a onClick="return false;" class="menu-toggle" href="">
        <i class="fas fa-unlock-alt"></i>
        <span>Roles | Permission</span>
    </a>
    <ul class="ml-menu">
        <li class="{{ Request::routeIs('roles.index') ? 'active' : '' }}">
            <a href="{{ route('roles.index') }}">
                <span>Roles</span>
            </a>
        </li>
    </ul>
</li>
@endcanAccess
@canAccess('role')
<li class="{{ Request::routeIs('athletes.index', 'athletes.*') ? 'active' : '' }}">
    <a href="{{ route('athletes.index') }}">
        <i class="fas fa-envelope"></i>
        <span>Athletes Profiles</span>
    </a>
</li>
@endcanAccess
@canAccess('user')
<li class="{{ Request::routeIs('admin.users', 'admin.users.*') ? 'active' : '' }}">
    <a href="{{ url('admin/users') }}">
        <i class="fas fa-user"></i>
        <span>Users Management</span>
    </a>
</li>
@endcanAccess
@canAccess('user')
<li class="{{ Request::routeIs('studio.*') ? 'active' : '' }}">
    <a href="{{ route('studio.index') }}">
        <i class="fas fa-square"></i>
        <span>Studio</span>
    </a>
</li>
@endcanAccess
{{-- <li>--}}
{{-- <form class="m-0" method="POST" action="{{ route('logout') }}">--}}
{{-- @csrf--}}
{{-- <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">--}}
{{-- <i class="fas fa-power-off"></i> <span>{{ __('Log Out') }}</span>--}}
{{-- </a>--}}
{{-- </form>--}}
{{-- </li>--}}

</ul>
</div>
<!-- #Menu -->
{{-- <div class="copyright">--}}
{{-- Powered By <a href="https://eliteblue.net/" class="text-dark d-block" target="_blank">Elite Blue--}}
{{-- Technologies</a>--}}
{{-- </div>--}}
</aside>
<!-- #END# Left Sidebar -->
<!-- Right Sidebar -->
<aside id="rightsidebar" class="right-sidebar">
    <ul class="nav nav-tabs tab-nav-right" role="tablist">
        <li role="presentation">
            <a href="#skins" data-toggle="tab" class="active">SKINS</a>
        </li>
        {{-- <li role="presentation"> --}}
        {{-- <a href="#settings" data-toggle="tab">SETTINGS</a> --}}
        {{-- </li> --}}
    </ul>
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane in active in active stretchLeft" id="skins">
            <div class="demo-skin">

                <div class="rightSetting">
                    <p>SIDEBAR MENU COLORS</p>
                    <button type="button"
                        class="btn btn-sidebar-light btn-border-radius p-l-20 p-r-20">Light</button>
                    <button type="button"
                        class="btn btn-sidebar-dark btn-default btn-border-radius p-l-20 p-r-20">Dark</button>
                </div>
                <div class="rightSetting">
                    <p>THEME COLORS</p>
                    <button type="button"
                        class="btn btn-theme-light btn-border-radius p-l-20 p-r-20">Light</button>
                    <button type="button"
                        class="btn btn-theme-dark btn-default btn-border-radius p-l-20 p-r-20">Dark</button>
                </div>
                <div class="rightSetting">
                    <p>SKINS</p>
                    <ul class="demo-choose-skin choose-theme list-unstyled">
                        <li data-theme="black" class="actived">
                            <div class="black-theme"></div>
                        </li>
                        <li data-theme="white">
                            <div class="white-theme white-theme-border"></div>
                        </li>
                        <li data-theme="purple">
                            <div class="purple-theme"></div>
                        </li>
                        <li data-theme="blue">
                            <div class="blue-theme"></div>
                        </li>
                        <li data-theme="cyan">
                            <div class="cyan-theme"></div>
                        </li>
                        <li data-theme="green">
                            <div class="green-theme"></div>
                        </li>
                        <li data-theme="orange">
                            <div class="orange-theme"></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane stretchRight" id="settings">
            <div class="demo-settings">
                <p>GENERAL SETTINGS</p>
                <ul class="setting-list">
                    <li>
                        <span>Report Panel Usage</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox" checked>
                                <span class="lever switch-col-green"></span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <span>Email Redirect</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox">
                                <span class="lever switch-col-blue"></span>
                            </label>
                        </div>
                    </li>
                </ul>
                <p>SYSTEM SETTINGS</p>
                <ul class="setting-list">
                    <li>
                        <span>Notifications</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox" checked>
                                <span class="lever switch-col-purple"></span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <span>Auto Updates</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox" checked>
                                <span class="lever switch-col-cyan"></span>
                            </label>
                        </div>
                    </li>
                </ul>
                <p>ACCOUNT SETTINGS</p>
                <ul class="setting-list">
                    <li>
                        <span>Offline</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox" checked>
                                <span class="lever switch-col-red"></span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <span>Location Permission</span>
                        <div class="switch">
                            <label>
                                <input type="checkbox">
                                <span class="lever switch-col-lime"></span>
                            </label>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</aside>
<!-- #END# Right Sidebar -->
</div>
<style>
    .copyright {
        position: absolute;
        bottom: 0 !important;
        width: 100% !important;
        text-align: center;
        font-size: 13px;
        padding: 15px;
        background: #F0F0F0;
        border-top: 1px solid #f1f2f7;
        z-index: 999;
    }

    .sidebar .menu .list {
        padding-bottom: 80px;
    }
</style>