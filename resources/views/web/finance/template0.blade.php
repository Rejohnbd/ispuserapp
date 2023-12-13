<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Laralink">
    <title>General Purpose Invoice</title>
    <link rel="stylesheet" href="{{ asset('assets/pdf_assets/css/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .tm_gray_bg.tm_round_border {
            padding-left: 20px;
            margin-left: -20px;
        }
    </style>
</head>

<body>
    <div class="tm_container">
        <div class="tm_invoice_wrap">
            <div class="tm_invoice tm_style1 tm_type1" id="tm_download_section">
                <div class="tm_invoice_in">
                    <div class="tm_invoice_head tm_top_head tm_mb15 tm_align_center">
                        <div class="tm_invoice_left">
                            <div class="tm_logo"><img src="{{ asset('assets/pdf_assets/img/isp_image.png') }}" alt="Logo"></div>
                        </div>
                        <div class="tm_invoice_right tm_text_right tm_mobile_hide">
                            <div class="tm_f50 tm_text_uppercase tm_accent_color">Invoice</div>
                        </div>
                        <div class="tm_shape_bg tm_accent_bg_20 tm_mobile_hide"></div>
                    </div>
                    <div class="tm_invoice_info tm_mb25">
                        <div class="tm_card_note tm_mobile_hide"><b class="tm_primary_color">Payment Method: </b> {{ ($invoiceshow->payment_mode == '1' ? 'Online' : 'Cash') }} </div>
                        <div class="tm_invoice_info_list tm_accent_color">
                            <p class="tm_invoice_number tm_m0"> INVOICE NUMBER: <b>{{ $invoiceshow->invoice_code }}</b></p>
                            <!-- <p class="tm_invoice_date tm_m0"> <b> </b></p> -->
                        </div>
                        <div class="tm_invoice_seperator tm_accent_bg_20"></div>
                    </div>
                    <div class="tm_invoice_head tm_mb10">
                        <div class="tm_invoice_left">
                            <p class="tm_mb2"><b class="tm_primary_color">Invoice To:</b></p>
                            <p>
                                <i class="ri-shield-user-line tm_accent_color"></i> {{ $invoiceshow->customername }}</br>
                                <i class="ri-home-smile-line tm_accent_color"></i> {{ $invoiceshow->caddress }}</br>
                                <i class="ri-cellphone-fill tm_accent_color"></i> {{ $invoiceshow->mobile }}</br>
                                <i class="ri-mail-line tm_accent_color"></i> {{ $invoiceshow->email }}</br>
                            </p>
                        </div>
                        <div class="tm_invoice_right tm_text_right">
                            <p class="tm_mb2"><b class="tm_primary_color">Pay To:</b></p>
                            <p>
                                <i class="ri-team-fill tm_accent_color"></i>{{ $billcomprofile->company_name ? $billcomprofile->company_name : 'N/A' }} <br>
                                <i class="ri-building-2-line tm_accent_color"></i> @if($billcomprofile->company_address) {{ strip_tags($billcomprofile->company_address) }} @else N/A @endif <br>
                                <i class="ri-scales-line tm_accent_color"></i> GST NO: {{ $billcomprofile->gst_no ? $billcomprofile->gst_no : 'N/A' }} <br>
                                <i class="ri-scales-line tm_accent_color"></i> PAN NO: {{ $billcomprofile->pan_no ? $billcomprofile->pan_no : 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="tm_table tm_style1 tm_mb30">
                        <div>
                            <div class="tm_table_responsive">
                                <table>
                                    <thead>
                                        <tr class="tm_accent_bg_20">
                                            <th class="tm_width_1 tm_semi_bold tm_accent_color">#</th>
                                            <th class="tm_width_3 tm_semi_bold tm_accent_color">Plan Name</th>
                                            <th class="tm_width_2 tm_semi_bold tm_accent_color">SubPlan</th>
                                            <th class="tm_width_1 tm_semi_bold tm_accent_color">Qty</th>
                                            <th class="tm_width_2 tm_semi_bold tm_accent_color tm_text_right">BasePrice</th>
                                            <th class="tm_width_2 tm_semi_bold tm_accent_color tm_text_right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="tm_width_1 tm_border_top_0">1</td>
                                            <td class="tm_width_3 tm_border_top_0">{{ $invoiceshow->planname }}</td>
                                            <td class="tm_width_2 tm_border_top_0">{{ $invoiceshow->suplanname }}</td>
                                            <td class="tm_width_1 tm_border_top_0">1</td>
                                            <td class="tm_width_2 tm_border_top_0 tm_text_right">₹{{ $invoiceshow->base_price }}</td>
                                            <td class="tm_width_2 tm_border_top_0 tm_text_right">₹{{ $invoiceshow->total_amount }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tm_invoice_footer tm_border_top tm_mb15 tm_m0_md">
                            <div class="tm_left_footer">
                                <p class="tm_mb2"><b class="tm_primary_color">Billing info:</b></p>
                                <p class="tm_m0"> Invoice Date: {{ ($invoiceshow->created_dt) }} <br>
                                <p class="tm_m0"> Billing Period:{{ date('d-m-Y', strtotime($invoiceshow->created_dt)) }} - {{ date('d-m-Y', strtotime($invoiceshow->upcoming_expiry_date)) }} <br>
                                <p class="tm_m0"> Due Date: {{ date("l, F j, Y", strtotime($invoiceshow->due_on)) }} <br>
                                </p>
                            </div>
                            <div class="tm_right_footer">
                                <table class="tm_mb15">
                                    <tbody>
                                        @if ($invoiceshow->tax_amount == '0')
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">CGST <span class="tm_ternary_color">(9%)</span></td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹0</td>
                                        </tr>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">SGST <span class="tm_ternary_color">(9%)</span></td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹0</td>
                                        </tr>
                                        @else
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">CGST <span class="tm_ternary_color">(9%)</span></td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹{{ ($invoiceshow->base_price * 9 / 100) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">SGST <span class="tm_ternary_color">(9%)</span></td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹ {{ ($invoiceshow->base_price * 9 / 100) }}</td>
                                        </tr>
                                        @endif;
                                        <tr class="tm_accent_bg_20">
                                            <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_accent_color">Grand Total </td>
                                            <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_accent_color tm_text_right">₹{{ round($invoiceshow->total_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">Paid </td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹{{ round($invoiceshow->camount_paid) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color">Balance Due </td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right">₹ {{ round($invoiceshow->total_amount - $invoiceshow->camount_paid) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if(isset($billcomprofile->invoice_footer) && !empty($billcomprofile->invoice_footer))
                    <hr class="tm_mb20">
                    <div class="tm_gray_bg tm_round_border">
                        <p>{!! $billcomprofile->invoice_footer !!}</p>
                    </div>
                    </br>
                    @endif
                    <hr class="tm_mb20">
                    <p class="tm_mb0 tm_text_center tm_accent_color">This is a computer generatated invoice hence require no signature</p>
                </div>
            </div>
            <div class="tm_invoice_btns tm_hide_print">
                <button id="tm_download_btn" class="tm_invoice_btn tm_color2">
                    <span class="tm_btn_icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                            <path d="M320 336h76c55 0 100-21.21 100-75.6s-53-73.47-96-75.6C391.11 99.74 329 48 256 48c-69 0-113.44 45.79-128 91.2-60 5.7-112 35.88-112 98.4S70 336 136 336h56M192 400.1l64 63.9 64-63.9M256 224v224.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" />
                        </svg>
                    </span>
                    <span class="tm_btn_text">Download</span>
                </button>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/pdf_assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/pdf_assets/js/jspdf.min.js') }}"></script>
    <script src="{{ asset('assets/pdf_assets/js/html2canvas.min.js') }}"></script>


    <script>
        (function($) {
            'use strict';

            $('#tm_download_btn').on('click', function() {
                var downloadSection = $('#tm_download_section');
                var cWidth = downloadSection.width();
                var cHeight = downloadSection.height();
                var topLeftMargin = 0;
                var pdfWidth = cWidth + topLeftMargin * 2;
                var pdfHeight = pdfWidth * 1.5 + topLeftMargin * 2;
                var canvasImageWidth = cWidth;
                var canvasImageHeight = cHeight;
                var totalPDFPages = Math.ceil(cHeight / pdfHeight) - 1;

                html2canvas(downloadSection[0], {
                    allowTaint: true
                }).then(function(
                    canvas
                ) {
                    canvas.getContext('2d');
                    var imgData = canvas.toDataURL('image/png', 1.0);
                    var pdf = new jsPDF('p', 'pt', [pdfWidth, pdfHeight]);
                    pdf.addImage(
                        imgData,
                        'PNG',
                        topLeftMargin,
                        topLeftMargin,
                        canvasImageWidth,
                        canvasImageHeight
                    );
                    for (var i = 1; i <= totalPDFPages; i++) {
                        pdf.addPage(pdfWidth, pdfHeight);
                        pdf.addImage(
                            imgData,
                            'PNG',
                            topLeftMargin,
                            -(pdfHeight * i) + topLeftMargin * 0,
                            canvasImageWidth,
                            canvasImageHeight
                        );
                    }
                    pdf.save("{{ $invoiceshow->customername . ' - ' . date('Y-m-d') }}.pdf");
                });
            });

        })(jQuery);
    </script>
</body>

</html>