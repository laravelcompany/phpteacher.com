public function testCalculateWithMultipleItems() {
    $cart = new ShoppingCart();
    $items = [
        ['name' => 'Item A', 'price' => 10],
        ['name' => 'Item B', 'price' => 15],
    ];
    $subtotal = $cart->calculateSubtotal($items);
    $this->assertEquals(25, $subtotal);
}