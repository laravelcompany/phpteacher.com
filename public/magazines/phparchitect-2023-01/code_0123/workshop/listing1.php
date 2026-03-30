use Auth\LoginController;

Route::get('login', 'LoginController@showLoginForm')
      ->name('login');

Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout')
      ->name('logout');

Route::get('/', [
        'as' => 'home',
        'uses' => 'HomeController@index'
]);
Route::post('/referrals', [
    'as' => 'referrals.create',
    'uses' => 'ReferralController@create'
]);
Route::group(['middleware' => ['auth']], function () {
    Route::get('/referrals', [
        'as' => 'referrals.index',
        'uses' => 'ReferralController@index'
    ]);
    Route::get('/referrals/fulfilled', [
        'as' => 'referrals.index.fulfilled',
        'uses' => 'AdminController@getFulfilled'
    ]);
    Route::get('/referrals/unfulfilled', [
        'as' => 'referrals.index.unfulfilled',
        'uses' => 'AdminController@getUnfulfilled'
    ]);
    Route::get('/referrals/archived', [
        'as' => 'referrals.index.archived',
        'uses' => 'AdminController@getArchived'
        ]);
    Route::get('/admin', [
        'as' => 'admin.index',
        'uses' => 'AdminController@index'
    ]);
...