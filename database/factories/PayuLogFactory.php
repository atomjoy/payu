<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Payu\Models\PayuLog;

class PayuLogFactory extends Factory
{
	protected $model = PayuLog::class;

	public function definition()
	{
		return [
			'code' => 'err_' . $this->faker->uuid(),
			'description' => $this->faker->sentence(),
			'oid' => '',
			'ip' => request()->ip(),
		];
	}
}
