
class BankAccount {

	private $balance = 0;

	public function deposit($amount) {
		if ($amount > 0) {
			$this->balance += $amount;
		}
	}

	public function getBalance() {
		return $this->balance;
	}
}