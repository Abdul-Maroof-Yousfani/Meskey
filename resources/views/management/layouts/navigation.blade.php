<div class="header-navbar navbar-expand-sm navbar navbar-horizontal navbar-fixed navbar-light navbar-shadow menu-border navbar-brand-center"
    role="navigation" data-menu="menu-wrapper">
    <div class="navbar-container main-menu-content center-layout" data-menu="menu-container">
        <ul class="navigation-main nav navbar-nav" id="main-menu-navigation" data-menu="menu-navigation">
            @php
                $menus = getMenu();
             @endphp
            @foreach($menus as $menu)
                @canAccess($menu->permission_name) {{-- Assuming permission relationship exists --}}
                <li class="nav-item {{$menu->children->isNotEmpty() ? 'dropdown' : ''}}" data-menu="dropdown">
                    <a class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                        data-toggle="dropdown">
                        <i class="{{ $menu->icon }}"></i><span data-i18n="{{ $menu->name }}">{{ $menu->name }}</span>
                    </a>
                    @if($menu->children->isNotEmpty())

                        <ul class="dropdown-menu">
                            @foreach($menu->children as $child)
                                @canAccess($child->permission_name)
                                <li data-menu="">
                                    <a class="dropdown-item d-flex align-items-center" href="{{ url($child->route) }}"
                                        data-toggle="dropdown">
                                        <i class="ft-arrow-right submenu-icon"></i><span
                                            data-i18n="{{ $child->name }}">{{ $child->name }}</span>
                                    </a>
                                </li>
                                @endcanAccess
                            @endforeach
                        </ul>
                    @endif
                </li>
                @endcanAccess
            @endforeach





            <li class="nav-item" data-menu="dropdown">
                <a class="dropdown-toggle nav-link d-flex align-items-center"
                    onclick="loadPageContent('{{ url('dashboard') }}')" data-toggle="dropdown"><i
                        class="ft-home"></i><span data-i18n="Dashboard">Dashboard</span></a>
            </li>
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-aperture"></i><span data-i18n="UI Kit">Arrival</span></a>


                <ul class="dropdown-menu">
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                    href="{{ route('ticket.index') }}"
                            onclick="loadPageContent('{{ route('ticket.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span data-i18n="Email">Ticket</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                    href="#"
                           data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span data-i18n="Email">First
                                Inpection</span></a>
                    </li>
                    {{-- <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            @routerLink(route('users.index')) data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span data-i18n="Chat">Manccccage
                                Users</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center" href="{{ route('menu.index') }}"
                            onclick="loadPageContent('{{ route('menu.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Menu</span>
                        </a>
                    </li> --}}
                </ul>

            </li>
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-box"></i><span data-i18n="Apps">Access
                        Control</span></a>
                <ul class="dropdown-menu">
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('roles.index') }}" onclick="loadPageContent('{{ route('roles.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Email">Manage Roles &
                                Permission</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('company.index') }}"
                            onclick="loadPageContent('{{ route('company.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span data-i18n="Email">Manage
                                Company</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('users.index') }}" onclick="loadPageContent('{{ route('users.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Chat">Manage
                                Users</span></a>
                    </li>
                    {{-- <li data-menu=""><a class="dropdown-item d-flex align-items-center" href="{{ route('menu.index') }}"
                            onclick="loadPageContent('{{ route('menu.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Menu</span>
                        </a>
                    </li> --}}
                </ul>
            </li>
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-grid"></i><span data-i18n="Tables">Master
                        Control</span></a>
                <ul class="dropdown-menu">
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Manage Product</span></a>
                        <ul class="dropdown-menu">

                            @canAccess('product')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('product.index') }}"
                                    onclick="loadPageContent('{{ route('product.index') }}')" data-toggle="dropdown"><i
                                        class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Product</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('product')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('product-slab.index') }}"
                                    onclick="loadPageContent('{{ route('product-slab.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Product Slab</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('product')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('product-slab-type.index') }}"
                                    onclick="loadPageContent('{{ route('product-slab-type.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Product Slab type</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('category')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('category.index') }}"
                                    onclick="loadPageContent('{{ route('category.index') }}')" data-toggle="dropdown"><i
                                        class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Category</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('uom')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('unit_of_measure.index') }}"
                                    onclick="loadPageContent('{{ route('unit_of_measure.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Unit of mesurement</span></a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>

                    <li data-menu=""><a class="dropdown-item d-flex align-items-center" href="{{ route('supplier.index') }}"
                            onclick="loadPageContent('{{ route('supplier.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Supplier</span>
                        </a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center" href="{{ route('broker.index') }}"
                            onclick="loadPageContent('{{ route('broker.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Broker</span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</div>