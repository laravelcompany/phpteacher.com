Use PHPUnit\Framework\TestCase;

class UserResolverTest extends TestCase
{
    public function testUserNameResolver()
    {
        // Create a mock user object with a name
		$user = (object) ['name' => 'John Doe'];

        // Invoke the resolveUserName function
        $result = resolveUserName($user);

        // Use PHPUnit assertions to check the result
        $this->assertEquals('John Doe', $result);
    }
}