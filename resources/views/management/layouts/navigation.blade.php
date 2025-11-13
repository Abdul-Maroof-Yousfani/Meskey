<div class="header-navbar navbar-expand-sm navbar navbar-horizontal navbar-fixed navbar-light navbar-shadow menu-border navbar-brand-center"
    role="navigation" data-menu="menu-wrapper">
    <div class="navbar-container main-menu-content center-layout" data-menu="menu-container">
        <ul class="navigation-main nav navbar-nav" id="main-menu-navigation" data-menu="menu-navigation">
            @php
                $menus = getMenu();
            @endphp
            @foreach ($menus as $menu)
                @canAccess($menu->permission_name)
                <li class="nav-item {{ $menu->children->isNotEmpty() ? 'dropdown' : '' }}" data-menu="dropdown">
                    <a class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                        data-toggle="dropdown">
                        <i class="{{ $menu->icon }}"></i><span data-i18n="{{ $menu->name }}">{{ $menu->name }}</span>
                    </a>
                    @if ($menu->children->isNotEmpty())
                        <ul class="dropdown-menu">
                            @foreach ($menu->children as $child)
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

            @canAccess('dashboard')
            <li class="nav-item" data-menu="dropdown">
                <a class="dropdown-toggle nav-link d-flex align-items-center" href="{{ url('dashboard') }}"
                    data-toggle="dropdown"><i class="ft-home"></i><span data-i18n="Dashboard">Dashboard</span></a>
            </li>
            @endcanAccess
            @canAccess('arrival')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-truck"></i><span data-i18n="UI Kit">Arrival</span></a>
                <ul class="dropdown-menu">
                    @canAccess('arrival-ticket')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('ticket.index') }}" onclick="loadPageContent('{{ route('ticket.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span>Ticket</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-initial-sampling')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('initialsampling.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Initial
                                Sampling</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-initial-re-sampling')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('initial-resampling.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Initial
                                Re-Sampling</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-purchaser-approval')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('sampling-monitoring.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Purchaser's
                                Approval</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-location-transfer')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('location-transfer.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Location
                                Transfer</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-first-weighbridge')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('first-weighbridge.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>First
                                Weighbridge</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-request-for-inner-sampling')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('inner-sampling-request.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Request For Inner
                                Sampling</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-inner-sampling')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('inner-sampling.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Inner
                                Sampling</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-inner-re-sampling')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('inner-resampling.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Inner
                                Re-Sampling</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-half-full-approved')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('arrival-approve.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Half/Full
                                Approved</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-second-weighbridge')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('second-weighbridge.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Second
                                Weighbridge</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-freight-management')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('freight.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Freight
                                Management</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-slip')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('arrival-slip.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Arrival Slip
                            </span></a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-slip')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('raw-material.gate-buy.payment-request.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Generate Bill (Gate-Buy)
                            </span></a>
                    </li>
                    @endcanAccess
                </ul>
            </li>
            @endcanAccess
            @canAccess('procurement-raw-material')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-book"></i><span data-i18n="UI Kit">Purchase
                        Contract</span></a>
                <ul class="dropdown-menu">
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('raw-material.purchase-order.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Purchase Order
                            </span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('raw-material.gate-buying.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Gate Buying
                            </span></a>
                    </li>
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Purchase Sampling</span></a>
                        <ul class="dropdown-menu">
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.purchase-sampling-request.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.purchase-sampling-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Qc Request</span>
                                </a>
                            </li>
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.purchase-sampling.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.purchase-sampling.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Purchase Sampling/QC</span>
                                </a>
                            </li>
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.purchase-resampling.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.purchase-resampling.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Purchase Re-Sampling/QC</span>
                                </a>
                            </li>
                            @canAccess('procurement-raw-material-purchaser-approval')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.sampling-monitoring.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.sampling-monitoring.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Purchaser's Approval</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('procurement-raw-material-loading')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.freight.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.freight.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Loading Management</span>
                                </a>
                            </li>
                            @endcanAccess

                        </ul>
                    </li>


                    @canAccess('procurement-raw-material-payment-management')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Payment Management</span></a>
                        <ul class="dropdown-menu">
                            @canAccess('p-r-m-advance-payment-request')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.advance-payment-request.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.advance-payment-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Advance Payment Request</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('p-r-m-payment-request-thadda')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.payment-request.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.payment-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Payment Request (Thadda)</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('p-r-m-payment-request-pohouch')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.ticket.payment-request.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.ticket.payment-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Payment Request (Pohouch)</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('p-r-m-payment-request-pohouch')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.freight-request.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.freight-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Freight Payment Request</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('p-r-m-payment-request-approvals')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.payment-request-approval.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.payment-request-approval.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Payment Request Approval's</span>
                                </a>
                            </li>
                            @endcanAccess
                            @canAccess('stock-in-transit-vehicles')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.sit-vehicle.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.sit-vehicle.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Stock in Transit Vehicles</span>
                                </a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('procurement-raw-material-contract-selection')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Contract Linking</span></a>
                        <ul class="dropdown-menu">
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.ticket-contracts.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.ticket-contracts.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Pending Tickets</span>
                                </a>
                            </li>
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('raw-material.verified-contracts.index') }}"
                                    onclick="loadPageContent('{{ route('raw-material.verified-contracts.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Verified Tickets</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('procurement-raw-material-inicative-price')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('indicative-prices.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Indicative Prices
                            </span></a>
                    </li>
                    @endcanAccess
                </ul>
            </li>
            @endcanAccess
            @canAccess('procurement-store')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-book"></i><span data-i18n="UI Kit">Store
                        Management</span></a>
                <ul class="dropdown-menu">
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('store.purchase-request.index') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Purchase Request
                            </span></a>
                    </li>
                    <li data-menu=""><a
                            class="dropdown-item d-flex align-items-center"
                            href="{{ route('store.purchase-quotation.comparison-list') }}" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span data-i18n="Bootstrap Tables">Purchase
                                Quotation
                            </span></a>
                        {{-- <ul class="dropdown-menu">
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.purchase-quotation.index') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-quotation.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">List</span>
                                </a>
                            </li>
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.purchase-quotation.comparison-list') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-quotation.comparison-list') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">comparison</span>
                                </a>
                            </li>
                        </ul> --}}
                    </li>
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Purchase Order</span></a>
                        <ul class="dropdown-menu">
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.purchase-order.index') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-order.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Purchase Order</span>
                                </a>
                            </li>
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.purchase-order-receiving.index') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-order-receiving.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Goods Received Note</span>
                                </a>
                            </li>

                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.qc.get') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-order-receiving.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">QC</span>
                                </a>
                            </li>

                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('store.purchase-order-payment-request.index') }}"
                                    onclick="loadPageContent('{{ route('store.purchase-order-payment-request.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                                    <span data-i18n="Task Board">Payment Request</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            @endcanAccess
            @canAccess('finance')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-dollar-sign"></i><span data-i18n="Apps">Finance</span></a>
                <ul class="dropdown-menu">
                    @canAccess('finance-chart-of-account')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('account.index') }}"
                            onclick="loadPageContent('{{ route('account.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Chart of Account</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('payment-voucher')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('payment-voucher.index') }}"
                            onclick="loadPageContent('{{ route('payment-voucher.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Payment Vouchers</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('journal-voucher')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('journal-voucher.index') }}"
                            onclick="loadPageContent('{{ route('journal-voucher.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Journal Vouchers</span>
                        </a>
                    </li>
                    @endcanAccess
                    {{-- <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('menu.index') }}" onclick="loadPageContent('{{ route('menu.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Menu</span>
                        </a>
                    </li> --}}
                </ul>
            </li>
            @endcanAccess
            @canAccess('access-control')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-user-check"></i><span data-i18n="Apps">Access
                        Control</span></a>
                <ul class="dropdown-menu">
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('roles.index') }}" onclick="loadPageContent('{{ route('roles.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span>Manage Roles &
                                Permission</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('company.index') }}"
                            onclick="loadPageContent('{{ route('company.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Manage
                                Company</span></a>
                    </li>
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('users.index') }}" onclick="loadPageContent('{{ route('users.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Chat">Manage
                                Users</span></a>
                    </li>
                </ul>
            </li>
            @endcanAccess
            @canAccess('reports')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-file-text"></i><span data-i18n="Apps">Reports</span></a>
                <ul class="dropdown-menu">
                    @canAccess('report-arrival-report')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu">
                        <a class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown">
                            <i class="ft-arrow-right submenu-icon"></i><span data-i18n="Bootstrap Tables">Arrival</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li data-menu="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('arrival-history.index') }}"
                                    onclick="loadPageContent('{{ route('arrival-history.index') }}')"
                                    data-toggle="dropdown">
                                    <i class="ft-arrow-right submenu-icon"></i><span data-i18n="Basic">Arrival
                                        Report</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('report-ledger-report')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ url('transactions/report') }}"
                            onclick="loadPageContent('{{ url('transactions/report') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i><span>Ledger
                                Reports</span></a>
                    </li>
                    @endcanAccess
                    @canAccess('report-indicative-price')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('indicative-prices.reports') }}"
                            onclick="loadPageContent('{{ route('indicative-prices.reports') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span>Indicative Price
                                Reports</span></a>
                    </li>
                    @endcanAccess

                </ul>
            </li>
            @endcanAccess
            @canAccess('master')
            <li class="dropdown nav-item" data-menu="dropdown"><a
                    class="dropdown-toggle nav-link d-flex align-items-center" href="javascript:;"
                    data-toggle="dropdown"><i class="ft-grid"></i><span data-i18n="Tables">Master
                        Control</span></a>
                <ul class="dropdown-menu">
                    @canAccess('product')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Manage Locations</span></a>
                        <ul class="dropdown-menu">
                            @canAccess('company-locations')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('company-location.index') }}"
                                    onclick="loadPageContent('{{ route('company-location.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Company Locations</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('arrival-locations')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('arrival-location.index') }}"
                                    onclick="loadPageContent('{{ route('arrival-location.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Arrival Locations</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('arrival-sub-locations')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('arrival-sub-location.index') }}"
                                    onclick="loadPageContent('{{ route('arrival-sub-location.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Arrival Sub Locations</span></a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('product')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Manage Product</span></a>
                        <ul class="dropdown-menu">
                            @canAccess('category')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('category.index') }}"
                                    onclick="loadPageContent('{{ route('category.index') }}')" data-toggle="dropdown"><i
                                        class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Category</span></a>
                            </li>
                            @endcanAccess
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
                                    href="{{ route('product-slab-type.index') }}"
                                    onclick="loadPageContent('{{ route('product-slab-type.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Product Slab type</span></a>
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

                            @canAccess('uom')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('unit_of_measure.index') }}"
                                    onclick="loadPageContent('{{ route('unit_of_measure.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Unit of mesurement</span></a>
                            </li>
                            @endcanAccess


                            @canAccess('sizes')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('color.index') }}"
                                    onclick="loadPageContent('{{ route('color.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Colors</span></a>
                            </li>
                            @endcanAccess


                            @canAccess('sizes')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('size.index') }}"
                                    onclick="loadPageContent('{{ route('size.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Sizes</span></a>
                            </li>
                            @endcanAccess

                            @canAccess('payment-terms')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('payment-term.index') }}"
                                    onclick="loadPageContent('{{ route('payment-term.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Payment Terms</span></a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>
                    @endcanAccess



                    @canAccess('manage-arrival')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Manage Arrival</span></a>
                        <ul class="dropdown-menu">

                            @canAccess('truck-type')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('truck-type.index') }}"
                                    onclick="loadPageContent('{{ route('truck-type.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Truck Type</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('truck-size-range')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('truck-size-ranges.index') }}"
                                    onclick="loadPageContent('{{ route('truck-size-ranges.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Truck Size Range</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('station')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('station.index') }}"
                                    onclick="loadPageContent('{{ route('truck-type.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Stations</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('qc-relief')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('qc-relief.index') }}"
                                    onclick="loadPageContent('{{ route('qc-relief.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Basic">Qc Relief</span></a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('manage-production')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu"><a
                            class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                data-i18n="Bootstrap Tables">Manage Production</span></a>
                        <ul class="dropdown-menu">

                            @canAccess('plant')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('plant.index') }}"
                                    onclick="loadPageContent('{{ route('plant.index') }}')" data-toggle="dropdown"><i
                                        class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Plants</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('production-machine')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('production-machine.index') }}"
                                    onclick="loadPageContent('{{ route('production-machine.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Production Machines</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('fumigation-company')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('fumigation-company.index') }}"
                                    onclick="loadPageContent('{{ route('fumigation-company.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Fumigation Companies</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('inspection-company')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('inspection-company.index') }}"
                                    onclick="loadPageContent('{{ route('inspection-company.index') }}')"
                                    data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Inspection Companies</span></a>
                            </li>
                            @endcanAccess
                            @canAccess('brands')
                            <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('brands.index') }}"
                                    onclick="loadPageContent('{{ route('brands.index') }}')" data-toggle="dropdown"><i
                                        class="ft-arrow-right submenu-icon"></i><span
                                        data-i18n="Extended">Brands</span></a>
                            </li>
                            @endcanAccess
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('raw-material-supplier')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('supplier.index') }}"
                            onclick="loadPageContent('{{ route('supplier.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Suppliers</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('raw-material-broker')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('broker.index') }}" onclick="loadPageContent('{{ route('broker.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Brokers</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('raw-material-supplier')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('vendor.index') }}" onclick="loadPageContent('{{ route('vendor.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Vendors</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('approval-modules')
                    <li class="dropdown dropdown-submenu" data-menu="dropdown-submenu">
                        <a class="dropdown-item d-flex align-items-center dropdown-toggle" href="javascript:;"
                            data-toggle="dropdown">
                            <i class="ft-arrow-right submenu-icon"></i><span data-i18n="Bootstrap Tables">Approval
                                Workflow</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li data-menu="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('approval-modules.index') }}"
                                    onclick="loadPageContent('{{ route('approval-modules.index') }}')"
                                    data-toggle="dropdown">
                                    <i class="ft-arrow-right submenu-icon"></i><span data-i18n="Basic">Approval
                                        Modules</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcanAccess
                    @canAccess('manage-divisions')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('division.index') }}"
                            onclick="loadPageContent('{{ route('division.index') }}')" data-toggle="dropdown"><i
                                class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Manage Divisions</span>
                        </a>
                    </li>
                    @endcanAccess
                    @canAccess('arrival-doubt-trucks')
                    <li data-menu=""><a class="dropdown-item d-flex align-items-center"
                            href="{{ route('raw-material.doubt-trucks.index') }}"
                            onclick="loadPageContent('{{ route('raw-material.doubt-trucks.index') }}')"
                            data-toggle="dropdown"><i class="ft-arrow-right submenu-icon"></i>
                            <span data-i18n="Task Board">Doubt Trucks</span>
                        </a>
                    </li>
                    @endcanAccess
                </ul>
            </li>
            @endcanAccess
        </ul>
    </div>
</div>