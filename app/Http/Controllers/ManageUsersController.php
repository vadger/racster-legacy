<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Validator;
use Storage;
use Session;
use Lang;
use Response;
use LaravelLocalization;

use \App\Models\UserTransactions;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;

class ManageUsersController extends Controller
{

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {

		$this->viewtime = time(); // Define the time of the view
		$this->perpage = 15; // Define users count on one page

		$this->credit_rules = [ // Define credit validation
			'amount'	=> 'required|integer|min:1',
			'comment'	=> 'max:1000',
		];

		$this->user_data_rules = [ // Define user data validation
			'user_level'		=> 'required|integer',
			'discount_amount'	=> 'required|integer|between:-1,100',
			'user_desc'			=> 'max:1000',
		];

		$this->coach_data_rules = [ // Define coach data validation
			'user_level'		=> 'integer|nullable',
			'discount_amount'	=> 'integer|between:-1,100',
			'user_desc'			=> 'max:1000',
		];

    }

   /**
     * Change filters
     *
     * @return Response
     */
	public function filterUsers($filter = null)
	{

		if (Auth::user()->hasRole('admin')){

			if (!empty($filter)){
				Session::put('filterUsers', $filter);
			}else{
				Session::forget('filterUsers');
			}

			return Redirect::to('/users');

		}else{ return Redirect::to('/home')->with('notice', trans('racster.no-rights-for-op')); }

	}

	/**
	* View user credit
	*/
	public function viewUserCredit($uid = null)
	{

		if (Auth::user()->hasRole('client')) {

			// Get user data
			$user = DB::table('users')
				->where('id', ((!Auth::user()->hasRole('manager') or empty($uid)) ? Auth::user()->id : $uid))
				->whereNull('deleted_at')
				->first();

			if (!empty($user->id)){

				// Get user transactions
				$transactions = UserTransactions::paginatedList($user->id, $this->perpage);

				if (!empty($transactions)){

					// Get user transactions balance
					$balance = UserTransactions::getBalance($user->id);

				}

			}

			return view('usersManageUserCredit')
				->with([
					'user' => ((!empty($uid) and !empty($user)) ? $user : ''),
					'transactions' => (!empty($transactions) ? $transactions : []),
					'balance' => (!empty($balance) ? $balance : 0),
				]);

		}else{ return Redirect::to('/home')->with('notice', trans('racster.no-rights-for-op')); }

	}

	/**
	* Manage user credit
	*/
	public function manageUserCredit(Request $request, $uid = null)
	{

		if (Auth::user()->hasRole('manager')) {

			$validator = Validator::make($request->all(), $this->credit_rules); // Validate input rules

			if ($validator->fails()) { // If main validation fails

				return Redirect::to(LaravelLocalization::localizeUrl('/users/view-credit'.(!empty($uid) ? '/'.$uid : '')))
							->with('notice', trans('racster.all-fields-are-required'))
							->withInput($request->all())
							->withErrors($validator);

			}

			// Add new credit with transaction for user
			UserTransactions::createTransaction('added', [
				'amount'	=> $request->input('amount'),
				'comment'	=> $request->input('comment'),
				'date_id'	=> NULL,
				'user_id'	=> (!Auth::user()->hasRole('manager') ? Auth::user()->id : $uid),
			]);

			return Redirect::to(LaravelLocalization::localizeUrl('/users/view-credit'.(!empty($uid) ? '/'.$uid : '')))
				->with('message', trans('racster.credit-added-successfully'));

		}

	}

	/**
	* Delete user credit
	*/
	public function deleteUserCredit($uid, $tid)
	{

		if (Auth::user()->hasRole('manager')) {

			// Get user transaction to be deleted
			$transaction = UserTransactions::getUserTransaction($tid, 'added', $uid);

			if (!empty($transaction)){

				// Delete user transaction
				$transaction->delete();

				return Redirect::to(LaravelLocalization::localizeUrl('/users/view-credit/'.$uid))
					->with('message', trans('racster.user-credit-delete-successfully'));

			}

		}else{ return Redirect::to('/home')->with('notice', trans('racster.no-rights-for-op')); }

	}

