<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		$now = now();

		// System user roles (locked roles 1-4, see config/racster.php)
		$roles = [
			['id' => 1, 'title' => 'Admin'],
			['id' => 2, 'title' => 'Manager'],
			['id' => 3, 'title' => 'Coach'],
			['id' => 4, 'title' => 'Client'],
		];

		foreach ($roles as $role){
			DB::table('racster_assets')->updateOrInsert(
				['id' => $role['id']],
				[
					'uid' => 1,
					'type' => 'userrole',
					'title' => $role['title'],
					'created_at' => $now,
					'updated_at' => $now,
				]
			);
		}

		$password = Hash::make('secret');

		// Users. Note: ID-s defined in config('racster.superadmin') (1, 7) are
		// hidden from the /users list, so admin gets ID 2.
		$users = [
			[
				'id' => 2,
				'name' => 'Racster Admin',
				'email' => 'admin@racster.com',
				'first_name' => 'Racster',
				'last_name' => 'Admin',
				'user_roles' => '1',
			],
			[
				'id' => 3,
				'name' => 'Anna Coach',
				'email' => 'coach@racster.com',
				'first_name' => 'Anna',
				'last_name' => 'Coach',
				'user_roles' => '3',
			],
			[
				'id' => 4,
				'name' => 'Kevin Coach',
				'email' => 'coach2@racster.com',
				'first_name' => 'Kevin',
				'last_name' => 'Coach',
				'user_roles' => '3',
			],
			[
				'id' => 5,
				'name' => 'Clara Client',
				'email' => 'client@racster.com',
				'first_name' => 'Clara',
				'last_name' => 'Client',
				'user_roles' => '4',
			],
			[
				'id' => 6,
				'name' => 'Mark Client',
				'email' => 'client2@racster.com',
				'first_name' => 'Mark',
				'last_name' => 'Client',
				'user_roles' => '4',
			],
		];

		foreach ($users as $user){
			DB::table('users')->updateOrInsert(
				['email' => $user['email']],
				[
					'id' => $user['id'],
					'name' => $user['name'],
					'email' => $user['email'],
					'password' => $password,
					'first_name' => $user['first_name'],
					'last_name' => $user['last_name'],
					'mobile_country_code' => '+372',
					'mobile_number' => '5555' . $user['id'],
					'birthday' => '1990-01-01',
					'user_roles' => $user['user_roles'],
					'email_verified_at' => $now,
					'created_at' => $now,
					'updated_at' => $now,
				]
			);
		}
	}
}
