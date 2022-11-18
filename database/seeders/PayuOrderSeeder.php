<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Client;

class PayuOrderSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		// Order::factory()->count(5)->create();

		Order::factory()->count(5)->create()->each(function ($order) {
			$client = Client::factory()->create();
			$order->client()->save($client);
		});

		// Order::factory()->count(25)->create()->each(function ($order) {
		// 	// Seed the relation with one address
		// 	$client = Client::factory()->make();
		// 	$order->client()->save($client);

		// 	// Seed the relation with 5 purchases
		// 	$purchases = Product::factory()->count(5)->make();
		// 	$order->products()->saveMany($purchases);
		// });
	}
}
