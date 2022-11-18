<?php

namespace Payu\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;

/**
 * Payu notification page
 */
class PayuPaymentPageController extends Controller
{
	function success(Order $order)
	{
		if (!empty(request()->input('error'))) {
			return $this->error($order);
		}

		return view('payu::page.success', ['order' => $order]);
	}

	function error(Order $order)
	{
		return view('payu::page.error', ['order' => $order, 'error' => request()->input('error')]);
	}
}
