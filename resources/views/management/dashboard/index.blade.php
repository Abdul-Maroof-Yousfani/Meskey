@extends('management.layouts.master')
@section('title')
    Dashboard - {{ ucfirst($module) }}
@endsection

@section('content')
    <div class="content-wrapper">
        @if(canAccess('no-stats') && auth()->user()->user_type != 'super-admin')
<div class="row">
     <div class="no-stats-container">
        <div class="no-stats-card">
            <!-- Logo Section -->
            <div class="no-stats-logo-wrapper">
        
                <img src="{{ image_path(auth()->user()->profile_image) }}" alt="Company Logo" class="no-stats-logo">
                {{-- OR if you have SVG logo --}}
                {{-- <svg class="no-stats-logo" viewBox="0 0 200 60">
                    <rect width="200" height="60" rx="8" fill="#26499b"/>
                    <text x="20" y="38" fill="white" font-size="24" font-weight="bold">COMPANY</text>
                    <text x="140" y="38" fill="#6b8fd4" font-size="16">Logo</text>
                </svg> --}}
            </div>

            <!-- Welcome Section -->
            <div class="no-stats-welcome">
                <div class="welcome-badge">
                    <i class="ft-user"></i>
                    <span>Welcome Back</span>
                </div>
                <h2 class="no-stats-title">
                    Hello, {{ auth()->user()->name ?? 'User' }}! 👋
                </h2>
                <p class="no-stats-subtitle">
                    We're glad to see you again. Your dashboard is ready when you are.
                </p>
            </div>

            <!-- Divider -->
            <div class="no-stats-divider">
                <span></span>
                <div class="divider-content">
                    <i class="ft-info"></i>
                </div>
                <span></span>
            </div>

            <!-- Access Restricted Message -->
            <div class="no-stats-message-wrapper">
                <div class="access-icon">
                    <i class="ft-lock"></i>
                </div>
                <div class="access-text">
                    <h4>Statistics Access Restricted</h4>
                    <p>You currently don't have permission to view analytics data for this dashboard.</p>
                </div>
            </div>

            <!-- Contact Admin -->
            <!-- <div class="no-stats-contact">
                <button class="contact-admin-btn" onclick="window.location.href='mailto:admin@company.com'">
                    <i class="ft-mail"></i> Contact Administrator
                </button>
                <span class="or-text">or</span>
                <button class="refresh-btn-small" onclick="location.reload()">
                    <i class="ft-refresh-cw"></i> Refresh
                </button>
            </div> -->

            <!-- Footer -->
            <div class="no-stats-footer">
                <span class="footer-dot"></span>
                <span class="footer-text">Restricted Access</span>
                <span class="footer-dot"></span>
            </div>
        </div>
    </div>
