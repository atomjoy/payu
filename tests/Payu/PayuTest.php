<?php

namespace Tests\Payu;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Database\Seeders\PayuDatabaseSeeder;
use App\Models\Order;
use Tests\TestCase;
use Payu\Events\PayuPaymentCreated;
use Payu\Gateways\PayuPaymentGateway;

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

	/** @test */
	public function convert_to_cents()
	{
		$g = new PayuPaymentGateway();

		// Invalid

		$this->expectException(\Exception::class);
		$g->toCents('3.59');

		$this->expectException(\Exception::class);
		$g->toCents(0);

		$this->expectException(\Exception::class);
		$g->toCents(0.00);


		$this->expectException(\Exception::class);
		$g->toCents(.123);


		$this->expectException(\Exception::class);
		$g->toCents(10.123);

		// Good

		$this->assertTrue($g->toCents(1) == 100);
		$this->assertTrue($g->toCents(2) == 200);
		$this->assertTrue($g->toCents(3) == 300);
		$this->assertTrue($g->toCents(4) == 400);
		$this->assertTrue($g->toCents(5) == 500);

		$this->assertTrue($g->toCents(.01) == 1);
		$this->assertTrue($g->toCents(.1) == 10);
		$this->assertTrue($g->toCents(.10) == 10);
		$this->assertTrue($g->toCents(.11) == 11);
		$this->assertTrue($g->toCents(.12) == 12);
		$this->assertTrue($g->toCents(.13) == 13);
		$this->assertTrue($g->toCents(.14) == 14);
		$this->assertTrue($g->toCents(.15) == 15);
		$this->assertTrue($g->toCents(.16) == 16);
		$this->assertTrue($g->toCents(.17) == 17);
		$this->assertTrue($g->toCents(.18) == 18);
		$this->assertTrue($g->toCents(.19) == 19);
		$this->assertTrue($g->toCents(.20) == 20);
		$this->assertTrue($g->toCents(.21) == 21);

		$this->assertTrue($g->toCents(01.10) == 110);
		$this->assertTrue($g->toCents(01.11) == 111);
		$this->assertTrue($g->toCents(01.12) == 112);
		$this->assertTrue($g->toCents(01.13) == 113);
		$this->assertTrue($g->toCents(01.14) == 114);
		$this->assertTrue($g->toCents(01.15) == 115);
		$this->assertTrue($g->toCents(01.16) == 116);
		$this->assertTrue($g->toCents(01.17) == 117);
		$this->assertTrue($g->toCents(01.18) == 118);
		$this->assertTrue($g->toCents(01.19) == 119);
		$this->assertTrue($g->toCents(01.20) == 120);
		$this->assertTrue($g->toCents(01.21) == 121);

		$this->assertTrue($g->toCents(10.11) == 1011);
		$this->assertTrue($g->toCents(10.12) == 1012);
		$this->assertTrue($g->toCents(10.13) == 1013);
		$this->assertTrue($g->toCents(10.14) == 1014);
		$this->assertTrue($g->toCents(10.15) == 1015);
		$this->assertTrue($g->toCents(10.16) == 1016);
		$this->assertTrue($g->toCents(10.17) == 1017);
		$this->assertTrue($g->toCents(10.18) == 1018);
		$this->assertTrue($g->toCents(10.19) == 1019);
		$this->assertTrue($g->toCents(10.20) == 1020);
		$this->assertTrue($g->toCents(10.21) == 1021);

		$this->assertTrue($g->toCents(123.46) == 12346);
		$this->assertTrue($g->toCents(123.56) == 12356);
	}

	/** @test */
	public function notify_success_pages()
	{
		if (config('payu.env', 'sandbox') == 'sandbox') {
			// Create demo orders
			$this->seed(PayuDatabaseSeeder::class);

			$res = $this->postJson('/web/payment/notify/payu', ['status' => 'SUCCESS']);
			$res->assertStatus(422);

			$o = Order::first();
			$this->assertNotEmpty($o->id);

			$res = $this->get('/web/payment/success/payu/' . $o->id);
			$res->assertStatus(200);
		}
	}
}
