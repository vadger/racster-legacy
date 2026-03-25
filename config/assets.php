<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System assets variables
    |--------------------------------------------------------------------------
    */

	// Active assets list [type - parent, title, descr, extra]
	'active' => [
		'userrole' => [
			'title' => '',
		],
		'entry-type' => [
			'title' => '',
			'descr' => '',
			'extra' => '',
		],
		'entry-length' => [
			'parent' => 'entry-type',
			'title' => '',
			'descr' => '',
			'extra' => '',
		],
		'entry-price' => [
			'parent' => 'entry-type',
			'title' => '',
			'extra' => '',
		],
		'entry-minperiod' => [
			'parent' => 'entry-type',
			'title' => '',
			'extra' => '',
		],
		'entry-mincancel' => [
			'parent' => 'entry-type',
			'title' => '',
			'extra' => '',
		],
		'entry-location' => [
			'title' => '',
			'descr' => '',
			'extra' => '',
		],
		'client-level' => [
			'title' => '',
			'descr' => '',
			'extra' => '',
		],
	],

	// Hidden assets
	'hidden' => [
		'userrole',
	],

	// Translated asset types
	'translated_assets' => [
		'entry-type',
		'entry-length',
		'entry-location',
		'client-level',
	],

];
