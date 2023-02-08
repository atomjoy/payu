<?php

namespace Payu\Listeners;

use Illuminate\Support\Facades\Log;

class PayuPaymentNotification
{
	public function handle($event)
	{
		Log::info("Payu payment event", ['class' => $event]);
	}
}
