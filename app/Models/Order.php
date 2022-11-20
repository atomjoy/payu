<?php

namespace App\Models;

use Payu\Models\Order as PaymentOrder;

class Order extends PaymentOrder
{
	protected $guarded = [];
}
