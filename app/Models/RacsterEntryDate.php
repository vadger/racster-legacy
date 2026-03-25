<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RacsterEntryDate extends Model
{

	protected $table = 'racster_entry_dates';
	protected $guarded = [];

	public function entry()
	{
		return $this->belongsTo(RacsterEntry::class, 'entry_id');
	}

}
