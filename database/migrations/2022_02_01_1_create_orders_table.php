<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('orders', function (Blueprint $table) {
			$table->id();
			$table->uuid('uid')->unique()->index()->nullable(true);
			$table->decimal('cost', 15, 2)->default(0.00);
			$table->enum('payment_method', ['money', 'card', 'online', 'pickup'])->nullable()->default('money');
			$table->string('payment_gateway')->nullable(true);
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('orders');
	}	
};
