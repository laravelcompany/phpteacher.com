# old DatabaseSeeder
public function run()
{
    App\User::create([
        'name' => 'admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('admin'),
    ]);

    factory(App\User::class, 5)->create();
    factory(App\Referral::class, 15)->create();
    factory(App\Gift::class, 35)->create();
}

# new DatabaseSeeder
public function run()
{
    \App\Models\User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('admin'),
    ]);

     \App\Models\User::factory(5)->create();
     \App\Models\Referral::factory(15)->create();
     \App\Models\Gift::factory(35)->create();
}