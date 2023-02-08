<?php

namespace Payu\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Payu\Facades\Payu;
use Illuminate\Database\QueryException;
use PDOException;
use Exception;

// Sandbox testing only
class PayuPaymentController extends Controller
{
	function notify()
	{
		return Payu::notify();
	}

	function create()
	{
		try {
			$order = Order::create([
				'cost' => 123.79,
				'firstname' => 'Marysia',
				'lastname' => 'Malinka',
				'email' => 'masysia@localhost',
				'phone' => '+48100200300',
				'payment_method' => 'online',
				'payment_gateway' => 'payu',
			]);

			$url = Payu::pay($order);

			return response()->json([
				'url' => $url,
				'order_id' => $order->id
			]);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function pay(Order $order)
	{
		try {
			return response()->json([
				'url' => Payu::pay($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function confirm(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::confirm($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function cancel(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::cancel($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function refresh(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::refresh($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function retrive(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::retrive($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function transaction(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::transaction($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function refund(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::refund($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database Error.', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function refunds(Order $order)
	{
		try {
			return response()->json([
				'message' => Payu::refunds($order)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}

	function payments($lang)
	{
		try {
			return response()->json([
				'message' => Payu::payments($lang)
			]);
		} catch (PDOException | QueryException $e) {
			report($e);
			return response('Database error', 422);
		} catch (Exception $e) {
			report($e);
			return response($e->getMessage(), 422);
		}
	}
}
