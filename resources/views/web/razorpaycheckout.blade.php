<div>Do Not Press Any Button Payment Is processing Please Wait!!!!!!!</div>
<html>
<button id="rzp-button1" class="btn btn-outline-dark btn-lg"><i class="fas fa-money-bill"></i>Pay</button>
<button id="rzp-close1" onclick='window.location.href="{{ route('renewal') }}"' class="btn btn-outline-dark btn-lg"><i class="fas fa-money-bill"></i>Return</button>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
    var options = {
        "key": "{{ $key }}", // Enter the Key ID generated from the Dashboard
        "amount": " $order['amount'] ",
        "currency": "INR",
        "name": "BSBNETWORKS",
        "description": "New Payment",
        "image": "http://34.131.248.27/public/assets/img/logo.png",
        "order_id": "{{ $order['id'] }}",
        "callback_url": "{{ route('razorpaypg-status') }}",
        "prefill": {
            "name": "{{ $dataofcust['custname'] }}",
            "email": "{{ $dataofcust['email'] }}",
            "contact": "{{ $dataofcust['mobile'] }}",
        },
        "notes": "BSBNETWORKS SOLUTIONS PVT LTD...",
        "theme": {
            "color": "#3399cc"
        },
    };
    var rzp1 = new Razorpay(options);
    document.getElementById('rzp-button1').onclick = function(e) {
        rzp1.open();
        e.preventDefault();
    }

    document.getElementById('rzp-button1').click();
</script>

</html>