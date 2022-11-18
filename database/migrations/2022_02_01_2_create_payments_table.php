<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payments', function (Blueprint $table) {
			$table->string('id', 191)->primary();
			$table->uuid('order_uid')->unique();
			$table->enum('status', ['NEW', 'PENDING', 'WAITING_FOR_CONFIRMATION', 'CANCELED', 'COMPLETED', 'REJECTED', 'FAILED', 'REFUNDED'])->nullable()->default('NEW');
			$table->enum('status_refund', ['PENDING', 'CANCELED', 'FINALIZED'])->nullable(true);
			$table->string('gateway')->nullable(true);
			$table->unsignedBigInteger('total')->nullable()->default(0);
			$table->string('currency', 3)->nullable()->default('PLN');
			$table->text('url')->nullable(true);
			$table->string('ip')->nullable(true);
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('order_uid')->references('uid')->on('orders')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('payments');
	}
}
