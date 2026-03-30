use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

// Define the ‘User’ type
$userType = new ObjectType([
    'name' => 'User',
    'fields' => [
    'name' => [
        'type' => Type::string(),
        // Link to the resolver function
        'resolve’ => 'resolveUserName',
    ],
        // ... other fields ...
    ],
]);