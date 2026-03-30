$dogStoryEbookId = 1;
$ebookController->registerEbook(json_encode([
    "ebookId" => $dogStoryEbookId,
    "title" => "Happy Dog Story",
    "content" => "Dog went for work a walk and is happy because of that!",
    "price" => 10
]));
$cookbookId = 2;
$ebookController->registerEbook(json_encode([
    "ebookId" => $cookbookId,
    "title" => "Cookbook - Home Recipes",
    "content" => "To make scrambled eggs, you need to first have eggs.",
    "price" => 20
]));

echo "Making order for two books\n";

$orderController->placeOrder(json_encode([
    "email" => "johnybravo@o3.en",
    "ebookIds" => [$dogStoryEbookId, $cookbookId],
    "creditCard" => [
        "number" => "4242424242424242",
        "validTillMonth" => 12,
        "validTillYear" => 2028,
        "cvc" => 123
    ]
]));

echo sprintf("Orders history:\n%s\n", $orderController->getOrders());