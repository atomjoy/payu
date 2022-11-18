<?php

namespace Payu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Database\Factories\PayuOrderFactory;
use App\Models\Client;
use Payu\Models\Payment;

class Order extends Model
{
	use HasFactory, SoftDeletes;

	protected $guarded = [];

	protected $dateFormat = 'Y-m-d H:i:s';

	public function client()
	{
		return $this->hasOne(Client::class, 'order_id', 'id');
	}

	public function payment()
	{
		return $this->hasOne(Payment::class, 'order_uid', 'uid');
	}

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($model) {
			if (empty($model->uid)) {
				$model->uid = (string) Str::uuid();
			}
		});
	}

	protected static function newFactory()
	{
		return PayuOrderFactory::new();
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format($this->dateFormat);
	}
}
