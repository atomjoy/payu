<?php

namespace Payu\Http\Payu;

use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Http;
use OpenPayU_Refund;

// OpenPayu get payment refunds list
class OpenPayU_Refunds extends OpenPayU_Refund
{
	public static function retrive($orderId)
	{
		if (empty($orderId)) {
			throw new OpenPayU_Exception('Invalid orderId value for refund');
		}

		try {
			$authType = self::getAuth();
			$pathUrl = OpenPayU_Configuration::getServiceUrl() . 'orders/' . $orderId . '/refunds';
			return self::verifyResponse(OpenPayU_Http::doGet($pathUrl, $authType));
		} catch (OpenPayU_Exception $e) {
			throw new OpenPayU_Exception($e->getMessage(), $e->getCode());
		}
	}
}
