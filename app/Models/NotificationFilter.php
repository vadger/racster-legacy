<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationFilter extends Model
{

	protected $fillable = ['user_id', 'name', 'is_active'];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function coaches(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'notification_filter_coach', 'notification_filter_id', 'coach_id');
	}

	public function locations(): BelongsToMany
	{
		return $this->belongsToMany(RacsterAssets::class, 'notification_filter_location', 'notification_filter_id', 'location_id');
	}

	public function trainingTypes(): BelongsToMany
	{
		return $this->belongsToMany(RacsterAssets::class, 'notification_filter_training_type', 'notification_filter_id', 'training_type_id');
	}

}
