@extends('layouts.app')

@section('title', 'Renewal')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        Renewal
    </h4>

    <div class="card">
        <div class="card-body">
            @if(!empty($pgdetails) && $pgdetails->default_pg == 0)
            <form action="{{ route('razorpaypg-checkout') }}" method="post">
                @else
                <form action="{{ route('razorpaypg-checkout') }}" method="post">
                    <!-- <form action="ccavenuepg/checkout" method="post"> -->
                    @endif
                    @csrf
                    <input type="hidden" name="partner_id" value="{{ Auth::user()->partner_id }}">
                    <input type="hidden" name="id" value="{{ Auth::user()->id }}">
                    <input type="hidden" name="mobile" value="{{ Auth::user()->mobno }}">
                    <input type="hidden" name="email" value="{{ Auth::user()->email }}">
                    <input type="hidden" name="name" value="{{ Auth::user()->name }}">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">ClientName</label>
                            <input type="text" disabled class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">UserName</label>
                            <input type="text" disabled class="form-control" value="{{ Auth::user()->username }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Package</label>
                            <div class="position-relative">
                                <select name="plan_id" id="plan_id" class="select2 form-select select2-hidden-accessible">
                                    <option value="">Select Plan</option>
                                    @if(!empty($planbyop))
                                    @foreach ($planbyop as $key => $value)
                                    <option @if(Auth::user()->plan_id == $value->plan_id) selected @endif value="{{ $value->plan_id }}">{{ $value->plan_name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SubPackage</label>
                            <div class="position-relative">
                                <select name="subplan_id" id="subplan_id" class="select2 form-select select2-hidden-accessible">
                                    <option value="">Select SubPlan</option>
                                    @if(!empty($subplanbyop))
                                    @foreach ($subplanbyop as $key => $value)
                                    <option @if(Auth::user()->subplan_id == $value->subplan_id) selected @endif value="{{ $value->subplan_id }}">{{ $value->name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <hr class="my-4 mx-n4">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="dt">Price to Subscriber</div>
                                <div class="dd" id="baseprice">
                                    &#8377;
                                    @if(isset($subplanprice) && isset($subplanprice->price))
                                    {{ (int) $subplanprice->price }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="dt">Total GST @18.0%</div>
                                <div class="dd" id="taxamount">
                                    &#8377;
                                    @if(isset($subplanprice) && isset($subplanprice->tax_price))
                                    {{ (int) $subplanprice->tax_price }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="dt">Total Amount</div>
                                <div class="dd" id="totalprice">
                                    &#8377;
                                    @if(isset($subplanprice) && isset($subplanprice->total_price))
                                    {{ (int) $subplanprice->total_price }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 d-flex justify-content-center">
                            @if(strtotime(Auth::user()->expired_at) <= time()) <button type="submit" class="btn btn-primary me-sm-3 me-1">Renew</button> @endif
                                @if(strtotime(Auth::user()->expired_at) >= time()) <button type="submit" name="advancedrenew" class="btn btn-primary me-sm-3 me-1">Advance Renew</button> @endif
                        </div>
                    </div>
                </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        //FOR PLAN ID
        $("#plan_id").change(function(e) {
            e.preventDefault();
            plan_id = $(this).val();
            if (plan_id !== "") {
                $.ajax({
                    type: "post",
                    url: "{{route('subplanbyplanid')}}",
                    data: {
                        _token: '{{ csrf_token() }}',
                        plan_id: plan_id
                    },
                    dataType: "json",
                    success: function(response) {
                        $("#subplan_id").empty();
                        $("#subplan_id").append('<option value="">Select SubPlan</option>');
                        $.each(response, function(key, value) {
                            $("#subplan_id").append('<option value="' + value.subplan_id + '">' + value.name + '</option>');
                        });
                        $("#baseprice").empty();
                        $("#taxamount").empty();
                        $("#totalprice").empty();
                    }
                });
            } else {
                $("#subplan_id").empty();
                $("#subplan_id").append('<option value="">Select SubPlan</option>');
                $("#baseprice").empty();
                $("#taxamount").empty();
                $("#totalprice").empty();
            }
        });

        //FOR SUBPLAN ID
        $("#subplan_id").change(function(e) {
            e.preventDefault();
            subplan_id = $(this).val();
            if (subplan_id !== "") {
                $.ajax({
                    type: "post",
                    url: "{{ route('getsubplanbyidfordata') }}",
                    data: {
                        _token: '{{ csrf_token() }}',
                        subplan_id: subplan_id
                    },
                    dataType: "json",
                    success: function(response) {
                        $("#baseprice").html('&#8377 ' + response.price);
                        $("#taxamount").html('&#8377 ' + response.tax_price);
                        $("#totalprice").html('&#8377 ' + response.total_price);
                    }
                });
            } else {
                $("#subplan_id").empty();
                $("#subplan_id").append('<option value="">Select SubPlan</option>');
                $("#baseprice").empty();
                $("#taxamount").empty();
                $("#totalprice").empty();
            }
        });
    });
</script>
@endsection