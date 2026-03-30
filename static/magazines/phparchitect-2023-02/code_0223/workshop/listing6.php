Route::get('/',
    [HomeController::class, 'index'])
    ->name('home');
Route::get('/referrals', 
    [ReferralController::class, 'index'])
    ->name('referrals.index');
Route::post('/referrals', 
    [ReferralController::class, 'create'])
    ->name('referrals.create');