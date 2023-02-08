<?php

use Illuminate\Support\Facades\Route;
use Payu\Http\Controllers\PayuPaymentController;

// Private routes for admin only (add similar functionality in your admin panel if you need)
if (config('payu.env') == 'sandbox') {
	// Here only for payu sandbox mode
	Route::prefix('/web')->name('web.')->middleware(['web', 'payu'])->group(function () {
		// Create order with payment link
		Route::get('/payment/create', [PayuPaymentController::class, 'create'])
			->name('payu.create');
		// Create payu payment url from order
		Route::get('/payment/url/payu/{order}', [PayuPaymentController::class, 'pay'])
			->name('payu.url');
		// Confirm waiting payu payment (disable auto confirmation in payu panel)
		Route::get('/payment/confirm/payu/{order}', [PayuPaymentController::class, 'confirm'])
			->name('payu.confirm');
		// Cancel waiting payu payment (disable auto confirmation in payu panel)
		Route::get('/payment/cancel/payu/{order}', [PayuPaymentController::class, 'cancel'])
			->name('payu.cancel');
		// Refresh payu payment details
		Route::get('/payment/refresh/payu/{order}', [PayuPaymentController::class, 'refresh'])
			->name('payu.refresh');
		// Get payment details
		Route::get('/payment/retrive/payu/{order}', [PayuPaymentController::class, 'retrive'])
			->name('payu.retrive');
		// Get payment transaction details
		Route::get('/payment/transaction/payu/{order}', [PayuPaymentController::class, 'transaction'])
			->name('payu.transaction');
		// Refund payment
		Route::get('/payment/refund/payu/{order}', [PayuPaymentController::class, 'refund'])
			->name('payu.refund');
		// Get refunds details
		Route::get('/payment/refunds/payu/{order}', [PayuPaymentController::class, 'refunds'])
			->name('payu.refunds');
		// Get allowed payment types list
		Route::get('/payment/payments/payu/{lang}', [PayuPaymentController::class, 'payments'])
			->name('payu.payments');
	});
}
