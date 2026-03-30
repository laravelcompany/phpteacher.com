<?php
class Product {
    public function __construct(
        public string $name,
        public string $price,
        public string $category
    ) { }
}

class ShoppingCart {
    public $products = [];

    public function addProduct($name, $price, $cat) {
        $product = new Product($name, $price, $cat);
        $this->products[] = $product;
    }

    public function calculateTotal() {
        $total = 0;
        foreach ($this->products as $product) {
            $category = $product->category;
            if ($category === 'Electronics') {
                // 10% tax for electronics
                $total += $product->price * 1.1;
            } elseif ($category === 'Clothing') {
                // 20% tax for clothing
                $total += $product->price * 1.2;
            } else {
                $total += $product->price;
            }
        }
        return $total;
    }
}

$cart = new ShoppingCart();
$cart->addProduct('Laptop', 1000, 'Electronics');
$cart->addProduct('Shirt', 20, 'Clothing');
$cart->addProduct('Phone', 500, 'Electronics');
$cart->addProduct('Jeans', 50, 'Clothing');

$total = $cart->calculateTotal();
echo "Total cost: $" . $total;