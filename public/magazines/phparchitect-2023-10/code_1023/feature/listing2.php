$mutation = '
	Mutation {
	  createUser(input: {
	    name: "John Doe"
	    age: 30
	  }) {
	    Name
	    Age
	  }
	}
';

$result = GraphQL::executeQuery($schema, $mutation)
                ->toArray();