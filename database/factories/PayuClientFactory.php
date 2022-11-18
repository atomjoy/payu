<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Client;

class PayuClientFactory extends Factory
{
	protected $model = Client::class;

	public function definition()
	{
		// $email = $this->faker->unique()->safeEmail();
		// $email = uniqid().'@'.request()->getHttpHost();

		return [
			'name' => $this->faker->name(),
			'lastname' => $this->faker->lastName(),
			'country' => $this->faker->country(),
			'city' => $this->faker->city(),
			'address' => $this->faker->streetAddress(),
			'email' => uniqid() . '@localhost',
			'mobile' => $this->faker->numerify('+48#########'),
			'comment' => $this->faker->sentence(),
			'ip' => '127.0.0.1',
		];
	}
}
