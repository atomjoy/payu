<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Payu\Models\PayuLog;

class PayuLogSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		PayuLog::factory()->count(5)->create();
	}
}
