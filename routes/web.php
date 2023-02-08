<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use Payu\Http\Controllers\PayuPaymentController;
use Payu\Http\Controllers\PayuPaymentPageController;

// Public routes for Payu notification
Route::prefix('/web')->name('web.')->middleware(['web', 'payu'])->group(function () {

	// Payu order success and error page
	Route::get('/payment/success/payu/{order}', [PayuPaymentPageController::class, 'success'])
		->withoutMiddleware([VerifyCsrfToken::class])
		->name('payu.success');

	// Payu notifications url
	Route::post('/payment/notify/payu', [PayuPaymentController::class, 'notify'])
		->withoutMiddleware([VerifyCsrfToken::class])
		->name('payu.notify');
});

// Private routes
require('sandbox.php');
