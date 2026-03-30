use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class GraphQLApiTest extends TestCase
{
    public function testGetUser()
    {
        // Replace this with the actual API endpoint URL
        $apiEndpoint = 'https://your-api-endpoint/graphql';

        // Create a Guzzle HTTP client
        $client = new Client();

        // Prepare the GraphQL query
        $query = '{
            getUser(id: 123) {
                name
                email
            }
        }';

		// Send a POST request to the GraphQL API
		$response = $client->post($apiEndpoint, [
				    'json' => [
				        'query' => $query,
				    ],
		]);
				
		// Parse the response JSON
		$contents = $response->getBody()->getContents();
        $data = json_decode($contents, true);

		// Assert that the user's name
        //   matches the expected value
        $actual = $data['data']['getUser']['name'];
		$this->assertEquals('John Doe', $actual);
    }
}