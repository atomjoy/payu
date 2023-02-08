<?php

namespace Payu\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Payu\Events\PayuPaymentCanceled;
use Payu\Events\PayuPaymentConfirmed;
use Payu\Events\PayuPaymentCreated;
use Payu\Events\PayuPaymentNotified;
use Payu\Events\PayuPaymentRefunded;
use Payu\Listeners\PayuPaymentNotification;

class PayuEventServiceProvider extends ServiceProvider
{
	protected $listen = [
		PayuPaymentCreated::class => [
			PayuPaymentNotification::class,
		],
		PayuPaymentNotified::class => [
			PayuPaymentNotification::class,
		],
		PayuPaymentRefunded::class => [
			PayuPaymentNotification::class,
		],
		PayuPaymentCanceled::class => [
			PayuPaymentNotification::class,
		],
		PayuPaymentConfirmed::class => [
			PayuPaymentNotification::class,
		]
	];

	/**
	 * Register any events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();
	}
}
