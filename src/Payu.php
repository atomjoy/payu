<?php

namespace Payu;

use Exception;
use App\Models\Order;
use Payu\Gateways\PayuPaymentGateway;

class Payu
{
	protected $url;

	public function pay(Order $order)
	{
		return (new PayuPaymentGateway())->pay($order);
	}

	function confirm(Order $order)
	{
		return (new PayuPaymentGateway())->confirm($order);
	}

	function cancel(Order $order)
	{
		return (new PayuPaymentGateway())->cancel($order);
	}

	function refresh(Order $order)
	{
		return (new PayuPaymentGateway())->refresh($order);
	}

	function retrive(Order $order)
	{
		return (new PayuPaymentGateway())->retrive($order);
	}

	function transaction(Order $order)
	{
		return (new PayuPaymentGateway())->transaction($order);
	}

	function refund(Order $order)
	{
		return (new PayuPaymentGateway())->refund($order);
	}

	function refunds(Order $order)
	{
		return (new PayuPaymentGateway())->refunds($order);
	}

	function payments($lang = 'pl')
	{
		return (new PayuPaymentGateway())->payments($lang);
	}

	function notify()
	{
		return (new PayuPaymentGateway())->notify();
	}

	function logo()
	{
		return (new PayuPaymentGateway())->logo();
	}
}
