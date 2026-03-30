use PHPUnit\Framework\TestCase;
class ShoppingCartTest extends TestCase {
    public function testCalculateSubtotal() {
        // Create an instance of ShoppingCart
        $cart = new ShoppingCart();
        // Define a sample array of items
        $items = [
            ['price' => 10, 'quantity' => 2], // $20
            ['price' => 5, 'quantity' => 4],  // $20
            ['price' => 15, 'quantity' => 1], // $15
        ];
        // Calculate the expected subtotal
        $expected = 20 + 20 + 15;
        // Call the calculateSubtotal method
        //     and assert the result
        $subtotal = $cart->calculateSubtotal($items);
        // Assert that the calculated subtotal matches
        //     the expected subtotal
        $this->assertEquals($expected, $subtotal);
    }
}