@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        Invoices
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
    <div class="card">
        <h5 class="card-header">Invoices</h5>
        <div class="p-2 card-datatable table-responsive">
            <table id="@if(count($showinvoices) > 0) example @endif" class="dt-responsive table table-bordered">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Action</th>
                        <th>Invoice Code</th>
                        <th>Status</th>
                        <th>Invoice Date</th>
                        <th>Plan Name</th>
                        <th>Sub Plan Name</th>
                        <th>Amount Paid</th>
                        <th>Amount</th>
                        <th>Last Expiry</th>
                        <th>Upcomming Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($showinvoices as $value)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td></td>
                        <td>@if($value->invoice_code){{ $value->invoice_code }} @else<span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->invoice_status == '1') <span class="badge bg-label-secondary">Open</span>' @elseif(($value->invoice_status == 0))<span class="badge bg-label-secondary">Closed</span>@else<span class="badge bg-label-secondary">Canceled</span>@endif</td>
                        <td>@if($value->created_dt) {{ $value->created_dt }} @else<span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->planname) {{ $value->planname }} @else <span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->subplanname) {{$value->subplanname}} @else <span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->camount_paid) {{ $value->camount_paid }} @else <span class="badge bg-label-info">0</span>@endif</td>
                        <td>@if($value->total_amount) {{ $value->total_amount }}@else <span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->last_expiry_date) {{ formatdatetime($value->last_expiry_date) }} @else <span class="badge bg-label-info">--N/A--</span>@endif</td>
                        <td>@if($value->upcoming_expiry_date) {{ formatdatetime($value->upcoming_expiry_date)}} @else <span class="badge bg-label-info">--N/A--</span>@endif</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No Data Found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@section('styles')
{{-- <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" /> --}}
<link href="https://cdn.datatables.net/v/dt/dt-1.13.8/datatables.min.css" rel="stylesheet">
<style>
    table td {
        max-width: 500px;
        white-space: nowrap;
        text-overflow: auto;
        word-break: break-all;
        overflow: hidden;
    }

    table thead {
        max-width: 500px;
        white-space: nowrap;
        text-overflow: auto;
        word-break: break-all;
        overflow: hidden;
        height: 20px !important;
        font-size: 12px !important;
        font-weight: bold !important;
        color: white !important;
        background: rgb(2, 0, 36);
        background: linear-gradient(90deg, rgba(2, 0, 36, 1) 0%, rgba(9, 9, 121, 1) 35%, rgba(0, 212, 255, 1) 100%);
    }

    .dt-button.buttons-csv {
        border-radius: 55px !important;
        background-color: #0093E9 !important;
        background-image: linear-gradient(160deg, #0093E9 0%, #80D0C7 100%) !important;
        border: none !important;
    }

    .dt-button.buttons-pdf {
        border-radius: 60px !important;
        background-color: #FBAB7E !important;
        background-image: linear-gradient(62deg, #FBAB7E 0%, #F7CE68 100%) !important;
        border: none !important;
    }

    .dt-button.buttons-colvis {
        border-radius: 60px !important;
        background-color: #8BC6EC !important;
        background-image: linear-gradient(135deg, #8BC6EC 0%, #9599E2 100%) !important;
        border: none !important;
    }

    .dt-button.buttons-colvis span {
        color: white !important;
    }

    .custom-select.custom-select-sm.form-control.form-control-sm {
        border-radius: 50px;
    }

    .dataTables_length {
        padding: 5px;
    }


    .dataTables_scrollBody {
        overflow-y: hidden !important;
    }

    .table:not(.table-dark) thead:not(.table-dark) th {
        color: #fff !important;
    }
</style>
@endsection

@section('scripts')
{{-- <script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive/datatables.responsive.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
<script src="{{ asset('assets/js/tables-datatables-advanced.js') }}"></script> --}}
<script src="https://cdn.datatables.net/v/dt/dt-1.13.8/datatables.min.js"></script>
<script>
    new DataTable('#example');
</script>
@endsection