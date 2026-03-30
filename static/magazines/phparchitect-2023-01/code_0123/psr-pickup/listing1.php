class TicketPurchasedEvent {
    public function __construct(
        protected array $purchaseData,
        protected array $movieData)
    {
    }

    public function getPurchaseData(): array {
        return $this->purchaseData;
    }

    public function getMovieData(): array {
        return $this->movieData;
    }
}