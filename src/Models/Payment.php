<?php

namespace Payu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;

class Payment extends Model
{
	use HasFactory, SoftDeletes;

	protected $primaryKey = 'id';

	protected $keyType = 'string';

	public $incrementing = false;

	protected $guarded = [];

	protected $dateFormat = 'Y-m-d H:i:s';

	public function order()
	{
		// 'foreign_key', 'owner_key'
		return $this->belongsTo(Order::class, 'order_id', 'id');
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format($this->dateFormat);
	}
}
