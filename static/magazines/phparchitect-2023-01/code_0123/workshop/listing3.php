public function testUpdateReferral()
{
  $user = factory(App\User::class)->create();
  factory(App\Referral::class, 2)->create();
  $referral = Referral::all()->random();

  $this->actingAs($user)
    ->withSession(['foo' => 'bar'])
    ->visit('/referrals/' . $referral->id . '/edit')
    ->see('Edit Referral')
    ->type('Updated Recipient Name', 'recipient_name')
    ->type('up_recipient@email.com', 'recipient_email')
    ->type('Updated Submitter Name', 'submitter_name')
    ->type('up_submitter@email.com', 'submitter_email')
    ->type('Updated Friend', 'patient_relationship')
    ->type('Updated 1234 somewhere street', 'address')
    ->type('Updated 44th floor', 'address2')
    ->type('Updated Some City', 'city')
    ->type('Updated TN', 'state')
    ->type('Updated 90210', 'zip_code')
    ->type('Updated Illness Type', 'illness_type')
    ->type('Updated Doctors Name', 'doctors_name')
    ->type('Updated Treatment Loc', 'facility_name')
    ->type('Updated Gift Card Type', 'giftcard_type')
    ->press('Update Referral')
    ->seeInDatabase('referrals', [
      'id' => $referral->id,
      'recipient_name' => 'Updated Recipient Name',
      'recipient_email' => 'up_recipient@email.com',
      'submitter_name' => 'Updated Submitter Name',
      'submitter_email' => 'up_submitter@email.com',
      'patient_relationship' => 'Updated Friend',
      'address' => 'Updated 1234 somewhere street',
      'address2' => 'Updated 44th floor',
      'city' => 'Updated Some City',
      'state' => 'Updated TN',
      'zip_code' => 'Updated 90210',
      'cancer_type' => 'Updated Illness Type',
      'doctors_name' => 'Updated Doctors Name',
      'facility_name' => 'Updated Treatment Loc',
      'giftcard_type' => 'Updated Gift Card Type',
    ])
    ->dontSee('Whoops');
}