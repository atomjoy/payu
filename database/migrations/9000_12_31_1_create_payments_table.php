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
			$table->string('id')->primary();
			$table->string('payu_id')->nullable(true);
			$table->enum('status', ['NEW', 'PENDING', 'WAITING_FOR_CONFIRMATION', 'CANCELED', 'COMPLETED', 'REJECTED', 'FAILED', 'REFUNDED'])->nullable(true)->default('NEW');
			$table->enum('status_refund', ['PENDING', 'CANCELED', 'FINALIZED'])->nullable(true);
			$table->string('gateway')->nullable()->default('payu');
			$table->unsignedBigInteger('total')->unsigned()->nullable()->default(0); // Cost must be integer
			$table->decimal('cost', 20, 2, true)->nullable()->default(0.00);
			$table->string('currency', 3)->nullable()->default('PLN');
			$table->text('url')->nullable(true);
			$table->string('ip')->nullable(true);
			$table->timestamps();
			$table->softDeletes();

			$table->unsignedBigInteger('order_id')->index();
			$table->foreign('order_id')->references('id')->on('orders')->onUpdate('cascade')->onDelete('cascade');
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
