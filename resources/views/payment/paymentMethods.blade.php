<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="utf-8">
    <title></title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <!-- Viewport-->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon and Touch Icons-->
    <link rel="shortcut icon" href="favicon.ico">
    <!-- Font -->
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{url('/payment')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{url('/payment')}}/css/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{url('/payment')}}/css/theme.minc619.css?v=1.0">
    <script src="{{url('/payment')}}/css/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{url('/payment')}}/css/toastr.css">
    <style>
        .stripe-button-el {
            display: none !important;
        }
        .razorpay-payment-button {
            display: none !important;
        }
    </style>
    <link rel="stylesheet" href="{{url('/payment')}}/css/bootstrap.css">
</head>
<!-- Body-->
<body class="toolbar-enabled">
<!-- Page Content-->
<div class="container pb-5 mb-2 mb-md-4">
    <div class="row">
        <div class="col-md-12 mb-5 pt-5">
            <center class="">
                <h1>Payment method</h1>
            </center>
        </div>
        <section class="col-lg-12">
            <div class="checkout_details mt-3">
                <div class="row">
                    <div class="col-md-12 mb-4" style="cursor: pointer">
                        <div class="card">
                            <div class="card-body">
                                <form class="needs-validation" method="POST" id="payment-form" action="{{route('payWay',['visa',$order->id,$user->id])}}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-block" type="submit">
                                        <img src="{{url('/payment')}}/meza-visa.png"/>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mb-4" style="cursor: pointer">
                        <div class="card">
                            <div class="card-body">
                                <form class="needs-validation" method="POST" id="payment-form" action="{{route('show_phone_page',['wallet',$order->id,$user->id])}}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-block" type="submit">
                                        <img width="100%" src="{{url('/payment')}}/mobile.png"/>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- JS Front -->
<script src="{{url('/payment')}}/js/jquery.js"></script>
<script src="{{url('/payment')}}/js/bootstrap.js"></script>
<script src="{{url('/payment')}}/js/sweet_alert.js"></script>
<script src="{{url('/payment')}}/js/toastr.js"></script>
<script type="text/javascript"></script>

<script>
    setTimeout(function () {
        $('.stripe-button-el').hide();
        $('.razorpay-payment-button').hide();
    }, 10)
</script>

</body>
</html>
