<?php

namespace Payu\Facades;

use Illuminate\Support\Facades\Facade;

class Payu extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'payu';
	}
}
