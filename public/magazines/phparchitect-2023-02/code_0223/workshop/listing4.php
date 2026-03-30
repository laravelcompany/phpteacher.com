public function up()
{
    $referrals = Referral::all();

    foreach ($referrals as $referral)
    {
        if (empty($referral->verification))
        {
            $input = [
                'referral_id' => $referral->id,
                'filename' => $referral->verification,
            ];
        }
        else
        {
            $input = [
                'referral_id' => $referral->id,
                'filename' => $referral->verification,
                'hash' => sha1_file($referral->verification),
            ];
        }

        VerificationImage::create($input);
    }

    Schema::table('referrals', function ($table) {
        $table->dropColumn('verification');
    });
}