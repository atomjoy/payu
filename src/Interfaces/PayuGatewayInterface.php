<?php

namespace Payu\Interfaces;

use App\Models\Order;

interface PayuGatewayInterface
{
	// Gateway name
	const gateway = 'payu';

	// Config keys
	function config();

	// Incoming notifications
	function notify();

	// Get payment details
	function retrive(Order $order);

	// Get payment transaction details
	function transaction(Order $order);

	// Create payment url
	function pay(Order $order): string;

	// Confirm payment
	function confirm(Order $order): string;

	// Cancel payment
	function cancel(Order $order): string;

	// Refresh status
	function refresh(Order $order): string;

	// Refund payment
	function refund(Order $order): string;

	// Payment refund details
	function refunds(Order $order);

	// Payment methods
	function payments($lang);

	// Redirect to url
	function successUrl(Order $order): string;

	// Notification url
	function notifyUrl(): string;

	// Ip address
	function ipAddress(): string;

	// Get app language
	function lang(): string;

	// Payment logo png
	function logo($square): string;

	// Decimal to cents/grosz
	function toCents($decimal): string;
}
