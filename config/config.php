<?php

return [
	// Enable payu payments
	'enable' => true,

	// Load payu routes
	'routes' => true,

	// Load payu db migrations
	'migrations' => true,

	// Enable payu logs
	'logs' => [
		'notify' => false,
		'errors' => true,
	],

	// Payu api credentials

	// Set environment: 'sandbox' or 'secure'
	'env' => 'sandbox',

	// Keys
	'pos_id' => '',
	'pos_md5' => '',

	// Oauth
	'client_id' => '',
	'client_secret' => '',

	// Currency
	'currency' => 'PLN'
];
