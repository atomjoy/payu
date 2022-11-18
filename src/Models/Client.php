<?php

namespace Payu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\PayuClientFactory;
use App\Models\Order;

class Client extends Model
{
	use HasFactory, SoftDeletes;

	protected $guarded = [];

	protected $dateFormat = 'Y-m-d H:i:s';

	public function order()
	{
		// 'foreign_key', 'owner_key'
		return $this->belongsTo(Order::class, 'order_id', 'id');
	}

	protected static function newFactory()
	{
		return PayuClientFactory::new();
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format($this->dateFormat);
	}
}
