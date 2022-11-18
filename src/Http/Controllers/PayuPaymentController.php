<?php

namespace Payu\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Payu\Facades\Payu;

class PayuPaymentController extends Controller
{
	function notify()
	{
		return Payu::notify();
	}

	function pay(Order $order)
	{
		return response()->json([
			'url' => Payu::pay($order)
		]);
	}

	function confirm(Order $order)
	{
		return response()->json([
			'message' => Payu::confirm($order)
		]);
	}

	function cancel(Order $order)
	{
		return response()->json([
			'message' => Payu::cancel($order)
		]);
	}

	function refresh(Order $order)
	{
		return response()->json([
			'message' => Payu::refresh($order)
		]);
	}

	function retrive(Order $order)
	{
		return response()->json([
			'message' => Payu::retrive($order)
		]);
	}

	function transaction(Order $order)
	{
		return response()->json([
			'message' => Payu::transaction($order)
		]);
	}

	function refund(Order $order)
	{
		return response()->json([
			'message' => Payu::refund($order)
		]);
	}

	function refunds(Order $order)
	{
		return response()->json([
			'message' => Payu::refunds($order)
		]);
	}

	function payments($lang)
	{
		return response()->json([
			'message' => Payu::payments($lang)
		]);
	}
}
