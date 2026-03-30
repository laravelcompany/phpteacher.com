<?php
class Product {
    public $name;
    public $price;
    public $category;

    public function __construct(
        $name, $price, $category
    ) {
        $this->name = $name;
        $this->price = $price;
        $this->category = $category;
    }
}

class Category {
    public $name;
    public $taxRate;

    public function __construct($name, $taxRate) {
        $this->name = $name;
        $this->taxRate = $taxRate;
    }
}

class TaxCalculator {
    public static function calculateTax(
        $product, $categories
    ) {
      foreach ($categories as $category) {
        if ($category->name === $product->category) {
          return $product->price * $category->taxRate;
        }
      }
      return 0; // No tax if category not found
    }
}

class ShoppingCart {
    public $products = [];
    public $categories = [];

    public function addProduct($name, $price, $cat) {
        $product = new Product($name, $price, $cat);
        $this->products[] = $product;
    }

    public function addCategory($name, $taxRate) {
        $category = new Category($name, $taxRate);
        $this->categories[] = $category;
    }

    public function calculateTotal() {
        $total = 0;
        foreach ($this->products as $product) {
            $tax = TaxCalculator::calculateTax(
                    $product,
                    $this->categories
            );
            $total += $product->price + $tax;
        }
        return $total;
    }
}

$cart = new ShoppingCart();
$cart->addProduct('Laptop', 1000, 'Electronics');
$cart->addProduct('Shirt', 20, 'Clothing');
$cart->addProduct('Phone', 500, 'Electronics');
$cart->addProduct('Jeans', 50, 'Clothing');

// 10% tax for Electronics
$cart->addCategory('Electronics', 0.1);
// 20% tax for Clothing
$cart->addCategory('Clothing', 0.2);

$total = $cart->calculateTotal();
echo "Total cost: $" . $total;