<?php

namespace Payu;

use Payu\Gateways\PayuPaymentGateway;
use Payu\Interfaces\PayuOrderInterface;

class Payu
{
	protected $url;

	public function pay(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->pay($order);
	}

	function confirm(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->confirm($order);
	}

	function cancel(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->cancel($order);
	}

	function refresh(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->refresh($order);
	}

	function retrive(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->retrive($order);
	}

	function transaction(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->transaction($order);
	}

	function refund(PayuOrderInterface $order)
	{
		return (new PayuPaymentGateway())->refund($order);
	}

	function refunds(PayuOrderInterface $order)
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
