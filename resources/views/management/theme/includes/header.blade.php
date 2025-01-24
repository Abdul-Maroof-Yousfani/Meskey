<!-- BEGIN: Header-->
<nav
    class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow container-xxl"
>
    <div class="navbar-container d-flex content">
        <div class="bookmark-wrapper align-items-center">
            
            <ul class="nav navbar-nav bookmark-icons align-items-center">
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link" href="{{ route('dashboard.index') }}">
                        <img src="../theme/assets/images/logo.png" /> Dashboard
                    </a>
                </li>
                <li class=" ma-l">
                    <a class="Navclose nav-link " href="#">
                        <i class="fa-solid fa-bars"></i>
                    </a>
                </li>
                <li class="secli">
                    <form class="example" action="/action_page.php">
                        <input
                            class="fom-sear"
                            type="text"
                            placeholder="Search Anything"
                            name="search"
                        />
                        <button class="fom-sear-btn" type="submit">
                            <i class="fa fa-search"></i>
                        </button>
                    </form>
                </li>
                <li class="thirli">
                    <div class="time" id="currentTime"></div>
                </li>
            </ul>
        </div>
        <ul class="nav navbar-nav align-items-center">
            <li class="nav-item dropdown dropdown-user">
                <a
                    class="nav-link dropdown-toggle dropdown-user-link"
                    id="dropdown-user"
                    href="javascript:void(0);"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <span class="avatar">
                        <img
                            class="round"
                            src="../theme/assets/images/portrait/small/avatar-s-11.jpg"
                            alt="avatar"
                            height="40"
                            width="40"
                        />
                        <span class="avatar-status-online"></span>
                    </span>
                    <div class="user-nav d-sm-flex d-none">
                        <span class="user-name font-weight-bolder">
                            {{ auth()->user()->user_name }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </div>
                </a>
                <div
                    class="dropdown-menu dropdown-menu-right cus-b"
                    aria-labelledby="dropdown-user"
                >
                    <div class="text-center drop-c">
						<div class="avr">
							<img
                            class="round"
                            src="../theme/assets/images/portrait/small/avatar-s-11.jpg"
                            alt="avatar"
                            height="40"
                            width="40"
                        />
						</div>
                       
                        <div class="dropc-ctn">
							<h1>{{ auth()->user()->user_name }}</h1>
							<h4>Amir@innovative-net.com</h4>
                        <h3>Administrator</h3>
						</div>
                    </div>
                    <div class="drop-st text-center">
                        <div class="proset">
                            <div class="dps">
                                <div class="ps-span">
                                    <span><i class="fas fa-user"></i></span>
                                </div>
                                <div class="ps-ctn">
                                    <h6>Profile Setting</h6>
                                    <a
                                        onclick="openModal('{{
                                            route('usersSetting.users_detail')
                                        }}')"
                                        href="#"
                                    >
                                        Change</a
                                    >
                                </div>
                            </div>
                        </div>
						<div class="th proset">
                            <div class="dps">
                                <div class="ps-span">
                                    <span><i class="fas fa-paint-brush"></i></span>
                                </div>
                                <div class="ps-ctn">
                                    <h6>Theme Setting</h6>
									<a
									href="#"
									data-toggle="modal"
									data-target="#change-theme-modal"
									class="set-btn"
									>
									Change</a
								>
                                </div>
                            </div>
                        </div>
                   
                    </div>
                   
                    <div class="text-center">
                        <!-- <a href="#" class="log-btn">Logout</a> -->
                        <a
                            class="log-btn"
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        >
                            <i class="mr-50" data-feather="power"></i>
                            {{ __("Logout") }}
                        </a>
                        <form
                            id="logout-form"
                            action="{{ route('logout') }}"
                            method="POST"
                            class="d-none"
                        >
                            @csrf
                        </form>
                    </div>
                    <!-- <a onclick="openModal('{{ route('usersSetting.users_detail') }}')" class="dropdown-item" href="#">
						<i class="mr-50" data-feather="settings"></i>
						Profile
					</a>			
					<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-user">
						<a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
							<i class="mr-50" data-feather="power"></i>
							{{ __('Logout') }}
						</a>
						<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
							@csrf
						</form>
					</div> -->
                </div>
            </li>
        </ul>
    </div>
</nav>
<ul class="main-search-list-defaultlist d-none">
    <li class="d-flex align-items-center">
        <a href="javascript:void(0);">
            <h6 class="section-label mt-75 mb-0">Files</h6>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        src="../theme/assets/images/icons/xls.png"
                        alt="png"
                        height="32"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Two new item submitted</p>
                    <small class="text-muted">Marketing Manager</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;17kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        src="../theme/assets/images/icons/jpg.png"
                        alt="png"
                        height="32"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">52 JPG file Generated</p>
                    <small class="text-muted">FontEnd Developer</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;11kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        src="../theme/assets/images/icons/pdf.png"
                        alt="png"
                        height="32"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">25 PDF File Uploaded</p>
                    <small class="text-muted">Digital Marketing Manager</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;150kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        src="../theme/assets/images/icons/doc.png"
                        alt="png"
                        height="32"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Anna_Strong.doc</p>
                    <small class="text-muted">Web Designer</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;256kb</small>
        </a>
    </li>
    <li class="d-flex align-items-center">
        <a href="javascript:void(0);">
            <h6 class="section-label mt-75 mb-0">Members</h6>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view.html"
        >
            <div class="d-flex align-items-center">
                <div class="avatar mr-75">
                    <img
                        src="../theme/assets/images/portrait/small/avatar-s-8.jpg"
                        alt="png"
                        height="32"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Hasan Saleem</p>
                </div>
            </div>
        </a>
    </li>
</ul>
<ul class="main-search-list-defaultlist-other-list d-none">
    <li class="auto-suggestion justify-content-between">
        <a
            class="d-flex align-items-center justify-content-between w-100 py-50"
        >
            <div class="d-flex justify-content-start">
                <span class="mr-75" data-feather="alert-circle"></span
                ><span>No results found.</span>
            </div>
        </a>
    </li>
</ul>

<!-- END: Header-->
