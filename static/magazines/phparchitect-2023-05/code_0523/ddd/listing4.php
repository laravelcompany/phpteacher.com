class CheckoutSessionNational
{
  private RunStripeCheckout $runStripeCheckout;
  private NationalLogSessionBegan
                        $nationalLogSessionBegan;
  public function __construct(
    RunStripeCheckout $runStripeCheckout,
	NationalLogSessionBegan $nationalLogSessionBegan
  ) {
    $this->runStripeCheckout = $runStripeCheckout;
    $this->nationalLogSessionBegan =
                            $nationalLogSessionBegan;
  }
}