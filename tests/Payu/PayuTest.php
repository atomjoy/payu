<?php

namespace Tests\Payu;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Database\Seeders\PayuDatabaseSeeder;
use App\Models\Order;
use Tests\TestCase;
use Payu\Events\PayuPaymentCreated;

/**
 * php artisan --env=testing migrate:fresh --seed
 * php artisan --env=testing db:seed --class="\Database\Seeders\PayuDatabaseSeeder"
 *
 * File: phpunit.xml
 * <testsuite name="Payu">
 *	<directory suffix="Test.php">.vendor/atomjoy/payu/tests/Payu</directory>
 * </testsuite>
 *
 * php artisan vendor:publish --tag=payu-tests
 * php artisan test --testsuite=Payu --stop-on-failure
 */
class PayuTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function sandbox_config()
	{
		if (config('payu.env') == 'sandbox') {
			$this->assertNotEmpty(config('payu.env'));
			$this->assertNotEmpty(config('payu.pos_id'));
			$this->assertNotEmpty(config('payu.pos_md5'));

			if (!empty(config('payu.client_id'))) {
				$this->assertNotEmpty(config('payu.client_id'));
				$this->assertNotEmpty(config('payu.client_secret'));
			}
		}

		$this->assertTrue(true);
	}

	/** @test */
	public function sandbox_payment_url()
	{
		if (config('payu.env') == 'sandbox') {
			// Create demo orders
			$this->seed(PayuDatabaseSeeder::class);

			// Get first order
			// $o = Order::latest()->first();
			$o = Order::first();
			$o->delete();
			$o = Order::first();
			$this->assertNotEmpty($o->uid);

			if (!empty($o->client->email)) {
				$this->assertTrue(true);
			}

			// Event
			Event::fake();

			// Create payment url
			$res = $this->get('/web/payment/url/payu/' . $o->id);
			$res->assertStatus(200);
			$this->assertNotEmpty($res['url']);

			// Test event
			Event::assertDispatched(PayuPaymentCreated::class, function ($event) use ($o) {
				return $event->order->uid === $o->uid;
			});
		}

		$this->assertTrue(true);
	}
}
