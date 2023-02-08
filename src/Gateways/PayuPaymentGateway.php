<?php

namespace Payu\Gateways;

use Exception;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Payu\Events\PayuPaymentCreated;
use Payu\Events\PayuPaymentCanceled;
use Payu\Events\PayuPaymentConfirmed;
use Payu\Events\PayuPaymentRefunded;
use Payu\Events\PayuPaymentNotified;
use Payu\Interfaces\PayuGatewayAbstract;
use Payu\Interfaces\PayuGatewayInterface;
use Payu\Interfaces\PayuOrderInterface;
use Payu\Http\Payu\OpenPayU_Refunds;
use Payu\Models\Payment;
use OpenPayU_Configuration;
use OpenPayU_Order;
use OpenPayU_Refund;
use OpenPayU_Retrieve;
use OpenPayU_Result;
use OauthCacheFile;
use OpenPayU_Exception;
use OpenPayU_Util;
use OpenPayU;

/**
 * PayU payment gateway
 */
class PayuPaymentGateway extends PayuGatewayAbstract implements PayuGatewayInterface
{
	public $currency = 'PLN';

	public $allowed_ip = ['185.68.12.10', '185.68.12.11', '185.68.12.12', '185.68.12.26', '185.68.12.27', '185.68.12.28'];

	public $allowed_status = ['NEW', 'PENDING', 'WAITING_FOR_CONFIRMATION', 'CANCELED', 'COMPLETED', 'REJECTED', 'FAILED', 'REFUNDED', 'FINALIZED'];

	function __construct()
	{
		$this->env = config('payu.env', 'sandbox');
		$this->pos_id = config('payu.pos_id', '');
		$this->pos_md5 = config('payu.pos_md5', '');
		$this->client_id = config('payu.client_id', '');
		$this->client_secret = config('payu.client_secret', '');
		$this->currency = config('payu.currency', 'PLN');

		// Change in sandbox mode
		if ($this->env == 'sandbox') {
			$this->allowed_ip = ['185.68.14.10', '185.68.14.11', '185.68.14.12', '185.68.14.26', '185.68.14.27', '185.68.14.28'];
		}

		// Create dir
		if (!is_dir(storage_path() . '/framework/cache/payu')) {
			@mkdir(storage_path() . '/framework/cache/payu', 0770);
		}

		// Config
		$this->config();
	}

	function config()
	{
		// Cache
		OpenPayU_Configuration::setOauthTokenCache(
			new OauthCacheFile(storage_path() . '/framework/cache/payu')
		);

		// Environment
		OpenPayU_Configuration::setEnvironment($this->env);

		if (!empty($this->pos_id)) {
			// POS ID and Second MD5 Key (from merchant admin panel)
			OpenPayU_Configuration::setMerchantPosId($this->pos_id);
			OpenPayU_Configuration::setSignatureKey($this->pos_md5);
		}

		if (!empty($this->client_id)) {
			// Oauth Client Id and Oauth Client Secret (from merchant admin panel)
			OpenPayU_Configuration::setOauthClientId($this->client_id);
			OpenPayU_Configuration::setOauthClientSecret($this->client_secret);
		}
	}

