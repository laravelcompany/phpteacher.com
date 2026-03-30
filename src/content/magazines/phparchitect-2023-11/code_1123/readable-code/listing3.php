<?php
interface TaxCalculator {
  public function calculateTax(Product $product):float;
}

class BasicTaxCalculator implements TaxCalculator {
    public function calculateTax(
        Product $product
    ): float {
        return 0; // Default tax calculation, no tax
    }
}

class Product {
    public $name;
    public $price;
    public $category;

    public function __construct($name, $price, $cat) {
        $this->name = $name;
        $this->price = $price;
        $this->category = $cat;
    }
}

class Category {
    public function __construct(
        public string $name,
        private readonly TaxCalculator $taxCalculator
    ) { }

    public function calculateTax(
        Product $prod
    ): float {
      return $this->taxCalculator->calculateTax($prod);
    }
}

class ShoppingCart {
    private $products = [];

    public function addProduct(Product $product) {
        $this->products[] = $product;
    }

    public function calculateTotal(): float {
        $total = 0;
        foreach ($this->products as $product) {
          $total += $product->price +
            $product->category->calculateTax($product);
        }
        return $total;
    }
}

// Usage
$cart = new ShoppingCart();

$electronicsCategory = new Category(
    'Electronics',
    new class implements TaxCalculator {
        public function calculateTax(
            Product $product
        ): float {
            return $product->price * 0.1; // 10% tax
        }
    });

$clothingCategory = new Category(
    'Clothing',
    new class implements TaxCalculator {
        public function calculateTax(
            Product $product
        ): float {
            return $product->price * 0.2; // 20% tax
        }
    });

$product1 = new Product(
    'Laptop', 1000, $electronicsCategory
);
$product2 = new Product(
    'Shirt', 20, $clothingCategory
);
$product3 = new Product(
    'Phone', 500, $electronicsCategory
);
$product4 = new Product(
    'Jeans', 50, $clothingCategory
);

$cart->addProduct($product1);
$cart->addProduct($product2);
$cart->addProduct($product3);
$cart->addProduct($product4);

$total = $cart->calculateTotal();
echo "Total cost: $" . $total;