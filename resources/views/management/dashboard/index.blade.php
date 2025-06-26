@extends('management.layouts.master')
@section('title')
    Dashboard - {{ ucfirst($module) }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 dd-none">
                <div class="dashboard-filters">
                    <div class="filter-group">
                        <label>SELECT MODULE</label>
                        <select id="module_select" class="filter-input">
                            <option value="arrival" {{ $module == 'arrival' ? 'selected' : '' }}>Arrival</option>
                            <option value="purchase_order" {{ $module == 'purchase_order' ? 'selected' : '' }}>Purchase
                                Contract
                            </option>
                            <option value="finance" {{ $module == 'finance' ? 'selected' : '' }}>Finance</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>FROM DATE</label>
                        <input type="date" id="from_date" class="filter-input" value="{{ $fromDate }}">
                    </div>
                    <div class="filter-group">
                        <label>TO DATE</label>
                        <input type="date" id="to_date" class="filter-input" value="{{ $toDate }}">
                    </div>
                    <button type="button" id="refresh_dashboard" class="refresh-btn">
                        <i class="ft-refresh-cw"></i> Refresh
                    </button>
                </div>
            </div>

            @if ($module === 'arrival')
                <div class="col-12">
                    <div class="dashboard-cards-grid">
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-grid"></i>
                            </div>
                            <div class="card-number">{{ $data['total_tickets'] ?? 0 }}</div>
                            <div class="card-title">Total Tickets</div>
                            <div class="card-subtitle">Total Tickets</div>
                            @if (($data['rejected_tickets'] ?? 0) > 0)
                                <div class="status-badge status-danger cursor-pointer"
                                    onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}','Rejected Tickets - Bilty Return Pending', true, '70%')">
                                    {{ $data['rejected_tickets'] ?? 0 }} Rejected</div>
                            @else
                                <div class="status-badge status-neutral">No Rejections</div>
                            @endif

                            @if (($data['completed_tickets'] ?? 0) > 0)
                                <div class="status-badge status-success cursor-pointer"
                                    onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=completed_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}','Rejected Tickets - Bilty Return Pending', true, '70%')">
                                    {{ $data['completed_tickets'] ?? 0 }} Completed</div>
                            @else
                                <div class="status-badge status-neutral">No Completed Yet!</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=total_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}','Total Tickets - Bilty Return Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card d-none">
                            <div class="card-icon">
                                <i class="ft-x-circle"></i>
                            </div>
                            <div class="card-number">{{ $data['rejected_tickets'] ?? 0 }}</div>
                            <div class="card-title">Rejected Tickets</div>
                            <div class="card-subtitle">Bilty Return Pending</div>
                            @if (($data['rejected_tickets'] ?? 0) > 0)
                                <div class="status-badge status-danger">-{{ $data['rejected_tickets'] ?? 0 }} Rejected
                                </div>
                            @else
                                <div class="status-badge status-neutral">No Rejections</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}','Rejected Tickets - Bilty Return Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-file-plus"></i>
                            </div>
                            <div class="card-number">{{ $data['new_tickets'] ?? 0 }}</div>
                            <div class="card-title">Initial Sampling</div>
                            <div class="card-subtitle">Pending Initial Sampling</div>
                            @if (($data['new_tickets'] ?? 0) > 0)
                                <div class="status-badge status-pending">+{{ $data['new_tickets'] ?? 0 }} Pending</div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=new_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}','New Tickets - Pending Initial Sampling', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-clipboard"></i>
                            </div>
                            <div class="card-number">{{ $data['initial_sampling_done'] ?? 0 }}</div>
                            <div class="card-title">Purchaser Approval (Initial Sampling)</div>
                            <div class="card-subtitle">Pending Approval</div>
                            @if (($data['initial_sampling_done'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['initial_sampling_done'] ?? 0 }}
                                    Awaiting
                                    Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_done&from_date={{ $fromDate }}&to_date={{ $toDate }}','Initial Sampling Done - Pending Approval', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card d-none">
                            <div class="card-icon">
                                <i class="ft-refresh-cw"></i>
                            </div>
                            <div class="card-number">{{ $data['resampling_required'] ?? 0 }}</div>
                            <div class="card-title">Resampling Required</div>
                            <div class="card-subtitle">Pending Resampling</div>
                            @if (($data['resampling_required'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['resampling_required'] ?? 0 }} Need
                                    Resampling
                                </div>
                            @else
                                <div class="status-badge status-neutral">No Resampling Required</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=resampling_required&from_date={{ $fromDate }}&to_date={{ $toDate }}','Resampling Required', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-map-pin"></i>
                            </div>
                            <div class="card-number">{{ $data['location_transfer_pending'] ?? 0 }}</div>
                            <div class="card-title">Location Transfer</div>
                            <div class="card-subtitle">Pending Transfer</div>
                            @if (($data['location_transfer_pending'] ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $data['location_transfer_pending'] ?? 0 }}
                                    Ready
                                    for
                                    Transfer</div>
                            @else
                                <div class="status-badge status-neutral">All Transferred</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=location_transfer_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}','Location Transfer Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-truck"></i>
                            </div>
                            <div class="card-number">{{ $data['first_weighbridge_pending'] ?? 0 }}</div>
                            <div class="card-title">First Weighbridge</div>
                            <div class="card-subtitle">Pending Weighing</div>
                            @if (($data['first_weighbridge_pending'] ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $data['first_weighbridge_pending'] ?? 0 }}
                                    Ready
                                    for
                                    Weighing</div>
                            @else
                                <div class="status-badge status-neutral">All Weighed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=first_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}','First Weighbridge Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-layers"></i>
                            </div>
                            <div class="card-number">{{ $data['inner_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Inner Sampling</div>
                            <div class="card-subtitle">Requested Not Done</div>
                            @if (($data['inner_sampling_requested'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['inner_sampling_requested'] ?? 0 }}
                                    Pending
                                </div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}','Inner Sampling Requested', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-check-circle"></i>
                            </div>
                            <div class="card-number">{{ $data['inner_sampling_pending_approval'] ?? 0 }}</div>
                            <div class="card-title">Purchaser Approval (Inner Sampling)</div>
                            <div class="card-subtitle">Pending Approval</div>
                            @if (($data['inner_sampling_pending_approval'] ?? 0) > 0)
                                <div class="status-badge status-warning">
                                    +{{ $data['inner_sampling_pending_approval'] ?? 0 }}
                                    Awaiting Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_pending_approval&from_date={{ $fromDate }}&to_date={{ $toDate }}','Inner Sampling Pending Approval', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-thumbs-up"></i>
                            </div>
                            <div class="card-number">{{ $data['half_full_approve_pending'] ?? 0 }}</div>
                            <div class="card-title">Half/Full Approve</div>
                            <div class="card-subtitle">Pending</div>
                            @if (($data['half_full_approve_pending'] ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $data['half_full_approve_pending'] ?? 0 }}
                                    Ready
                                    for
                                    Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_full_approve_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}','Half/Full Approve Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-truck"></i>
                            </div>
                            <div class="card-number">{{ $data['second_weighbridge_pending'] ?? 0 }}</div>
                            <div class="card-title">Second Weighbridge</div>
                            <div class="card-subtitle">Pending</div>
                            @if (($data['second_weighbridge_pending'] ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $data['second_weighbridge_pending'] ?? 0 }}
                                    Ready
                                </div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=second_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}','Second Weighbridge Pending', true, '70%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="ft-dollar-sign"></i>
                            </div>
                            <div class="card-number">{{ $data['freight_ready'] ?? 0 }}</div>
                            <div class="card-title">Freight Pending</div>
                            <div class="card-subtitle">Ready for Freight</div>
                            @if (($data['decision_on_average_enabled'] ?? 0) > 0)
                                <div class="status-badge status-warning">{{ $data['decision_on_average_enabled'] ?? 0 }}
                                    Decision
                                    on Average Enabled</div>
                            @elseif(($data['freight_ready'] ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $data['freight_ready'] ?? 0 }} Ready</div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=freight_ready&from_date={{ $fromDate }}&to_date={{ $toDate }}','Freight Ready', true, '70%')">
                                View
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="col-12">
                    <div class="empty-module">
                        <div class="empty-card">
                            <h4>{{ ucfirst($module) }} Dashboard</h4>
                            <p>Dashboard for {{ ucfirst($module) }} module will be implemented here.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#module_select').change(function() {
                updateDashboard();
            });

            $('#from_date, #to_date').change(function() {
                updateDashboard();
            });

            $('#refresh_dashboard').click(function() {
                updateDashboard();
            });

            function updateDashboard() {
                const module = $('#module_select').val();
                const fromDate = $('#from_date').val();
                const toDate = $('#to_date').val();

                window.location.href = '{{ route('home') }}?' + $.param({
                    module: module,
                    from_date: fromDate,
                    to_date: toDate
                });
            }
        });
    </script>

    <style>
        body.layout-dark .dashboard-filters {
            background: #2d2d2d;
            border-color: #404040;
        }

        body.layout-dark .filter-input {
            background: #404040;
            border-color: #555;
            color: #fff;
        }

        body.layout-dark .filter-input:focus {
            border-color: #26499b;
        }

        body.layout-dark .dashboard-card {
            background: #2d2d2d;
            border-color: #404040;
        }

        body.layout-dark .card-title {
            color: #fff;
        }

        body.layout-dark .card-subtitle {
            color: #ccc;
        }

        body.layout-dark .card-number {
            color: #26499b;
        }

        body.layout-dark .empty-card {
            background: #2d2d2d;
            border-color: #404040;
        }

        body.layout-dark .empty-card h4 {
            color: #fff;
        }

        body.layout-dark .empty-card p {
            color: #ccc;
        }

        .dashboard-container {
            padding: 20px;
        }

        .dashboard-filters {
            display: flex;
            align-items: end;
            gap: 20px;
            background: #ffffff;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }

        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .filter-input {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.2s ease;
            outline: none;
        }

        .filter-input:focus {
            border-color: #26499b;
            box-shadow: 0 0 0 3px rgba(38, 73, 155, 0.1);
        }

        .refresh-btn {
            background: #26499b;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            height: fit-content;
        }

        .refresh-btn:hover {
            background: #1e3a7a;
            transform: translateY(-1px);
        }

        /* Updated grid for 5 columns on large screens */
        .dashboard-cards-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            max-width: 100%;
        }

        @media (min-width: 576px) {
            .dashboard-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 768px) {
            .dashboard-cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 992px) {
            .dashboard-cards-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1200px) {
            .dashboard-cards-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .dashboard-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            position: relative;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #26499b;
            font-size: 18px;
        }

        .card-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #26499b;
            line-height: 1;
            margin-bottom: 8px;
            margin-top: 12px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .card-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 16px;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-neutral {
            background: #f3f4f6;
            color: #4b5563;
        }

        .view-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: #26499b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-btn:hover {
            background: #1e3a7a;
            color: white;
            text-decoration: none;
        }

        .empty-module {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
        }

        .empty-card {
            background: #ffffff;
            padding: 60px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .empty-card h4 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .empty-card p {
            color: #6b7280;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .dashboard-filters {
                flex-direction: column;
                gap: 15px;
            }

            .filter-group {
                min-width: 100%;
            }

            .dashboard-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-card {
                padding: 20px;
            }

            .card-number {
                font-size: 2rem;
            }

            .card-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
        }
    </style>
@endsection
