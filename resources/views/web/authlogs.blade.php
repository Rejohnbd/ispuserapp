@extends('layouts.app')

@section('title', 'Session History')

@section('content')

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<!-- DataTables Responsive Bootstrap 5 CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />

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
        color: rgb(12, 12, 12) !important;
        /* background: rgb(2, 0, 36);
            background: linear-gradient(90deg, rgba(2, 0, 36, 1) 0%, rgba(9, 9, 121, 1) 35%, rgba(0, 212, 255, 1) 100%); */
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
        color: #131212 !important;
    }

    .control-sm {
        height: calc(1.25rem + 10px) !important;
    }
</style>
@endsection

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        Authentication Logs
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
        <h5 class="card-header">Authentication Logs</h5>
        <div class="p-2 card-datatable table-responsive">
            <table id="example" class="dt-column-search table table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Reply-Status</th>
                        <th>Reply-Message</th>
                        <th>DateTime(H:i:S)</th>
                        <th>NasIpAddress</th>
                        <th>Mac</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($authlogs as $value)
                    <tr>
                        <td>@if($value->username) {{ $value->username }} @else <span class="badge bg-label-danger">--N/A--</span> @endif </td>
                        <td>@if($value->pass) <span class="badge bg-label-info">{{ $value->pass }}</span> @else <span class="badge bg-label-danger">--N/A--</span>@endif</td>
                        <td>@if($value->reply == 'Access-Accept') <span class="badge bg-label-success">ACCEPT</span>@else<span class="badge bg-label-danger">REJECT</span>@endif</td>
                        <td>
                            @if($value->reply == 'Access-Accept' && $value->reply_msg == "")
                            Authenticated Successful
                            @else
                            {{$value->reply_msg}}
                            @endif
                        </td>
                        <td>@if($value->authdate) {{ formatdatetime($value->authdate) }} @else <span class="badge bg-label-danger">--N/A--</span>@endif</td>
                        <td>@if($value->nasipaddress) {{ $value->nasipaddress }} @else <span class="badge bg-label-danger">--N/A--</span>@endif</td>
                        <td>@if($value->mac) {{ $value->mac}} @else <span class="badge bg-label-danger">--N/A--</span>@endif</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No Data Found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection


@section('scripts')
<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive/datatables.responsive.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>

<!-- Flat Picker -->
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>

<!-- Main JS -->
{{-- <script src="{{ asset('assets/js/main.js') }}"></script> --}}

<script>
    $(document).ready(function() {
        // Setup - add a text input to each footer cell
        $('#example thead tr')
            .clone(true)
            .addClass('filters')
            .appendTo('#example thead');

        var table = $('#example').DataTable({
            orderCellsTop: true,
            fixedHeader: true,
            initComplete: function() {
                var api = this.api();

                // For each column
                api
                    .columns()
                    .eq(0)
                    .each(function(colIdx) {
                        // Set the header cell to contain the input element
                        var cell = $('.filters th').eq(
                            $(api.column(colIdx).header()).index()
                        );
                        var title = $(cell).text();
                        $(cell).html(
                            '<input type="text" class="form-control control-sm" placeholder="' +
                            title + '" />');

                        // On every keypress in this input
                        $(
                                'input',
                                $('.filters th').eq($(api.column(colIdx).header()).index())
                            )
                            .off('keyup change')
                            .on('change', function(e) {
                                // Get the search value
                                $(this).attr('title', $(this).val());
                                var regexr =
                                    '({search})'; //$(this).parents('th').find('select').val();

                                var cursorPosition = this.selectionStart;
                                // Search the column for that value
                                api
                                    .column(colIdx)
                                    .search(
                                        this.value != '' ?
                                        regexr.replace('{search}', '(((' + this.value +
                                            ')))') :
                                        '',
                                        this.value != '',
                                        this.value == ''
                                    )
                                    .draw();
                            })
                            .on('keyup', function(e) {
                                e.stopPropagation();

                                $(this).trigger('change');
                                $(this)
                                    .focus()[0]
                                    .setSelectionRange(cursorPosition, cursorPosition);
                            });
                    });
            },
        });
    });
</script>
@endsection