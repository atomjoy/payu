<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clients', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('order_id')->unique()->nullable(true);
			$table->string('name');
			$table->string('lastname')->nullable(true);
			$table->string('country')->nullable(true);
			$table->string('city')->nullable(true);
			$table->string('address')->nullable(true);
			$table->string('floor')->nullable(true);
			$table->string('mobile')->nullable(true);
			$table->string('email')->nullable(true);
			$table->string('comment')->nullable(true);
			$table->string('ip')->nullable(true);
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('clients');
	}
};
