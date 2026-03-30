public function initialize(): void
{
  $this->captureFlow = new CaptureFlow();
  $this->captureMode = new CaptureMode();
  $this->captureApiKey = new CaptureApiKey();
  $runStripeCheckout = new RunStripeCheckout();
  $this->restorePost = new RestorePost();
  $this->restoreStripeSession =
        new RestoreStripeSession($this->captureApiKey);
  $this->retrieveSeasonStripeSession =
        new RetrieveSeasonStripeSession();
  $this->retrieveLeagueStripeSession =
        new RetrieveLeagueStripeSession();
  $this->retrieveMshslStripeSession =
        new RetrieveMshslStripeSession();
  $this->retrieveNationalStripeSession =
        new RetrieveNationalStripeSession();
  $this->confirmSeason = new ConfirmSeason();
  $this->confirmLeague = new ConfirmLeague();
  $this->confirmMshsl = new ConfirmMshsl();
  $this->confirmNational = new ConfirmNational();
  $this->checkoutSessionSeason =
    new CheckoutSessionSeason(
        new CollectSeasonTeamInfo(),
        new CollectSeasonRosterIds(),
        new CollectSeasonLineItems(),
        $runStripeCheckout
    );
  $this->checkoutSessionLeague =
    new CheckoutSessionLeague(
        new CollectLeagueTeamInfo(),
        new CollectLeagueRosterIds(),
        new CollectLeagueLineItems(),
        $runStripeCheckout
    );
  $this->checkoutSessionMshsl =
    new CheckoutSessionMshsl(
        new CollectMshslRosterId(),
        $runStripeCheckout,
        new MshslLogSessionBegan()
    );
  $this->checkoutSessionNational =
    new CheckoutSessionNational(
        $runStripeCheckout,
        new NationalLogSessionBegan()
    );
}