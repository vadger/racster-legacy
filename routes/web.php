<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\CheckRole;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimetableController;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceController;
use App\Http\Controllers\SubscriptionController;

use App\Http\Controllers\NotificationsController;

use App\Http\Controllers\AssetsController;
use App\Http\Controllers\ManageUsersController;

Route::group(['prefix' => LaravelLocalization::setLocale()], function(){

	Route::get('/', function () {

		if (auth()->check()) {
			return redirect(LaravelLocalization::localizeURL('/home'));
		}

		return redirect(LaravelLocalization::localizeURL('/login'));

	});

	Route::get('/privacy', function () { return view('privacy'); });
	Route::get('/terms', function () { return view('terms'); });

	Route::get('/nouser', function (){ return view('nouser'); })->name('nouser'); // IF no user

	/**
	 * Google login webhooks
	 */
	Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
	Route::get('auth/google/callback', [GoogleController::class, 'callback']);

	/**
	 * Profile hooks
	 */
	Route::middleware(['auth', 'verified'])->group(function () {
		Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
		Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
	});

	/**
	 * Dashboard hook only with completed profile
	 */
	Route::middleware(['auth', 'verified', EnsureProfileIsComplete::class])->group(function () {
		//Route::get('/home', [DashboardController::class, 'showUserDashboard'])->name('dashboard');
		Route::get('/home', [TimetableController::class, 'display'])->name('dashboard');
	});

	/**
	 * Routes for users with client and above role
	 */
	Route::middleware(['auth', 'verified', CheckRole::class . ':client'])->group(function () {

		Route::get('/view/{form}', [TimetableController::class, 'changeView']); // Change timetable view
		Route::get('/time/{time}', [TimetableController::class, 'changeTime']); // Change timetable time
		Route::get('/timetable', [TimetableController::class, 'display'])->name('timetable'); // Show timetable
			Route::post('/timetable', [TimetableController::class, 'display']);
			Route::post('/acquireEntryData', [TimetableController::class, 'acquireTimetableEntryInfo']); // AJAX - Acquire timetable entry data
			Route::post('/acquirePrice', [TimetableController::class, 'acquireTimetableEntryPrice']); // AJAX - Acquire timetable entry price
			Route::post('/attendPeriod', [TimetableController::class, 'attendToEntryPeriod']); // AJAX - Attend client to timetable entry period
			Route::post('/changeSetting', [TimetableController::class, 'changeSettings']); // AJAX - Change user calendar settings
			Route::post('/addFilter', [TimetableController::class, 'filterTimetableBy']); // AJAX - Filter timetable by filters
			Route::post('/getPrice', [TimetableController::class, 'getEntryTypePrice']); // AJAX - Get entry type default price

		// Stripe payment hooks
		Route::get('checkout/continue/{payment}', [CheckoutController::class, 'continueCheckout'])->name('checkout.continue');
			Route::post('checkout/oneoff/{product}', [CheckoutController::class, 'oneOff'])->name('checkout.oneoff');
		Route::get('checkout/attend/{date}', [CheckoutController::class, 'attend_entry_date'])->name('checkout.attend.date');
		Route::get('checkout/subscribe/{entry}', [CheckoutController::class, 'subscribe_entry'])->name('checkout.subscribe.entry');
			Route::post('checkout/subscribe/{product}', [CheckoutController::class, 'subscribe'])->name('checkout.subscribe');
		Route::get('checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
		Route::get('checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

		// View user subscriptions
		Route::get('/subscriptions', [SubscriptionController::class, 'listSelf'])->name('subscriptions.list');
		Route::get('/subscription/{subscription}', [SubscriptionController::class, 'showSelf'])->name('subscription.show')->whereNumber('subscription');
			//Route::post('/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancelSelf'])->name('subscription.cancel')->whereNumber('subscription');
			//Route::post('/subscription/{subscription}/cancel-now', [SubscriptionController::class, 'cancelNowSelf'])->name('subscription.cancel_now')->whereNumber('subscription');
			//Route::post('/subscription/{subscription}/resume', [SubscriptionController::class, 'resumeSelf'])->name('subscription.resume')->whereNumber('subscription');
			//Route::post('/subscription/{subscription}/swap', [SubscriptionController::class, 'swapSelf'])->name('subscription.swap')->whereNumber('subscription');
			//Route::post('/subscription/{subscription}/quantity', [SubscriptionController::class, 'updateQuantitySelf'])->name('subscription.quantity')->whereNumber('subscription');
			//Route::post('/subscriptions/{subscription}/remove-discount', [SubscriptionController::class, 'removeDiscountSelf'])->name('subscription.remove_discount');

		// View and manage user credit
		Route::get('/users/view-credit/{uid?}', [ManageUsersController::class, 'viewUserCredit'])->name('users-view-credit');
			Route::post('/users/add-credit/{uid?}', [ManageUsersController::class, 'manageUserCredit'])->name('users-manage-credit');

		// View and manage user notifications
		Route::get('/notifications', [NotificationsController::class, 'manageUserNotifications'])->name('notifications.list');
			Route::post('/notifications/update-filters', [NotificationsController::class, 'updateNotificationFilters'])->name('notifications.update-filters'); // AJAX - Update user notification filters
			Route::post('/notifications/toggle-active', [NotificationsController::class, 'toggleNotificationsActivation'])->name('notifications.toggle-active'); // AJAX - Toggle notifications activation

	});

	/**
	 * Routes for users with coach and above role
	 */
	Route::middleware(['auth', 'verified', CheckRole::class . ':coach'])->group(function () {

	});

	/**
	 * Routes for users with manager and above role
	 */
	Route::middleware(['auth', 'verified', CheckRole::class . ':manager'])->group(function () {

		Route::get('/manage/entry/{eid?}', [TimetableController::class, 'manageEntryData'])->name('manage.entry'); // Manage timetable entry data
			Route::post('/manage/entry/{eid?}', [TimetableController::class, 'updateEntryData']); // Update timetable entry data
			Route::post('/acquireClients', [TimetableController::class, 'acquireClientsList']); // AJAX - Acquire clients list to add to entry
			Route::post('/createDates', [TimetableController::class, 'createRecurringDates']); // AJAX - Create recurring dates array
			Route::post('/acquirePastDates', [TimetableController::class, 'acquireRecurringEntryPastDates']); // AJAX - Acquire timetable recurring entry past dates
			Route::post('/acquireEntrySubscriptions', [TimetableController::class, 'acquireRecurringEntrySubscriptions']); // AJAX - Acquire timetable recurring entry subscriptions
			Route::post('/manage/date-coaches', [TimetableController::class, 'updateDateCoaches']);

		Route::get('/assets', [AssetsController::class, 'showAssetsList'])->name('assets'); // Show assets list
			Route::post('/activeAsset', [AssetsController::class, 'manageActiveAsset']); // AJAX - Update active asset
			Route::post('/manageAsset', [AssetsController::class, 'manageAssetData']); // AJAX - Get asset data
			Route::post('/saveAsset', [AssetsController::class, 'saveAssetData']); // AJAX - Insert / update asset data
			Route::post('/deleteAsset', [AssetsController::class, 'removeAsset']); // AJAX - Remove asset

		// Delete user credit
		Route::get('/users/delete-credit/{uid}/{tid}', [ManageUsersController::class, 'deleteUserCredit'])->name('users-delete-credit');

	});

	/**
	 * Routes for users with admin role
	 */
	Route::middleware(['auth', 'verified', CheckRole::class . ':admin'])->group(function () {

		// Manage stripe products
		Route::resource('products', ProductController::class);
			Route::post('products/{product}/prices', [ProductPriceController::class, 'store'])->name('products.prices.store');
			Route::delete('products/{product}/prices/{price}', [ProductPriceController::class, 'destroy'])->name('products.prices.destroy');

		Route::get('/users/{role?}', [ManageUsersController::class, 'showUsers'])->name('users'); // Users view - Show user role management
			Route::post('/users/{role?}', [ManageUsersController::class, 'showUsers']);
		Route::get('/remove-role/{user}/{role}', [ManageUsersController::class, 'removeUserRole'])->name('removerole'); // Remove role from user
		Route::get('/delete-role/{rid}', [ManageUsersController::class, 'deleteUsersRole'])->name('deleterole'); // Delete role

		// Show and update user info
		Route::get('/users/show-info/{uid}', [ManageUsersController::class, 'showUserData'])->name('users-show-info');
			Route::post('/users/update-info/{uid}', [ManageUsersController::class, 'updateUserData'])->name('users-update-info');

		// View user subscriptions
		Route::get('/users/subscriptions/list', [SubscriptionController::class, 'adminIndex'])->name('subscriptions.admin.index');
		Route::get('/users/subscriptions/{user}', [SubscriptionController::class, 'listForUser'])->name('subscriptions.admin.user_list');
		Route::get('/users/subscription/{subscription}', [SubscriptionController::class, 'showAdmin'])->name('subscription.admin.show')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancelAdmin'])->name('subscription.admin.cancel')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/cancel-now', [SubscriptionController::class, 'cancelNowAdmin'])->name('subscription.admin.cancel_now')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/resume', [SubscriptionController::class, 'resumeAdmin'])->name('subscription.admin.resume')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/swap', [SubscriptionController::class, 'swapAdmin'])->name('subscription.admin.swap')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/quantity', [SubscriptionController::class, 'updateQuantityAdmin'])->name('subscription.admin.quantity')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/pause', [SubscriptionController::class, 'pauseAdmin'])->name('subscription.admin.pause')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/restart', [SubscriptionController::class, 'restartAdmin'])->name('subscription.admin.restart')->whereNumber('subscription');
			Route::post('/users/subscription/{subscription}/trial', [SubscriptionController::class, 'updateTrialAdmin'])->name('subscription.admin.trial')->whereNumber('subscription');
			Route::post('/admin/subscriptions/{subscription}/remove-discount', [SubscriptionController::class, 'removeDiscountAdmin'])->name('subscription.admin.remove_discount')->whereNumber('subscription');

	});

});

require __DIR__.'/auth.php';
