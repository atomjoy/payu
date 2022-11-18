<?php

namespace Payu\Gateways;

use Exception;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Payu\Interfaces\PayuGatewayInterface;
use Payu\Interfaces\PayuGatewayAbstract;
use Payu\Models\Payment;
use Payu\Models\PayuLog;
use Payu\Events\PayuPaymentCreated;
use Payu\Events\PayuPaymentNotCreated;
use Payu\Events\PayuPaymentCanceled;
use Payu\Events\PayuPaymentConfirmed;
use Payu\Events\PayuPaymentRefunded;
use Payu\Events\PayuPaymentNotified;
use Payu\Http\Payu\OpenPayU_Refunds;
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
		try {
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
		} catch (Exception $e) {
			$this->log('PAYU_CONFIG_ERR', $e->getMessage());
			throw new Exception($e->getMessage(), 422);
		}
	}

	function pay(Order $order): string
	{
		$payu_url = '';

		try {
			// Client address
			$client = $order->client;
			$total = $this->toCents((float) $order->cost);
			$desc = 'ID-' . $order->uid;
			// Credentials
			$o['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
			// Urls
			$o['notifyUrl'] = $this->notifyUrl();
			$o['continueUrl'] = $this->successUrl($order);
			// Order uid string
			$o['extOrderId'] = $order->uid;
			$o['currencyCode'] = $this->currency;
			$o['customerIp'] = $this->ipAddress();
			$o['totalAmount'] = $total;
			$o['description'] = $desc;
			// Products
			$o['products'][0]['name'] = $desc;
			$o['products'][0]['unitPrice'] = $total;
			$o['products'][0]['quantity'] = 1;
			// Buyer
			$o['buyer']['email'] = $client->email;
			$o['buyer']['phone'] = $client->mobile;
			$o['buyer']['firstName'] = $client->name;
			$o['buyer']['lastName'] = $client->lastname ?? $client->name;
			$o['buyer']['language'] = $this->lang();

			// Create payu order
			$res = OpenPayU_Order::create($o);

			if (!$res instanceof OpenPayU_Result || $res->getStatus() != 'SUCCESS') {
				throw new Exception('Invalid payment object or status');
			}

			$payu_uid = $res->getResponse()->orderId ?? null;
			$payu_url = $res->getResponse()->redirectUri ?? null;

			if (empty($payu_uid) || empty($payu_url) || empty($order->uid)) {
				throw new Exception('Invalid payment details');
			}

			Payment::updateOrCreate([
				'id' => $payu_uid,
				'order_uid' => $order->uid
			], [
				'url' => $payu_url,
				'total' => $total,
				'currency' => $this->currency,
				'ip' => $this->ipAddress(),
				'gateway' => 'payu'
			]);

			// Emit event
			PayuPaymentCreated::dispatch($order);

			return $payu_url;
		} catch (Exception $e) {
			PayuPaymentNotCreated::dispatch($order);
			$this->log('PAYU_PAY_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function notify()
	{
		try {
			if (!in_array($this->ipAddress(), $this->allowed_ip)) {
				throw new Exception('Notify invalid ip address');
			}

			// Data
			$data = trim(request()->getContent());

			if (empty($data)) {
				throw new Exception('Notify invalid data');
			}

			// Log to database in sandbox mode
			if (
				$this->env == 'sandbox' ||
				config('payu.logs.notify', false) == true
			) {
				$this->log('PAYU_NOTIFY_CONTENT', $data);
			}

			// Notification
			$notify = OpenPayU_Order::consumeNotification($data);

			if (!$notify instanceof OpenPayU_Result) {
				throw new Exception("Notify invalid object");
			}

			PayuPaymentNotified::dispatch($notify);

			// Confirm Order
			if (!empty($notify->getResponse()->order->extOrderId)) {
				$this->refresh(Order::where('uid', $notify->getResponse()->order->extOrderId)->first());
				return response("Comfirmed", 200);
			}

			// Confirm Refund
			if (!empty($notify->getResponse()->refund) && !empty($notify->getResponse()->extOrderId)) {
				$this->refunds(Order::where('uid', $notify->getResponse()->extOrderId)->first());
				return response("Comfirmed", 200);
			}

			// Invalid notification content
			throw new Exception("Invalid notification content", 422);
		} catch (Exception $e) {
			$this->log('PAYU_NOTIFY_ERR', $e->getMessage());
			throw new Exception("Not confirmed", 422);
		}
	}

	function confirm(Order $order): string
	{
		try {
			$p = Payment::where(['order_uid' => $order->uid])->first();

			if ($p instanceof Payment) {
				if ($p->status != 'WAITING_FOR_CONFIRMATION') {
					throw new Exception('You can not update payment with this status');
				}

				$res = OpenPayU_Order::statusUpdate([
					"orderId" => $p->id,
					"orderStatus" => 'COMPLETED'
				]);

				if ($res->getStatus() == 'SUCCESS') {
					// Refresh shop payment
					// $this->refresh($order);

					// Emit event
					PayuPaymentConfirmed::dispatch($order);

					return 'UPDATED';
				} else {
					throw new Exception('Status update error');
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			$this->log('PAYU_CONFIRM_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function cancel(Order $order): string
	{
		try {
			$p = Payment::where(['order_uid' => $order->uid])->first();

			if ($p instanceof Payment) {
				if ($p->status != 'WAITING_FOR_CONFIRMATION') {
					throw new Exception('You can not update payment with this status');
				}

				$res = OpenPayU_Order::cancel($p->id);

				if ($res->getStatus() == 'SUCCESS') {
					if (!empty($res->getResponse()->orderId)) {
						// Refresh shop payment
						// $this->refresh($order);

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
		} catch (Exception $e) {
			$this->log('PAYU_CANCEL_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function refund(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p instanceof Payment) {
				// Refund full order
				$res = OpenPayU_Refund::create($p->id, __('Refunding'), null);

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
		} catch (Exception $e) {
			$this->log('PAYU_REFUND_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function refunds(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p instanceof Payment) {
				// Get refunds from payu
				$res = OpenPayU_Refunds::retrive($p->id);

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
		} catch (Exception $e) {
			$this->log('PAYU_REFUNDS_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function refresh(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p instanceof Payment) {
				// Get payment from payu
				$res = OpenPayU_Order::retrieve($p->id);

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
		} catch (Exception $e) {
			$this->log('PAYU_REFRESH_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function retrive(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p instanceof Payment) {
				$res = OpenPayU_Order::retrieve($p->id);

				if ($res->getStatus() == 'SUCCESS') {
					if (count($res->getResponse()->orders) == 0) {
						throw new Exception("Empty orders array");
					}

					return $res->getResponse()->orders[0];
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			$this->log('PAYU_RETRIVE_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function transaction(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p instanceof Payment) {
				$res = OpenPayU_Order::retrieveTransaction($p->id);

				if (count($res->getResponse()->transactions) == 0) {
					throw new Exception("Empty transactions array");
				}

				return $res->getResponse()->transactions[0];
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			$this->log('PAYU_TRANSACTION_ERR', $e->getMessage(), $order->id);
			throw new Exception($e->getMessage(), 422);
		}
	}

	function payments($lang = 'pl')
	{
		try {
			$res = OpenPayU_Retrieve::payMethods($lang);

			return $res->getResponse();
		} catch (Exception $e) {
			$this->log('PAYU_PAYMENTS_ERR', $e->getMessage());
			throw new Exception($e->getMessage(), 422);
		}
	}

	function notifyUrl(): string
	{
		// https://your.page/web/payment/notify/{gateway}
		return request()->getSchemeAndHttpHost() . '/web/payment/notify/payu';
	}

	function successUrl(Order $order): string
	{
		// https://your.page/web/payment/success/{order}
		return request()->getSchemeAndHttpHost() . '/web/payment/success/payu/' . $order->id;
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

		return number_format($decimal, 2, '.', '') * 100;
	}

	function log($code, $desc, $oid = 'NONE')
	{
		try {
			if (config('payu.logs.errors', true) == true) {
				PayuLog::create([
					'code' => $code,
					'description' => $desc,
					'oid' => $oid,
					'ip' => $this->ipAddress(),
				]);
			}
		} catch (Exception $e) {
			report($e);
			throw new Exception("PayuLog log error", 422);
		}
	}
}
