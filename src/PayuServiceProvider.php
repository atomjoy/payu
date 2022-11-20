<?php

namespace Payu;

use Illuminate\Support\ServiceProvider;
use Payu\Gateways\PayuPaymentGateway;
use Payu\Http\Middleware\PayuMiddleware;
use Payu\Payu;

class PayuServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['router']->aliasMiddleware('payu', PayuMiddleware::class);

		$this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'payu');

		// Facade
		$this->app->bind('payu', function ($app) {
			return new Payu();
		});

		// Enable payu gateway
		if (config('payu.enable') == true) {
			$this->app->bind(PayuPaymentGateway::class, function ($app) {
				return new PayuPaymentGateway();
			});
		}

		// Event service
		// $this->app->register(PayuEventServiceProvider::class);
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'payu');
		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'payu');

		if (config('payu.migrations') == true) {
			$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		}

		if (config('payu.routes') == true) {
			$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
		}

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__ . '/../config/config.php' => config_path('payu.php'),
				__DIR__ . '/../public' => public_path('vendor/payu'),
			], 'payu-config');

			$this->publishes([
				__DIR__ . '/../resources/views' => resource_path('views/vendor/payu'),
				__DIR__ . '/../resources/lang' => $this->app->langPath('vendor/payu'),
			], 'payu-pages');

			$this->publishes([
				__DIR__ . '/../database/migrations' => database_path('/migrations'),
			], 'payu-migrations');

			$this->publishes([
				__DIR__ . '/../public' => public_path('vendor/payu'),
			], 'payu-public');

			$this->publishes([
				__DIR__ . '/../tests/Payu' => base_path('tests/Payu')
			], 'payu-tests');

			$this->publishes([
				__DIR__ . '/../app/Models' => base_path('app/Models')
			], 'payu-models');
		}
	}
}
