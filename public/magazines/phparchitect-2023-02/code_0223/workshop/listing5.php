Route::get('/', 
    ['as' => 'home',
    'uses' => 'HomeController@index']);
Route::get('/referrals', 
    ['as' => 'referrals.index',
    'uses' => 'ReferralController@index']);
Route::post('/referrals', 
    ['as' => 'referrals.create',
    'uses' => 'ReferralController@create']);