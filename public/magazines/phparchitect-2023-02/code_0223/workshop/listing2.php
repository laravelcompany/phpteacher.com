# old factory
$factory->define(App\Gift::class, function (Faker $faker) {
    $referrals = \App\Referral::all();

    return [
        'description' => $faker->sentence,
        'amount' => $faker->randomFloat(2, 0, 10000),
        'referral_id' => $referrals->random()->id,
    ];
});

# new factory
public function definition()
{
    $referrals = \App\Models\Referral::all();

    return [
        'description' => fake()->sentence,
        'amount' => fake()->randomFloat(2, 0, 10000),
        'referral_id' => $referrals->random()->id,
    ];
}