</div>

        @else
        <div class="row">
            <div class="col-12 dd-none">
                <div class="dashboard-filters">
                    <div class="filter-group d-none">
                        <label>SELECT MODULE</label>
                        <select id="module_select" class="filter-input">
                            <option value="arrival" {{ $module == 'arrival' ? 'selected' : '' }}>Arrival</option>
                            <option value="purchase_order" {{ $module == 'purchase_order' ? 'selected' : '' }}>Purchase
                                Contract
                            </option>
                            <option value="finance" {{ $module == 'finance' ? 'selected' : '' }}>Finance</option>
                        </select>
                    </div>
                    <div class="filter-group d-none">
                        <label>FROM DATE</label>
                        <input type="date" id="from_date" class="filter-input" value="{{ $fromDate }}">
                    </div>
                    <div class="filter-group d-none">
                        <label>TO DATE</label>
                        <input type="date" id="to_date" class="filter-input" value="{{ $toDate }}">
                    </div>
                    <div class="filter-group">
                        <label>LOCATION</label>
                        <select name="location_id" id="location_id" class="filter-input select2">
                            <option value="">All</option>
                            @foreach($companylocations as $companylocation)
                                <option value="{{ $companylocation->id }}" @selected($companylocation->id == $location_id)>{{ $companylocation->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="refresh_dashboard" class="refresh-btn">
                        <i class="ft-refresh-cw"></i> Refresh
                    </button>
                </div>
            </div>

            @if ($module === 'arrival')
            @canAccess('arrival-stats')
                <div class="col-12">
                    <div class="dashboard-cards-grid" >
 <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=old_pending_trucks&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Old Pending Trucks', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-grid"></i>
                            </div>
                            <div class="card-number">{{ $data['old_pending_trucks'] ?? 0 }}</div>
                            <div class="card-title">Old Pending Trucks</div>
                            <div class="card-subtitle">Old Pending Trucks</div>

                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=old_pending_trucks&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Old Pending Trucks', true, '85%')">
                                View
                            </button>
                        </div>


                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=total_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Total Tickets', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-grid"></i>
                            </div>
                            <div class="card-number">{{ $data['total_tickets'] ?? 0 }}</div>
                            <div class="card-title m-0">Total Tickets</div>
                            <!-- <div class="card-subtitle">Total Tickets</div> -->
                            @if (($data['rejected_tickets'] ?? 0) > 0)
                                <div class="status-badge status-danger cursor-pointer mb-1"
                                    onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Rejected Tickets - Bilty Return Pending', true, '85%')">
                                    {{ $data['rejected_tickets'] ?? 0 }} Rejected</div>
                            @else
                                <div class="status-badge status-neutral">No Rejections</div>
                            @endif

                           
                            @if (($data['inprocess_tickets'] ?? 0) > 0)
                                <div class="status-badge status-warning cursor-pointer mb-1">{{ $data['inprocess_tickets'] ?? 0 }} In Process</div>
                            @else
                                <div class="status-badge status-neutral">No In Process Yet!</div>
                            @endif
                            <br>
                             @if (($data['completed_tickets'] ?? 0) > 0)
                                <div class="status-badge status-success cursor-pointer mb-1"
                                    onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=completed_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Completed Tickets - Arrival Slip Generated', true, '85%')">
                                    {{ $data['completed_tickets'] ?? 0 }} Completed</div>
                            @else
                                <div class="status-badge status-neutral">No Completed Yet!</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=total_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Total Tickets', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=total_tickets_kgs&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Total Tickets in Kgs', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-grid"></i>
                            </div>
                            <div class="card-number">{{ $data['total_tickets_kgs'] ?? 0 }} Kgs</div>
                            <div class="card-title">Total Tickets In Kgs</div>
                            <div class="card-subtitle">Total Kgs Received (Tickets Net Weight)</div>
                            
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=total_tickets_kgs&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Total Tickets in Kgs', true, '85%')">
                                View
                            </button>
                        </div>


                       

                        

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck Half Rejected', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-x-circle"></i>
                            </div>
                            <div class="card-number">{{ $data['half_rejected_tickets'] ?? 0 }}</div>
                            <div class="card-title">Truck Half Rejected</div>
                            <div class="card-subtitle">Truck Half Rejected</div>
                            
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck Half Rejected', true, '85%')">
                                View
                            </button>
                        </div>


                         <div class="dashboard-card bg-danger-light" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck Full Rejected', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-x-circle text-danger"></i>
                            </div>
                            <div class="card-number text-danger">{{ $data['rejected_tickets'] ?? 0 }}</div>
                            <div class="card-title">Truck Full Rejected</div>
                            <div class="card-subtitle">Truck Full Rejected</div>
                           

                           
                            <button class="view-btn" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck Full Rejected', true, '85%')">
                                View
                            </button>
                        </div>


                        
                         <div class="dashboard-card bg-warning-light" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_at_ho&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck at HO', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-home text-warning"></i>
                            </div>
                            <div class="card-number text-warning">{{ $data['truck_at_ho'] ?? 0 }}</div>
                            <div class="card-title">Truck at HO</div>
                            <div class="card-subtitle">Truck at HO (Unverified)</div>
                           

                           
                            <button class="view-btn" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_at_ho&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck at HO', true, '85%')">
                                View
                            </button>
                        </div>
                        
                         <div class="dashboard-card bg-warning-light" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_for_built_return&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck For Bilty Return - Pending Confirmation', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-dollar-sign text-warning"></i>
                            </div>
                            <div class="card-number text-warning">{{ $data['truck_for_built_return'] ?? 0 }}</div>
                            <div class="card-title">Truck For Bilty Return</div>
                            <div class="card-subtitle">Waiting for Confirmation</div>

                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_for_built_return&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Truck For Bilty Return', true, '85%')">
                                View
                            </button>
                        </div>
                         <div class="dashboard-card bg-success-light" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_out&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Truck Out - Pending Confirmation', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-dollar-sign text-success"></i>
                            </div>
                            <div class="card-number text-success">{{ $data['truck_out'] ?? 0 }}</div>
                            <div class="card-title">Truck Out</div>
                            <div class="card-subtitle">Arrival Slip Generated</div>

                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=truck_out&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Truck Out', true, '85%')">
                                View
                            </button>
                        </div>
     <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Sampling Requested - Pending Initial Sampling', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-file-plus"></i>
                            </div>
                            <div class="card-number">{{ $data['initial_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Truck at QC (Initial Sampling)</div>
                            <div class="card-subtitle">Pending Initial Sampling</div>
                            @if (($data['new_tickets'] ?? 0) > 0)
                                <div class="status-badge status-pending">+{{ $data['initial_sampling_requested'] ?? 0 }}
                                    Pending</div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Sampling Requested - Pending Initial Sampling', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_re_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Re-Sampling Requested - Pending Initial Re-Sampling', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-file-plus"></i>
                            </div>
                            <div class="card-number">{{ $data['initial_re_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Truck at QC (Initial Re-Sampling)</div>
                            <div class="card-subtitle">Pending Initial Re-Sampling</div>
                            @if (($data['new_tickets'] ?? 0) > 0)
                                <div class="status-badge status-pending">+{{ $data['initial_re_sampling_requested'] ?? 0 }}
                                    Pending</div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_re_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Re-Sampling Requested - Pending Initial Re-Sampling', true, '85%')">
                                View
                            </button>
                        </div>

                        
                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Inner Sampling Requested - Pending Inner Sampling', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-layers"></i>
                            </div>
                            <div class="card-number">{{ $data['inner_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Truck at QC (Inner Sampling)</div>
                            <div class="card-subtitle">Requested Not Done</div>
                            @if (($data['inner_sampling_requested'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['inner_sampling_requested'] ?? 0 }}
                                    Pending
                                </div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Inner Sampling Requested - Pending Inner Sampling', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_re_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Inner Re-Sampling Requested - Pending Inner Re-Sampling', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-layers"></i>
                            </div>
                            <div class="card-number">{{ $data['inner_re_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Truck at QC (Inner Resampling)</div>
                            <div class="card-subtitle">Requested Not Done</div>
                            @if (($data['inner_re_sampling_requested'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['inner_re_sampling_requested'] ?? 0 }}
                                    Pending
                                </div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_re_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Inner Re-Sampling Requested - Pending Inner Re-Sampling', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=rejected_tickets&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Rejected Tickets - Bilty Return Pending', true, '85%')">
                                View
                            </button>
                        </div>

                   @foreach ($purchasers as $purchaser)
              <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_done&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&purchaser_id={{ $purchaser->id ?? '' }}','Initial Sampling Done - Pending Approval', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-clipboard"></i>
                            </div>
                   
                            <div class="card-number">    {{ initialSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) }}
</div>
                            <div class="card-title">Truckt At {{ $purchaser->name }} (Initial Sampling)</div>
                            <div class="card-subtitle">Truckt At {{ $purchaser->name }} Pending Approval</div>
                            @if (initialSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) > 0)
                                <div class="status-badge status-warning">+{{ initialSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) }}
                                    Awaiting
                                    Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_done&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&purchaser_id={{ $purchaser->id ?? '' }}','Initial Sampling Done - Pending Approval', true, '85%')">
                                View
                            </button>
                        </div>
                        @endforeach


                        @foreach ($purchasers as $purchaser)
                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_pending_approval&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&purchaser_id={{ $purchaser->id ?? '' }}','Inner Sampling Done - Pending Approval', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-clipboard"></i>
                            </div>
                            <div class="card-number">    
                                {{ innerSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) }}
                            </div>
                            <div class="card-title">Truckt At {{ $purchaser->name }} (Inner Sampling)</div>
                            <div class="card-subtitle">Truckt At {{ $purchaser->name }} Pending Approval</div>
                            @if (innerSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) > 0)
                                <div class="status-badge status-warning">+{{ innerSamplingDonePurchaserwise($purchaser->id, $fromDate, $toDate, $location_id ?? null) }}
                                    Awaiting
                                    Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_pending_approval&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&purchaser_id={{ $purchaser->id ?? '' }}','Inner Sampling Done - Pending Approval', true, '85%')">
                                View
                            </button>
                        </div>
                        @endforeach


                        <div class="dashboard-card d-none" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_done&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Sampling Done - Pending Approval', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=initial_sampling_done&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Initial Sampling Done - Pending Approval', true, '85%')">
                                View
                            </button>
                        </div>




                        <div class="dashboard-card d-none" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=resampling_required&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Resampling Required - Pending Resampling', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=resampling_required&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Resampling Required', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=location_transfer_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Location Transfer - Pending Transfer', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=location_transfer_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Location Transfer Pending', true, '85%')">
                                View
                            </button>
                        </div>


                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=freight_ready&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Freight Pending', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=freight_ready&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Freight Ready', true, '85%')">
                                View
                            </button>
                        </div>



@foreach ($data['weighbridge_pending_locationwise'] as $location)
                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=first_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&arrival_location_id={{ $location->location_id ?? '' }}','Weighbridge Pending - {{ $location->location_name }}', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-truck"></i>
                            </div>
                            <div class="card-number">{{ $location->first_count ?? 0 }}</div>
                            <div class="card-title">Truck at Weighbridge {{ $location->location_name }} <br> (1st Weighing)</div>
                            <div class="card-subtitle">Pending Weighing</div>
                            @if (($location->first_count ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $location->first_count ?? 0 }}
                                    Ready
                                    for
                                    Weighing</div>
                            @else
                                <div class="status-badge status-neutral">All Weighed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=first_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}&arrival_location_id={{ $location->location_id }}','First Weighbridge Pending', true, '85%')">
                                View
                            </button>
                        </div>

                         <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=second_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&arrival_location_id={{ $location->location_id ?? '' }}','Weighbridge Pending - {{ $location->location_name }}', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-truck"></i>
                            </div>
                            <div class="card-number">{{ $location->second_count ?? 0 }}</div>
                            <div class="card-title">Truck at Weighbridge {{ $location->location_name }} <br> (2nd Weighing)</div>
                            <div class="card-subtitle">Pending Weighing</div>
                            @if (($location->second_count ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $location->second_count ?? 0 }}
                                    Ready
                                    for
                                    Weighing</div>
                            @else
                                <div class="status-badge status-neutral">All Weighed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=second_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}&arrival_location_id={{ $location->location_id }}','Second Weighbridge Pending', true, '85%')">
                                View
                            </button>
                        </div>

                        @endforeach



                        
                        <div class="dashboard-card d-none" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=first_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','First Weighbridge Pending', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=first_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','First Weighbridge Pending', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Request For (Inner Sampling) - Pending Approval', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-layers"></i>
                            </div>
                            <div class="card-number">{{ $data['inner_sampling_requested'] ?? 0 }}</div>
                            <div class="card-title">Request For (Inner Sampling)</div>
                            <div class="card-subtitle">Inner Sampling Requested Not Done</div>
                            @if (($data['inner_sampling_requested'] ?? 0) > 0)
                                <div class="status-badge status-warning">+{{ $data['inner_sampling_requested'] ?? 0 }}
                                    Pending
                                </div>
                            @else
                                <div class="status-badge status-neutral">All Completed</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_requested&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Inner Sampling Requested', true, '85%')">
                                View
                            </button>
                        </div>

                      
                        <div class="dashboard-card d-none" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_pending_approval&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}','Purchaser Approval (Inner Sampling) - Pending Approval', true, '85%')">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=inner_sampling_pending_approval&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Inner Sampling Pending Approval', true, '85%')">
                                View
                            </button>
                        </div>

                    @foreach ($data['unloading_pending_locationwise'] as $location)
                        <div class="dashboard-card" onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_full_approve_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id ?? '' }}&arrival_location_id={{ $location->location_id ?? '' }}','Unloading Pending - {{ $location->location_name }}', true, '85%')">
                            <div class="card-icon">
                                <i class="ft-thumbs-up"></i>
                            </div>
                            <div class="card-number">{{ $location->unloading_count ?? 0 }}</div>
                            <div class="card-title">Truck at Unloading (Warehouse {{ $location->location_name }})</div>
                            <div class="card-subtitle">Pending</div>
                            @if (($location->unloading_count ?? 0) > 0)
                                <div class="status-badge status-success">+{{ $location->unloading_count ?? 0 }}
                                    Ready
                                    for
                                    Approval</div>
                            @else
                                <div class="status-badge status-neutral">All Approved</div>
                            @endif
                            <button class="view-btn"
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_full_approve_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}&arrival_location_id={{ $location->location_id }}','Half/Full Approve Pending', true, '85%')">
                                View
                            </button>
                        </div>
                        @endforeach
                        <div class="dashboard-card d-none">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=half_full_approve_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Half/Full Approve Pending', true, '85%')">
                                View
                            </button>
                        </div>

                        <div class="dashboard-card d-none">
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
                                onclick="openModal(this,'{{ route('dashboard.list-data') }}?type=second_weighbridge_pending&from_date={{ $fromDate }}&to_date={{ $toDate }}&location_id={{ $location_id }}','Second Weighbridge Pending', true, '85%')">
                                View
                            </button>
                        </div>
                    </div>
                </div>
            @endcanAccess
            
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
        @endif
    </div>

    <script>
        $(document).ready(function () {

        $('#module_select, #from_date, #to_date, #location_id').on('change', function () {
            updateDashboard();
        });

        $('#refresh_dashboard').on('click', function () {
            updateDashboard();
        });

        function updateDashboard() {
            const module = $('#module_select').val();
            const fromDate = $('#from_date').val();
            const toDate = $('#to_date').val();
            const location_id = $('#location_id').val();

            let params = {
                module: module, 
                from_date: fromDate,
                to_date: toDate
            };

            // ✅ sirf jab value ho tab add ho
            if (location_id !== null && location_id !== '') {
                params.location_id = location_id;
            }

            window.location.href = '{{ route('home') }}?' + $.param(params);
        }

});     

        </script>

