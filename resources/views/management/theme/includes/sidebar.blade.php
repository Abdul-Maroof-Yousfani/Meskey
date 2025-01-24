
<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true" style="width: 260px; position: fixed; margin-top: 70px;">
   
    <div class="main-menu-content">
        <ul class="dan navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-list-alt"></i>
                    <span class="menu-title text-truncate">
                        Space
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="#">
                            <span class="menu-item text-truncate">
                                Project
                            </span>
                        </a>
                    </li>
                </ul>
            </li>



            <!-- <li class=" nav-item"><a class="d-flex align-items-center" href="#"><i class="fa-solid fa-timeline"></i></i><span class="menu-title text-truncate">Lead</span></a>
            </li>  -->

            <!-- <li class="nav-item">
                <a class="d-flex align-items-center" href="{{route('leads.index')}}">
                    <i class="fas fa-tasks-alt"></i>
                    <span class="menu-title text-truncate">
                        Lead
                    </span>
                </a>
            </li> -->
            @canany(['company'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-building"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Company
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('company.index')}}">
                            <span class="menu-item text-truncate">
                                All Companies
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['lead'])
            <li class=" nav-item"><a class="d-flex align-items-center" href="#"><i class="fas fa-tasks"></i><span class="menu-title text-truncate"> &nbsp; Lead</span></a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('dashboard.index')}}">
                            <span class="menu-item text-truncate">
                                Leads Dashboard
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="d-flex align-items-center" href="{{route('leads.index')}}">
                            <span class="menu-item text-truncate">
                                Leads View
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="d-flex align-items-center" href="{{route('leadsource')}}">
                            <span class="menu-item text-truncate">
                                Lead Sourcing
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccessany
            @canany(['contact'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-address-book"></i>
                    <span class="menu-title text-truncate">
                     &nbsp; Contact
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('contact.index')}}">
                            <span class="menu-item text-truncate">
                                All Contacts
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @canany(['calls'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-mobile-alt"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Calls
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('calllist')}}">
                            <span class="menu-item text-truncate">
                                Calls List
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['marketing'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-bullhorn"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Marketing
                    </span>
                </a>
                    <ul class="menu-content">
                        <li>
                            <a class="d-flex align-items-center" href="{{route('emaillist')}}">
                                <span class="menu-item text-truncate">
                                    Email
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    SMS
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    Facebook Ads
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    SEO
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @canany(['deals'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-handshake"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Deals
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="#">
                            <span class="menu-item text-truncate">
                                Deals Sub
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['social'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-share-alt"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Social Media
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="#">
                            <span class="menu-item text-truncate">
                                Social Media Sub
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['pages'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-file-alt"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Pages
                    </span>
                </a>
                <ul class="menu-content">
                    {{--<li>--}}
                        {{--<a class="d-flex align-items-center" href="{{route('page.create')}}">--}}
                            {{--<span class="menu-item text-truncate">--}}
                                {{--Create Page--}}
                            {{--</span>--}}
                        {{--</a>--}}
                    {{--</li>--}}
                    <li>
                        <a class="d-flex align-items-center" href="{{route('page.index')}}">
                            <span class="menu-item text-truncate">
                                View Page
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="d-flex align-items-center" href="{{route('viewAnalytics')}}">
                            <span class="menu-item text-truncate">
                                View Analytics
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['template'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="far fa-folder-open"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Templates
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('template.index')}}">
                            <span class="menu-item text-truncate">
                                Add Templates
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['super Admin'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-user-shield"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Super Admin
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('superadminsettings')}}">
                            <span class="menu-item text-truncate">
                                Super Admin Settings
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['admin'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-user-cog"></i>
                    <span class="menu-title text-truncate">
                    &nbsp; Admin
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('adminsettings')}}">
                            <span class="menu-item text-truncate">
                                Admin Settings
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess

            @canany(['settings'])
            <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                <i class="fas fa-sliders-h"></i>
                    <span class="menu-title text-truncate">
                        &nbsp; Settings
                    </span>
                </a>
                <ul class="menu-content">
                    <li>
                        <a class="d-flex align-items-center" href="{{route('settings')}}">
                            <span class="menu-item text-truncate">
                                Edit Settings
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="d-flex align-items-center" href="{{route('usersSetting.emailList')}}">
                            <span class="menu-item text-truncate">
                                Email Settings
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcanAccess
            <!-- <li class=" nav-item">
                <a class="d-flex align-items-center" href="#">
                    <i class="fa-solid fa-phone-volume"></i>
                    <span class="menu-title text-truncate">
                        Calls
                    </span>
                </a>
                    <ul class="menu-content">
                        <li>
                            <a class="d-flex align-items-center" href="add-new-product.php">
                                <span class="menu-item text-truncate">
                                    New Product
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="product-list.php">
                                <span class="menu-item text-truncate">
                                    Product List
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    Product Type
                                </span>
                            </a>
                            <ul class="menu-content">
                                <li>
                                    <a class="d-flex align-items-center" href="add-new-product-type.php">
                                        <span class="menu-item text-truncate">
                                            New Product Type
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a class="d-flex align-items-center" href="manage-product-type.php">
                                        <span class="menu-item text-truncate">
                                            Manage Product Type
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    Brands
                                </span>
                            </a>
                            <ul class="menu-content">
                                <li>
                                    <a class="d-flex align-items-center" href="add-new-brand.php">
                                        <span class="menu-item text-truncate">
                                            New Brand
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a class="d-flex align-items-center" href="brand-list.php">
                                        <span class="menu-item text-truncate">
                                            Brand List
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="d-flex align-items-center" href="#">
                                <span class="menu-item text-truncate">
                                    Product Unit
                                </span>
                            </a>
                            <ul class="menu-content">
                                <li>
                                    <a class="d-flex align-items-center" href="add-new-product-unit.php">
                                        <span class="menu-item text-truncate">
                                            New Product Unit
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a class="d-flex align-items-center" href="manage-product-unit.php">
                                        <span class="menu-item text-truncate">
                                            Manage Product Unit
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li> -->

        </ul>
    </div>
</div>
