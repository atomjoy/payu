<?php

namespace Payu\Interfaces;

interface PayuOrderInterface
{
	function order_id();
	function order_cost();
	function order_firstname();
	function order_lastname();
	function order_phone();
	function order_email();
}
