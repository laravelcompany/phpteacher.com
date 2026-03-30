class Order {
    private $customer;

    public function __construct(Customer $customer) {
        $this->customer = $customer;
        $this->customer->setOrder($this);
    }
}

class Customer {
    private $order;

    public function setOrder(Order $order) {
        $this->order = $order;
    }
}

// Usage
$customer = new Customer();
$order = new Order($customer);