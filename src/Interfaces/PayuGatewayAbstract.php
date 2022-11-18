<?php

namespace Payu\Interfaces;

abstract class PayuGatewayAbstract
{
	protected $env;
	protected $pos_id;
	protected $pos_md5;
	protected $client_id;
	protected $client_secret;
	protected $currency;
}