	function pay(PayuOrderInterface $order): string
	{
		$payu_url = '';

		// Payment
		$payment = Payment::create([
			'id' => Str::uuid(),
			'order_id' => $order->orderId(),
		]);

		// Client address
		$total = $this->toCents((float) $order->orderCost());
		$desc = 'ID-' . $order->orderId();
		// Credentials
		$o['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
		// Urls
		$o['notifyUrl'] = $this->notifyUrl();
		$o['continueUrl'] = $this->successUrl($order);
		// Order uid string
		$o['extOrderId'] = $payment->id;
		$o['currencyCode'] = $this->currency;
		$o['customerIp'] = $this->ipAddress();
		$o['totalAmount'] = $total;
		$o['description'] = $desc;
		// Products
		$o['products'][0]['name'] = $desc;
		$o['products'][0]['unitPrice'] = $total;
		$o['products'][0]['quantity'] = 1;
		// Buyer
		$o['buyer']['email'] = $order->orderEmail();
		$o['buyer']['phone'] = $order->orderPhone();
		$o['buyer']['firstName'] = $order->orderFirstname();
		$o['buyer']['lastName'] = $order->orderLastname();
		$o['buyer']['language'] = $this->lang();

		// Create payu order
		$res = OpenPayU_Order::create($o);

		if (!$res instanceof OpenPayU_Result || $res->getStatus() != 'SUCCESS') {
			throw new Exception('Invalid payment object or status');
		}

		$payu_uid = $res->getResponse()->orderId ?? null;
		$payu_url = $res->getResponse()->redirectUri ?? null;

		if (empty($payu_uid) || empty($payu_url)) {
			throw new Exception('Invalid payment details');
		}

		$a = [
			'payu_id' => $payu_uid,
			'url' => $payu_url,
			'total' => $total,
			'cost' => $order->orderCost(),
			'currency' => $this->currency,
			'ip' => $this->ipAddress(),
			'gateway' => 'payu'
		];

		$payment->fill($a);
		$payment->save();

		// Emit event
		PayuPaymentCreated::dispatch($order);

		return $payu_url;
	}

	function confirm(PayuOrderInterface $order): string
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			if (!in_array($p->status, ['WAITING_FOR_CONFIRMATION'])) {
				throw new Exception('You can not update payment with this status');
			}

			$res = OpenPayU_Order::statusUpdate([
				"orderId" => $p->payu_id,
				"orderStatus" => 'COMPLETED'
			]);

			if ($res->getStatus() == 'SUCCESS') {
				// Emit event
				PayuPaymentConfirmed::dispatch($order);

				return 'UPDATED';
			} else {
				throw new Exception('Status update error');
			}
		} else {
			throw new Exception('Invalid payment id');
		}
	}

	function cancel(PayuOrderInterface $order): string
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			if (!in_array($p->status, ['WAITING_FOR_CONFIRMATION'])) {
				throw new Exception('You can not update payment with this status');
			}

			$res = OpenPayU_Order::cancel($p->payu_id);

			if ($res->getStatus() == 'SUCCESS') {
				if (!empty($res->getResponse()->orderId)) {
					// Emit event
					PayuPaymentCanceled::dispatch($order);

					return 'UPDATED';
				}
			} else {
				throw new Exception('Status update error');
			}
		} else {
			throw new Exception('Invalid payment id or status');
		}
	}

	function refund(PayuOrderInterface $order): string
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			// Refund full order
			$res = OpenPayU_Refund::create($p->payu_id, __('Refunding'), null);

			if ($res->getStatus() == 'SUCCESS') {
				if ($res->getResponse()->refund->status == 'PENDING') {
					$p->status_refund = 'PENDING';
					$p->save();

					// Emit event
					PayuPaymentRefunded::dispatch($order);

					return 'PENDING';
				} else {
					throw new Exception('Order has not been refunded');
				}
			} else {
				throw new Exception('Order has not been refunded');
			}
		} else {
			throw new Exception('Invalid payment id');
		}
	}

	function refunds(PayuOrderInterface $order)
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			// Get refunds from payu
			$res = OpenPayU_Refunds::retrive($p->payu_id);

			if (
				$res instanceof OpenPayU_Result ||
				$res->getStatus() == 'SUCCESS'
			) {
				if (empty($res->getResponse()->refunds[0])) {
					throw new Exception("Empty refunds list");
				}

				$payu_refund = $res->getResponse()->refunds[0];

				if (
					!is_object($payu_refund) ||
					empty($payu_refund->status)
				) {
					throw new Exception("Invalid refund details");
				}

				// Validate status
				if (in_array($payu_refund->status, $this->allowed_status)) {
					// Update shop payment
					$p->status_refund = strtoupper($payu_refund->status);
					$p->save();

					return $res->getResponse();
				} else {
					throw new Exception('Invalid refund status');
				}
			} else {
				throw new Exception('Invalid refund status');
			}
		} else {
			throw new Exception('Invalid refund id');
		}
	}

	function refresh(PayuOrderInterface $order): string
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			// Get payment from payu
			$res = OpenPayU_Order::retrieve($p->payu_id);

			if (
				$res instanceof OpenPayU_Result ||
				$res->getStatus() == 'SUCCESS'
			) {
				if (empty($res->getResponse()->orders[0])) {
					throw new Exception("Empty payment orders array");
				}

				$payu_order = $res->getResponse()->orders[0];

				if (
					!is_object($payu_order) ||
					empty($payu_order->status) ||
					empty($payu_order->totalAmount) ||
					empty($payu_order->currencyCode)
				) {
					throw new Exception("Invalid payment details");
				}

				// Validate status
				if (in_array($payu_order->status, $this->allowed_status)) {
					// Update shop payment
					$p->status = strtoupper($payu_order->status);
					$p->currency = $payu_order->currencyCode;
					$p->total = $payu_order->totalAmount;
					$p->save();

					// Return order status
					return $payu_order->status;
				} else {
					throw new Exception('Invalid order status');
				}
			} else {
				throw new Exception('Invalid payment status');
			}
		} else {
			throw new Exception('Invalid payment id');
		}
	}

	function retrive(PayuOrderInterface $order)
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			$res = OpenPayU_Order::retrieve($p->payu_id);

			if ($res->getStatus() == 'SUCCESS') {
				if (count($res->getResponse()->orders) == 0) {
					throw new Exception("Empty orders array");
				}

				return $res->getResponse()->orders[0];
			}
		} else {
			throw new Exception('Invalid payment id');
		}
	}

	function transaction(PayuOrderInterface $order)
	{
		$p = Payment::where(['order_id' => $order->orderId()])->latest()->first();

		if ($p instanceof Payment) {
			$res = OpenPayU_Order::retrieveTransaction($p->payu_id);

			if (count($res->getResponse()->transactions) == 0) {
				throw new Exception("Empty transactions array");
			}

			return $res->getResponse()->transactions[0];
		} else {
			throw new Exception('Invalid payment id');
		}
	}

	function payments($lang = 'pl')
	{
		$res = OpenPayU_Retrieve::payMethods($lang);
		return $res->getResponse();
	}

	/**
	 * Get notifications from payu
	 *
	 * @return Response Return http response with status 200 or 422.
	 */
	function notify()
	{
		try {
			if (!in_array($this->ipAddress(), $this->allowed_ip)) {
				throw new Exception('Notify invalid ip address', 422);
			}

			$data = trim(request()->getContent()); // Json

			if (empty($data)) {
				throw new Exception('Notify invalid data');
			}

			if ($this->env == 'sandbox') {
				Log::info($data, [
					'error' => 'PAYU_NOTIFY',
					'ip' => $this->ipAddress()
				]);
			}

			// Prepare notification
			$notify = OpenPayU_Order::consumeNotification($data);

			if (!$notify instanceof OpenPayU_Result) {
				throw new Exception("Notify invalid object");
			}

			PayuPaymentNotified::dispatch($notify);

			// Confirm Order
			if (!empty($notify->getResponse()->order->extOrderId)) {
				$p = Payment::where('id', $notify->getResponse()->order->extOrderId)->first();
				if ($p instanceof Payment) {
					$this->refresh(Order::find($p->order_id));
					return response("Comfirmed", 200);
				}
			}

			// Confirm Refund
			if (
				!empty($notify->getResponse()->refund) &&
				!empty($notify->getResponse()->extOrderId)
			) {
				$p = Payment::where('id', $notify->getResponse()->extOrderId)->first();
				if ($p instanceof Payment) {
					$this->refunds(Order::find($p->order_id));
					return response("Comfirmed", 200);
				}
			}

			throw new Exception("Invalid notification content");
		} catch (Exception $e) {
			Log::info($e->getMessage(), [
				'error' => 'PAYU_NOTIFIY',
				'ip' => $this->ipAddress()
			]);
			return response("Not comfirmed", 422);
		}
	}

	function notifyUrl(): string
	{
		// https://your.page/web/payment/notify/{gateway}
		return request()->getSchemeAndHttpHost() . '/web/payment/notify/payu';
	}

	function successUrl(PayuOrderInterface $order): string
	{
		// https://your.page/web/payment/success/{order}
		return request()->getSchemeAndHttpHost() . '/web/payment/success/payu/' . $order->orderId() . '?lang=' . app()->getLocale();
	}

	function ipAddress(): string
	{
		return request()->ip();
	}

	function lang(): string
	{
		return strtolower(app()->getLocale()) ?? 'pl';
	}

	function logo($square = false): string
	{
		if ($square == true) {
			return 'vendor/payu/payu_square.png';
		}

		return 'vendor/payu/payu.png';
	}

	function toCents(float $decimal): int
	{
		if ($decimal < 0.01) {
			throw new Exception("Minimal decimal value: 0.01", 422);
		}

		if (!preg_match('/^\d+(\.\d{1,2})?$/', $decimal)) {
			throw new Exception("Invalid decimal value", 422);
		}

		return number_format($decimal * 100, 0, '.', '');
	}
}
