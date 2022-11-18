<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Order;

class PayuOrderFactory extends Factory
{
	protected $model = Order::class;

	public function definition()
	{
		return [
			'uid' => $this->faker->uuid(),
			'cost' => rand(10, 369) . '.' . rand(11, 99),
			'payment_method' => 'online',
			'payment_gateway' => 'payu',
		];
	}
}
