public function testCreateReferral()
{
  $user = factory(App\User::class)->create();

  $this->actingAs($user)
    ->withSession(['foo' => 'bar'])
    ->visit('/referrals')
    ->see('Request Gift Card')
    ->type('Recipient Name', 'recipient_name')
    ->type('recipient@email.com', 'recipient_email')
    ->type('Friend', 'patient_relationship')
    ->type('Submitter Name', 'submitter_name')
    ->type('submitter@email.com', 'submitter_email')
    ->type('1234 somewhere street', 'address')
    ->type('44th floor', 'address2')
    ->type('Some City', 'city')
    ->type('TN', 'state')
    ->type('90210', 'zip_code')
    ->type('Illness Type', 'ilness_type')
    ->type('Doctors Name', 'doctors_name')
    ->type('Treatment Location', 'facility_name')
    ->type('Gift Card Type', 'giftcard_type')
    ->attach(public_path().'/logo.jpeg','verification')
    ->press('Submit Request')
    ->seeInDatabase('referrals', [
      'recipient_name' => 'Recipient Name',
      'recipient_email' => 'recipient@email.com',
      'submitter_name' => 'Submitter Name',
      'submitter_email' => 'submitter@email.com',
      'patient_relationship' => 'Friend',
      'address' => '1234 somewhere street',
      'address2' => '44th floor',
      'city' => 'Some City',
      'state' => 'TN',
      'zip_code' => '90210',
      'cancer_type' => 'Illness Type',
      'doctors_name' => 'Doctors Name',
      'facility_name' => 'Treatment Location',
      'giftcard_type' => 'Gift Card Type',
    ])
    ->dontSee('Whoops');
}