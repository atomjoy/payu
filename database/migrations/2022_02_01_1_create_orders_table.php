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
			$table->enum('payment_method', ['money', 'card', 'online'])->nullable()->default('money');
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

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function downTable()
	{
		Schema::enableForeignKeyConstraints();
		Schema::dropIfExists('users');
		Schema::disableForeignKeyConstraints();

		if (Schema::hasTable('users')) {
			// The "users" table exists...
		}

		if (Schema::hasColumn('users', 'phone')) {
			Schema::table('users', function (Blueprint $table) {
				$table->dropColumn('phone');
				$table->dropColumn(['votes', 'avatar', 'location']);
				$table->foreignUuid('user_id');
				$table->string('name', 50)->nullable()->change();
				$table->unique('email', 'unique_email');

				$table->unsignedBigInteger('user_id');
				$table->foreign('user_id')->references('id')->on('users');
				$table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
			});
		}
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		Schema::defaultStringLength(191);
	}
};
