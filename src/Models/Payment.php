<?php

namespace Payu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Payu\Models\Traits\Uuids;
use App\Models\Order;

class Payment extends Model
{
	use HasFactory, SoftDeletes, Uuids;

	protected $primaryKey = 'id';

	protected $guarded = [];

	protected $dateFormat = 'Y-m-d H:i:s';

	protected $casts = [
		'created_at'  => 'date:Y-m-d H:i:s',
		'updated_at'  => 'date:Y-m-d H:i:s',
		'deleted_at'  => 'date:Y-m-d H:i:s',
	];

	public function order()
	{
		// 'foreign_key', 'owner_key'
		return $this->belongsTo(Order::class, 'order_uid', 'uid');
	}
}
