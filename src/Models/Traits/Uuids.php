<?php

namespace Payu\Models\Traits;

use Illuminate\Support\Str;

/**
 * Add in model class
 *
 * protected $primaryKey = 'uid';
 */
trait Uuids
{
	protected static function boot()
	{
		parent::boot();

		static::creating(function ($model) {
			if (empty($model->{$model->getKeyName()})) {
				$model->{$model->getKeyName()} = (string) Str::uuid();
			}
		});
	}

	public function getIncrementing(): bool
	{
		return false;
	}

	public function getKeyType(): string
	{
		return 'string';
	}
}