	/**
	* Show user data
	*/
	public function showUserData($uid)
	{

		if (Auth::user()->hasRole('admin')) {

			// Get client levels that are grouping other levels
			$groupLevels = array_values(array_unique(array_merge(...config('racster.client-levels', []))));

			// Get the client level assets
			$client_levels = DB::table('racster_assets')
				->where('type', 'client-level')
				->whereNotIn('id', $groupLevels)
				->whereNull('deleted_at')
				->orderByRaw("CAST(extra AS UNSIGNED)")
				->orderBy('title', 'asc')
				->get();

			$user = DB::table('users')
				->where('id', $uid)
				->whereNull('deleted_at')
				->first();

			if (!empty($user->id)){

				return view('usersShowUserData')
					->with([
						'levels' => (!empty($client_levels) ? $client_levels : ''),
						'user' => $user,
					]);

			}

		}

		return Redirect::to('/home')->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	* Update user data
	*/
	public function updateUserData(Request $request, $uid)
	{

		if (Auth::user()->hasRole('admin')) {

			$validator = Validator::make($request->all(), ($request->has('user_desc') ? $this->coach_data_rules : $this->user_data_rules)); // Validate input rules

			if ($validator->fails()) { // If main validation fails

				return Redirect::to(LaravelLocalization::localizeUrl('/users/show-info/'.$uid))
							->with('notice', trans('racster.all-fields-are-required'))
							->withInput($request->all())
							->withErrors($validator);

			}

			// Define user data 
			$user_data = [
				'updated_at'	=> \Carbon\Carbon::now(),
			];

			// If coach description field is set then update the data
			foreach($request->all() as $field => $value){
				if ($field != '_token'){
					$user_data[$field] = $value;
				}
			}

			// Update user data in database
			DB::table('users')
				->where('id', $uid)
				->whereNull('deleted_at')
				->update($user_data);

			return Redirect::to(LaravelLocalization::localizeUrl('/users/show-info/'.$uid))
				->with('message', trans('racster.userdata-changed-successfully'));

		}else{ return Redirect::to('/home')->with('notice', trans('racster.no-rights-for-op')); }

	}

    /**
     * Manage user roles
     */
	public function showUsers(Request $request, $role = null)
	{

		if (Auth::user()->hasRole('admin')){

			if ($request->isMethod('post')){

				// Add new / update existing role
				if (!empty($request->input('user_role')) and !empty(config('racster.superadmin')) and in_array(Auth::user()->id, config('racster.superadmin'))){

					if (empty($role)){

						DB::table('racster_assets')->insert([
							'uid'			=> Auth::user()->id,
							'type'			=> 'userrole',
							'title'			=> strtolower($request->input('user_role')),
							'descr'			=> NULL,
							'extra'			=> NULL,
							'parent'		=> NULL,
							'created_at'	=> \Carbon\Carbon::now(),
							'updated_at'	=> \Carbon\Carbon::now()
						]);

						return Redirect::to(LaravelLocalization::localizeUrl('/users'))
							->with('message', trans('racster.new-role-successfully-added'));

					}else{

						DB::table('racster_assets')
							->where('id', $role)
							->update([
								'title'			=> strtolower($request->input('user_role')),
								'updated_at'	=> \Carbon\Carbon::now(),
							]);

						return Redirect::to(LaravelLocalization::localizeUrl('/users'))
							->with('message', trans('racster.role-successfully-updated'));

					}

				}

				// Change roles of users
				if (!empty($request->input('role_type')) and $request->input('roletouser') == 'Yes' and !empty($request->input('roleinfo'))){

					// Get the roles of users
					$active_roles = DB::table('users')
						->whereIn('id', $request->input('roleinfo'))
						->whereNull('deleted_at')
						->pluck('user_roles', 'id')
						->toArray();

					foreach ($request->input('roleinfo') as $newrole){
						if ((empty($active_roles[$newrole]) or !in_array($request->input('role_type'), explode('|', $active_roles[$newrole]))) and Auth::user()->id != $newrole){
							DB::table('users')
								->where('id', $newrole)
								->update([
									'user_roles'	=> (!empty($active_roles[$newrole]) ? $active_roles[$newrole].'|' : '').$request->input('role_type'),
									'updated_at'	=> \Carbon\Carbon::now(),
								]);
						}
					}

					return Redirect::to(LaravelLocalization::localizeUrl('/users'))
						->with('message', trans('racster.selected-users-roles-successfully-updated'));

				}

				// Remove users
				if ($request->input('removeuser') == 'Yes' and !empty($request->input('roleinfo'))){

					DB::table('users')
						->whereIn('id', $request->input('roleinfo'))
						->whereNotIn('id', [Auth::user()->id])
						->update([
							'deleted_at' => \Carbon\Carbon::now(),
						]);

					return Redirect::to(LaravelLocalization::localizeUrl('/users'))
						->with('message', 'User deleted!');

				}

			}

			// Get the roles of users
			$role_rows = DB::table('racster_assets')
				->where('type', 'userrole')
				->whereNull('deleted_at')
				->orderBy('title', 'asc')
				->pluck('title', 'id')
				->toArray();

			// Find all users
			$users_query = DB::table('users')
				->select('id', 'name', 'email', 'user_roles', 'first_name', 'last_name', 'mobile_country_code', 'mobile_number');

				// Add main contitions
				$users_query
					->whereNotIn('id', config('racster.superadmin'))
					->whereNull('deleted_at');

				// Add filtering
				if (Session::has('filterUsers') and !empty(Session::get('filterUsers'))){
					$users_query
						->whereRaw("find_in_set(".Session::get('filterUsers').", replace(users.user_roles, '|', ',')) > 0");
				}

				// Add ordering
				$users_query
					->orderBy('first_name', 'asc')
					->orderBy('name', 'asc');

				// Get users list
				$users = $users_query->paginate($this->perpage);

			return view('users', [
				'urows' => (!empty($users) ? $users : ''),
				'roles' => (!empty($role_rows) ? $role_rows : ''),
				'cngrole' => (!empty($role) ? $role : '')
			]);

		}else{

			return Redirect::to(LaravelLocalization::localizeUrl('/home'))
				->with('notice', trans('racster.no-rights-for-op'));

		}

	}

    /**
     * Remove role from user
     */
	public function removeUserRole(Request $request, $user, $role)
	{

		if (Auth::user()->hasRole('admin') and !empty($role) and Auth::user()->id != $user){

			$roles = [];

			// Get the roles of user
			$active_roles = DB::table('users')
				->where('id', $user)
				->whereNull('deleted_at')
				->pluck('user_roles', 'id')
				->toArray();

			foreach (explode('|', $active_roles[$user]) as $active_role){
				if ($active_role != $role){
					$roles[] = $active_role;
				}
			}

			DB::table('users')->where('id', $user)->update([
				'user_roles'	=> (empty($roles) ? '' : implode('|', $roles)),
				'updated_at'	=> \Carbon\Carbon::now(),
			]);

			return Redirect::to(LaravelLocalization::localizeUrl('/users'))
				->with('message', trans('racster.user-role-successfully-removed'));

		}else{

			return Redirect::to(LaravelLocalization::localizeUrl('/home'))
				->with('notice', trans('racster.no-rights-for-op'));

		}

	}

    /**
     * Delete uesrs role
     */
    public function deleteUsersRole($rid)
    {

		if (Auth::user()->hasRole('admin')){

			DB::table('racster_assets')
				->where('id', $rid)
				->whereNull('deleted_at')
				->update([
					'deleted_at' => \Carbon\Carbon::now(),
				]);

			return Redirect::to(LaravelLocalization::localizeUrl('/users'))
				->with('message', trans('racster.users-role-successfully-deleted'));

		}

		return Redirect::to('/home')->with('message', trans('register.no-rights-for-op'))->with('msgcls', 'danger');

    }

}
