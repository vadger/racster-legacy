<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RacsterEntry extends Model
{

	protected $table = 'racster_entries';
	protected $guarded = [];

	public function dates()
	{
		return $this->hasMany(RacsterEntryDate::class, 'entry_id');
	}

}
