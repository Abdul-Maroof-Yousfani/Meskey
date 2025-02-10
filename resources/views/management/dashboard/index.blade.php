@extends('management.layouts.master')
@section('title')
    Dashboard
@endsection
@section('content')
  <div class="content-wrapper">
                    <!--Statistics cards Starts-->
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                            <div class="card gradient-purple-love">
                                <div class="card-content">
                                    <div class="card-body py-0">
                                        <div class="media pb-1">
                                            <div class="media-body white text-left">
                                                <h3 class="font-large-1 white mb-0">$2,156</h3>
                                                <span>Total Tax</span>
                                            </div>
                                            <div class="media-right white text-right">
                                                <i class="ft-activity font-large-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="Widget-line-chart" class="height-75 WidgetlineChart WidgetlineChartshadow mb-2">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                            <div class="card gradient-ibiza-sunset">
                                <div class="card-content">
                                    <div class="card-body py-0">
                                        <div class="media pb-1">
                                            <div class="media-body white text-left">
                                                <h3 class="font-large-1 white mb-0">$15,678</h3>
                                                <span>Total Cost</span>
                                            </div>
                                            <div class="media-right white text-right">
                                                <i class="ft-percent font-large-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="Widget-line-chart1" class="height-75 WidgetlineChart WidgetlineChartshadow mb-2">
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                            <div class="card gradient-mint">
                                <div class="card-content">
                                    <div class="card-body py-0">
                                        <div class="media pb-1">
                                            <div class="media-body white text-left">
                                                <h3 class="font-large-1 white mb-0">$45,668</h3>
                                                <span>Total Sales</span>
                                            </div>
                                            <div class="media-right white text-right">
                                                <i class="ft-trending-up font-large-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="Widget-line-chart2" class="height-75 WidgetlineChart WidgetlineChartshadow mb-2">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                            <div class="card gradient-king-yna">
                                <div class="card-content">
                                    <div class="card-body py-0">
                                        <div class="media pb-1">
                                            <div class="media-body white text-left">
                                                <h3 class="font-large-1 white mb-0">$32,454</h3>
                                                <span>Total Earning</span>
                                            </div>
                                            <div class="media-right white text-right">
                                                <i class="ft-credit-card font-large-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="Widget-line-chart3" class="height-75 WidgetlineChart WidgetlineChartshadow mb-2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Statistics cards Ends-->

                    <!--Line with Area Chart 1 Starts-->
                    <div class="row match-height">
                        <div class="col-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">PRODUCTS SALES</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="chart-info mb-3 ml-3">
                                            <span class="gradient-purple-bliss d-inline-block rounded-circle mr-1" style="width:15px; height:15px;"></span>
                                            Sales
                                            <span class="gradient-mint d-inline-block rounded-circle mr-1 ml-2" style="width:15px; height:15px;"></span>
                                            Visits
                                        </div>
                                        <div id="line-area" class="height-350 lineArea">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-12 col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Statistics</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p class="font-medium-2 text-center my-2">Last 6 Months Sales</p>
                                        <div id="Stack-bar-chart" class="height-300 Stackbarchart mb-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Line with Area Chart 1 Ends-->

                    <div class="row match-height d-none">
                        <div class="col-xl-4 col-lg-12 col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Statistics</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p class="font-medium-2 text-center my-2">Last 6 Months Sales</p>
                                        <div id="Stack-bar-chart" class="height-300 Stackbarchart mb-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-12 col-12">
                            <div class="card shopping-cart">
                                <div class="card-header pb-2">
                                    <h4 class="card-title mb-1">Shopping Cart</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table text-center m-0">
                                                <thead>
                                                    <tr>
                                                        <th>Image</th>
                                                        <th>Product</th>
                                                        <th>Quantity</th>
                                                        <th>Status</th>
                                                        <th>Amount</th>
                                                        <th>Delete</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><img class="height-50" src="{{image_path('')}}" alt="Generic placeholder image" /></td>
                                                        <td>Espresso</td>
                                                        <td>1</td>
                                                        <td>
                                                            <span class="badge badge-pill bg-light-primary cursor-pointer">Active</span>
                                                        </td>
                                                        <td>$19.94</td>
                                                        <td>
                                                            <i class="ft-trash-2"></i>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><img class="height-50" src="{{image_path('')}}" alt="Generic placeholder image" /></td>
                                                        <td>iPhone</td>
                                                        <td>2</td>
                                                        <td>
                                                            <span class="badge badge-pill bg-light-danger cursor-pointer">Disabled</span>
                                                        </td>
                                                        <td>$99.00</td>
                                                        <td>
                                                            <i class="ft-trash-2"></i>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><img class="height-50" src="{{image_path('')}}" alt="Generic placeholder image" /></td>
                                                        <td>iMac</td>
                                                        <td>1</td>
                                                        <td>
                                                            <span class="badge badge-pill bg-light-info cursor-pointer">Paused</span>
                                                        </td>
                                                        <td>$299.00</td>
                                                        <td>
                                                            <i class="ft-trash-2"></i>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><img class="height-50" src="{{image_path('')}}" alt="Generic placeholder image" /></td>
                                                        <td>iWatch</td>
                                                        <td>2</td>
                                                        <td>
                                                            <span class="badge badge-pill bg-light-success cursor-pointer">Active</span>
                                                        </td>
                                                        <td>$24.51</td>
                                                        <td>
                                                            <i class="ft-trash-2"></i>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><img class="height-50" src="{{image_path('')}}" alt="Generic placeholder image" /></td>
                                                        <td>iWatch</td>
                                                        <td>2</td>
                                                        <td>
                                                            <span class="badge badge-pill bg-light-success cursor-pointer">Active</span>
                                                        </td>
                                                        <td>$24.51</td>
                                                        <td>
                                                            <i class="ft-trash-2"></i>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

              
                </div>
                    <script src="{{asset('management/app-assets/vendors/js/chartist.min.js')}}"></script>

@endsection
