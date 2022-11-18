@php
if(!empty(request()->input('lang'))) {
app()->setLocale(request()->input('lang'));
}
@endphp

<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@lang('payu::page.payment_confirmation')</title>

	<link href="https://fonts.googleapis.com/css2?family=Hubballi&display=swap" rel="stylesheet">

	<style type="text/css">
		body {
			color: #003399;
			font-family: Hubballi;
		}

		.box {
			margin: 100px auto;
			width: 90%;
			max-width: 600px;
			padding: 20px;
		}

		.hi {
			font-weight: 700;
			font-size: 33px;
			margin-bottom: 20px;
		}

		.bye {
			font-weight: 700;
			font-size: 23px;
			margin-top: 20px;
		}

		.alert {
			padding: 20px;
			color: #55cc55;
			background: #55cc5511;
			border: 1px solid #55cc5533;
			border-radius: 10px;
			font-weight: 700;
			font-size: 20px;
		}
	</style>
</head>

<body>
	<div class="box">
		<div class="order">
			<div class="hi">@lang('payu::page.hello')!</div>
			<div class="alert"> @lang('payu::page.order') ID-{{ $order->id }} @lang('payu::page.has_been_paid').</div>
			<div class="bye">@lang('payu::page.nice_day').</div>
		</div>
	</div>
</body>

</html>