<script>
const companyId = "{{ auth()->user()->company_id ?? 1 }}";

window.Echo.channel("dashboard-arrival-" + companyId)
    .listen(".arrival-dashboard-updated", (e) => {
        updateArrivalDashboardUI(e.stats);
    });



    function updateArrivalDashboardUI(stats) {

$("[data-field='total_tickets']").text(stats.total_tickets);
$("[data-field='rejected_tickets']").text(stats.rejected_tickets);
$("[data-field='completed_tickets']").text(stats.completed_tickets);
$("[data-field='initial_sampling_requested']").text(stats.initial_sampling_requested);
$("[data-field='initial_sampling_done']").text(stats.initial_sampling_done);
$("[data-field='location_transfer_pending']").text(stats.location_transfer_pending);
$("[data-field='first_weighbridge_pending']").text(stats.first_weighbridge_pending);
$("[data-field='inner_sampling_requested']").text(stats.inner_sampling_requested);
$("[data-field='inner_sampling_pending_approval']").text(stats.inner_sampling_pending_approval);
$("[data-field='half_full_approve_pending']").text(stats.half_full_approve_pending);
$("[data-field='second_weighbridge_pending']").text(stats.second_weighbridge_pending);
$("[data-field='freight_ready']").text(stats.freight_ready);
$("[data-field='decision_on_average_enabled']").text(stats.decision_on_average_enabled);

console.log("Realtime Arrival Stats Updated ✅", stats);
}
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
            gap: 10px;
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
                grid-template-columns: repeat(6, 1fr);
            }
            .card-number {
                /* font-size: 1rem !important; */
            }

            .card-title {
                /* font-size: 12px !important; */
            }
        }

        .dashboard-card {
            cursor: pointer;
            background: #ffffff;
            border-radius: 12px;
            /* padding: 24px; */
            padding: 15px 20px;
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
            top: 14px;
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
            font-size: 1.8rem;
            font-weight: 700;
            color: #26499b;
            line-height: 1;
            margin-bottom: 8px;
            /* margin-top: 12px; */
        }

        .card-title {
            font-size: 14px;
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
            display: none;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 16px;
            display: none;
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
            display: none;
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










        /* No Stats Container */
.no-stats-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 500px;
    padding: 20px;
    width: 100%;
    background: transparent;
}

