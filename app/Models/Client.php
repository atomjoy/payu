<?php

namespace App\Models;

use Payu\Models\Client as PaymentClient;

class Client extends PaymentClient
{
	protected $guarded = [];
}
