<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Racster system main variables
    |--------------------------------------------------------------------------
    */

	'superadmin'	=> [1, 7], // Define super admins user ID-s
	'lockroles'		=> [1, 2, 3, 4], // Define locked roles ID-s
	'def_role'		=> 4, // Define default role ID

	// Allowed and active system user roles and who can see their content
    'allowed' => [
		'client'		=> [4, 3, 2, 1],
		'coach'			=> [3, 2, 1],
		'manager'		=> [2, 1],
		'admin'			=> [1],
    ],

	// Define coaches ID
	'coaches_role' => 3,

	// Define maximum size of profile image
	'max_avatar_mb' => 2,

	// Define additional client "levels"
	'client-levels' => [
		19 => [85],
		45 => [85],
		47 => [85],
	],

	// Define timetable start/end time and step for minutes
	'timetable-range' => [
		'start' => 00,
		'end'	=> 23,
		'step'	=> 30,
		'table'	=> 60,
	],

	// Define timetable week view starting time
	'week-start-scroll' => '0700',

	// Define default price placeholder
	'default-price' => 40,

	// Define minimum attendance period in minutes
	'attending-min-period' => (24*60),

	// Define minimum cancel time in minutes
	'cancelling-min-period' => (24*60),

	// Define maximum limit of days for attending and types wiuthout limit
	'prebooking-limit' => 14, // day count from end of week
	'unlimited-prebooking-limit' => [10, 11], // training types without limit

	// Define recurring weekdays
	'recurring-weekdays' => [
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday'
	],

	/* Define recurring dates specifications */
	'recurring-specs' => [
		'weekly',
		'over-week',
	],

	// Define currency info
	'main-currency' => 'EUR',
	'transaction-token' => '€',

	// Define max client count for private training
	'max-private-clients' => 4,

	// Define notifications future days count
	'notification-days-count' => 14,

	// Define failed subscription notice sending day steps
	'failed-subscription-steps' => [0, 1, 2], // Levipro - in case of one month > [0, 5, 10, 20];

	// Define failed subscription last step to be processed
	'failed-subscription-laststep' => 2, // Levipro - in case of one month > 30;

	// Define failed subscription maximum notice days to be processed
	'failed-subscription-maxday' => 3, // Levipro - in case of one month > 27;

	// Define admin e-mail for cancelled failed subscriptions
	'failed-subscription-admin-email' => 'info@föönikstennis.ee',

	// Define level video links
	'level-videos' => [
		5 => '',
		18 => 'https://drive.google.com/file/d/1AiWiauxG3SLutrnWZM6-uduWybS4gbYN/view',
		19 => 'https://drive.google.com/file/d/1KapLgC9CtpalHCiaNcPR-FntqOKAb53m/view',
		45 => 'https://drive.google.com/file/d/10BPTp-Z3_ThqLzYS3Q32h5oGwBzoFQ0g/view',
		47 => 'https://drive.google.com/file/d/1VODnyYFRLHFXUutqtvLvnnsKZ0koieQS/view',
	],

];