/* Main Card */
.no-stats-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 50px 60px 40px;
    text-align: center;
    max-width: 75%;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.no-stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #26499b, #6b8fd4, #26499b);
    background-size: 200% 100%;
    animation: gradientMove 3s ease infinite;
}

@keyframes gradientMove {
    0% { background-position: 0% 0%; }
    50% { background-position: 100% 0%; }
    100% { background-position: 0% 0%; }
}

/* Logo Section */
.no-stats-logo-wrapper {
    margin-bottom: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.no-stats-logo {
    height: 60px;
    width: auto;
    max-width: 200px;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));
    transition: transform 0.3s ease;
}

.no-stats-logo:hover {
    transform: scale(1.02);
}

/* Welcome Section */
.no-stats-welcome {
    margin-bottom: 25px;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f4ff;
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    color: #26499b;
    margin-bottom: 15px;
    border: 1px solid #dbe4f5;
}

.welcome-badge i {
    font-size: 14px;
}

.no-stats-title {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}

.no-stats-subtitle {
    font-size: 16px;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 5px;
}

/* Divider */
.no-stats-divider {
    display: flex;
    align-items: center;
    gap: 20px;
    margin: 30px 0 25px;
}

.no-stats-divider span {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, #d1d5db, transparent);
}

.divider-content {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f8faff;
    border-radius: 50%;
    border: 1px solid #dbe4f5;
    color: #26499b;
}

