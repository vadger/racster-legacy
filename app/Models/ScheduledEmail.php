<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmail extends Model
{

	protected $fillable = ['user_id','to_email','subject','body','heading','cta_name','cta_link','stripe_invid','send_at','status'];
	protected $casts = [
		'send_at' => 'datetime',
		'sent_at' => 'datetime',
	];

}
