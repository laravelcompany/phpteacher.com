class ShoppingCart {
    public function calculateSubtotal($items) {
        $subtotal = 0;
        foreach ($items as $item) {
            // Assuming each item has a
            // 'price' and 'quantity' property
            $subtotal += $item['price'] *
                            $item['quantity'];
        }
        return $subtotal;
    }
}