.divider-content i {
    font-size: 16px;
}

/* Access Message */
.no-stats-message-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #f8faff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
    border: 1px solid #e8edf8;
}

.access-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #eef4ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #26499b;
    font-size: 18px;
}

.access-text h4 {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
    margin-top: 0;
}

.access-text p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

/* Contact Buttons */
.no-stats-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.contact-admin-btn {
    background: #26499b;
    color: white;
    border: none;
    padding: 11px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.contact-admin-btn:hover {
    background: #1e3a7a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(38, 73, 155, 0.3);
}

.or-text {
    color: #9ca3af;
    font-size: 13px;
    font-weight: 500;
}

.refresh-btn-small {
    background: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    padding: 11px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.refresh-btn-small:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Footer */
.no-stats-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #f0f2f5;
}

.footer-dot {
    width: 4px;
    height: 4px;
    background: #d1d5db;
    border-radius: 50%;
}

.footer-text {
    font-size: 12px;
    font-weight: 500;
    color: #9ca3af;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* Dark Mode Support */
body.layout-dark .no-stats-card {
    background: #2d2d2d;
    border-color: #404040;
}

body.layout-dark .no-stats-title {
    color: #ffffff;
}

body.layout-dark .no-stats-subtitle {
    color: #b0b0b0;
}

body.layout-dark .welcome-badge {
    background: #1a1a2e;
    border-color: #333355;
    color: #6b8fd4;
}

body.layout-dark .divider-content {
    background: #1a1a2e;
    border-color: #333355;
    color: #6b8fd4;
}

body.layout-dark .no-stats-divider span {
    background: linear-gradient(90deg, transparent, #555, transparent);
}

body.layout-dark .no-stats-message-wrapper {
    background: #1a1a2e;
    border-color: #333355;
}

body.layout-dark .access-icon {
    background: #1a1a2e;
    color: #6b8fd4;
}

body.layout-dark .access-text h4 {
    color: #ffffff;
}

body.layout-dark .access-text p {
    color: #b0b0b0;
}

body.layout-dark .refresh-btn-small {
    background: #404040;
    color: #b0b0b0;
    border-color: #555;
}

body.layout-dark .refresh-btn-small:hover {
    background: #555;
}

body.layout-dark .no-stats-footer {
    border-top-color: #404040;
}

body.layout-dark .footer-text {
    color: #6b7280;
}

body.layout-dark .footer-dot {
    background: #555;
}

/* Responsive */
@media (max-width: 768px) {
    .no-stats-card {
        padding: 35px 25px 30px;
        max-width: 100%;
    }

    .no-stats-title {
        font-size: 22px;
    }

    .no-stats-logo {
        height: 45px;
        max-width: 150px;
    }

    .no-stats-message-wrapper {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 15px;
    }

    .access-text {
        text-align: center;
    }

    .no-stats-contact {
        flex-direction: column;
        gap: 10px;
    }

    .contact-admin-btn,
    .refresh-btn-small {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .no-stats-container {
        min-height: 400px;
        padding: 15px;
    }

    .no-stats-card {
        padding: 25px 18px 25px;
        border-radius: 16px;
    }

    .no-stats-title {
        font-size: 19px;
    }

    .no-stats-subtitle {
        font-size: 14px;
    }

    .no-stats-logo {
        height: 40px;
        max-width: 130px;
    }

    .welcome-badge {
        font-size: 11px;
        padding: 5px 12px;
    }
}
    </style>
@endsection
