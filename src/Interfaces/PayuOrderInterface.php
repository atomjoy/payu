<?php

namespace Payu\Interfaces;

interface PayuOrderInterface
{
	function orderId();
	function orderCost();
	function orderFirstname();
	function orderLastname();
	function orderPhone();
	function orderEmail();
}
