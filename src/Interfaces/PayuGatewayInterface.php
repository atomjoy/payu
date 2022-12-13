<?php

namespace Payu\Interfaces;

use Payu\Interfaces\PayuOrderInterface;

interface PayuGatewayInterface
{
	// Gateway name
	const gateway = 'payu';

	// Config keys
	function config();

	// Incoming notifications
	function notify();

	// Get payment details
	function retrive(PayuOrderInterface $order);

	// Get payment transaction details
	function transaction(PayuOrderInterface $order);

	// Create payment url
	function pay(PayuOrderInterface $order): string;

	// Confirm payment
	function confirm(PayuOrderInterface $order): string;

	// Cancel payment
	function cancel(PayuOrderInterface $order): string;

	// Refresh status
	function refresh(PayuOrderInterface $order): string;

	// Refund payment
	function refund(PayuOrderInterface $order): string;

	// Payment refund details
	function refunds(PayuOrderInterface $order);

	// Payment methods
	function payments($lang);

	// Redirect to url
	function successUrl(PayuOrderInterface $order): string;

	// Notification url
	function notifyUrl(): string;

	// Ip address
	function ipAddress(): string;

	// Get app language
	function lang(): string;

	// Payment logo png
	function logo($square): string;

	// Decimal to cents/grosz
	function toCents(float $decimal): int;
}
