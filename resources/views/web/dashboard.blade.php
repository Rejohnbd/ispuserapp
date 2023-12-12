@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        Dashboard
    </h4>
    @if(!$status)
    <div class="row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-2 text-center text-danger">Permission Denied</h3>
                </div>
                <div class="card-body p-5 bg-danger">

                    <h3 class="m-2 text-center text-white">Contact to Author</h3>

                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Cards with few info -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    <i class='bx bx-dollar-circle fs-4'></i>
                                </span>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-0 me-2">
                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totalinvoiceamount)
                                    {{round(getcustomersinvoicedetails(Auth::user()->id)->totalinvoiceamount)}}
                                    @else
                                    0
                                    @endif
                                </h5>
                                <small class="text-muted">TotalAmount</small>
                            </div>
                        </div>
                        <div id="conversationChart"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-warning"><i class="bx bx-dollar fs-4"></i></span>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-0 me-2">
                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totalpaidamount)
                                    {{round(getcustomersinvoicedetails(Auth::user()->id)->totalpaidamount)}}
                                    @else
                                    0
                                    @endif
                                </h5>
                                <small class="text-muted fs-6">TotalPaid</small>
                            </div>
                        </div>
                        <div id="incomeChart"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-wallet fs-4"></i></span>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-0 me-2">
                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totaldueamount)
                                    {{round(getcustomersinvoicedetails(Auth::user()->id)->totaldueamount)}}
                                    @else
                                    0
                                    @endif
                                </h5>
                                <small class="text-muted">DueAmount</small>
                            </div>
                        </div>
                        <div id="expensesLineChart"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-cart fs-4"></i></span>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-0 me-2">
                                    @if (getcustomersinvoicedetails(Auth::user()->id)->duedate)
                                    {{round(getcustomersinvoicedetails(Auth::user()->id)->duedate)}}
                                    @else
                                    <small class="text-muted">--N/A--</small>
                                    @endif
                                </h5>
                                <small class="text-muted">DueDate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Cards with few info -->

    <div class="row">
        <div class="col-md-6 col-lg-4 col-xl-4 mb-4 order-0">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">User Info</h5>
                </div>
                <div class="card-body pb-3">
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">ID</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(Auth::user()->customer_id))
                                    {{ Auth::user()->customer_id }}
                                    @else
                                    <span class="badge bg-label-danger">-NA-</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-pie-chart-alt"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Account Status</p>
                                </div>
                                @if (Auth::user()->status == 1)
                                <div class="item-progress"><span class="badge bg-label-success">New</span></div>
                                @else
                                <div class="item-progress">-NA-</div>
                                @endif
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Name</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->title }} {{ Auth::user()->name }}</div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Username</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->username }}</div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class='bx bxs-phone-call'></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Mobile No</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->mobno }}</div>
                            </div>
                        </li>
                        <li class="d-flex  mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-success"><i class='bx bxs-envelope'></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Email</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->email }}</div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-warning"><i class='bx bx-calendar-plus'></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Member Sence</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->created_at ? formatdatetime(Auth::user()->created_at) : 'N/A' }}</div>
                            </div>
                        </li>
                        <li class="d-flex">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-warning"><i class='bx bx-calendar-plus'></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Last Loggedin</p>
                                </div>
                                <div class="item-progress">{{ Auth::user()->last_login ? formatdatetime(Auth::user()->last_login) : 'N/A' }}</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-4 mb-4 order-0">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Plan Info</h5>
                </div>
                <div class="card-body pb-3">
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Package Subscribed:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(Auth::user()->userplan->plan_name))
                                    {{ Auth::user()->userplan->plan_name }}
                                    @else
                                    <span class="badge bg-label-danger">-NA-</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-pie-chart-alt"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Sub-Package:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(Auth::user()->usersubplan->name))
                                    {{ Auth::user()->usersubplan->name }}
                                    @else
                                    <span class="badge bg-label-danger">-NA-</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Duration:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(Auth::user()->usersubplan->duration))
                                    {{ Auth::user()->usersubplan->duration }} @if (Auth::user()->usersubplan->duration_type == 1)
                                    Month
                                    @else
                                    Days
                                    @endif
                                    @else
                                    <span class="badge bg-label-danger">-NA-</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Used Days / Remaining Days:</p>
                                </div>
                                <div class="item-progress">{!! calculate_date_difference(Auth::user()->renew_at, Auth::user()->expired_at) !!}</div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Plan Type:</p>
                                </div>
                                <div class="item-progress"><span class="badge @if (Auth::user()->userplan->plan_type == 0) bg-label-info @else bg-label-warning @endif">
                                        @if (Auth::user()->userplan->plan_type == 0)
                                        Unlimited
                                        @else
                                        FUP
                                        @endif
                                    </span></div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Price:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(Auth::user()->usersubplan->price))
                                    <span class="badge bg-label-success"> (&#8377;)&nbsp;INR
                                        {{ Auth::user()->usersubplan->price }} </span>
                                    @else
                                    <span class="badge bg-label-danger">-N/A-</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class=" d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Advance Renewal:</p>
                                </div>
                                <div class="item-progress">
                                    @if (Auth::user()->is_adv_renewed == 0)
                                    <span class="badge bg-label-danger">--No--</span>
                                    @elseif(Auth::user()->is_adv_renewed == '')
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @else
                                    <span class="badge bg-label-success">--Yes--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-dollar"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">MAC Vendor:</p>
                                </div>
                                <div class="item-progress">{!! getMacVendorName(Auth::user()->macaddress) !!}</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-4 mb-4 order-0">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">User Info</h5>
                </div>
                <div class="card-body pb-3">
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Data Uploaded Today:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_input_octets))
                                    {!! convert_data_size($gettodayusage->total_input_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-pie-chart-alt"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Data Downloaded Today:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_output_octets))
                                    {!! convert_data_size($gettodayusage->total_output_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Total Data Transfer Today:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_inout_total_output_octets))
                                    {!! convert_data_size($gettodayusage->total_inout_total_output_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Data Uploaded Monthly:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_input_octets))
                                    {!! convert_data_size($gettodayusage->total_input_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Data Downloaded Monthly:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_output_octets))
                                    {!! convert_data_size($gettodayusage->total_output_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Total Data Transfer Monthly:</p>
                                </div>
                                <div class="item-progress">
                                    @if (isset(getTodayUsage(Auth::user()->id)->total_inout_total_output_octets))
                                    {!! convert_data_size($gettodayusage->total_inout_total_output_octets) !!}
                                    @else
                                    <span class="badge bg-label-danger">--N/A--</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-credit-card"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Data Balance:</p>
                                </div>
                                <div class="item-progress"></div>
                            </div>
                        </li>
                        <li class="d-flex">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-dollar"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <p class="mb-0 lh-1">Time Used Today:</p>
                                </div>
                                <div class="item-progress"></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- <div class="card">
                <h5 class="card-header text-center">Balance Info</h5>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between" style="position: relative;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                        class="bx bx-dollar fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">
                                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totalinvoiceamount)
                                                        round(getcustomersinvoicedetails(Auth::user()->id)->totalinvoiceamount)
                                                    @else
                                                        0
                                                    @endif
                                                </h5>
                                                <small class="text-muted">Invoice Amount</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between" style="position: relative;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                        class="bx bx-dollar fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">
                                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totalpaidamount)
                                                        round(getcustomersinvoicedetails(Auth::user()->id)->totalpaidamount)
                                                    @else
                                                        0
                                                    @endif
                                                </h5>
                                                <small class="text-muted">Total Paid</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between" style="position: relative;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                        class="bx bx-dollar fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">
                                                    @if (getcustomersinvoicedetails(Auth::user()->id)->totaldueamount)
                                                        round(getcustomersinvoicedetails(Auth::user()->id)->totaldueamount)
                                                    @else
                                                        0
                                                    @endif
                                                </h5>
                                                <small class="text-muted">Due Amount</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between" style="position: relative;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                        class="bx bx-dollar fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">
                                                    @if (getcustomersinvoicedetails(Auth::user()->id)->duedate)
                                                        round(getcustomersinvoicedetails(Auth::user()->id)->duedate)
                                                    @else
                                                        <span class="badge bg-label-danger">-NA-</span>
                                                    @endif
                                                </h5>
                                                <small class="text-muted">Due Date</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
    </div>
    @endif
</div>
@endsection