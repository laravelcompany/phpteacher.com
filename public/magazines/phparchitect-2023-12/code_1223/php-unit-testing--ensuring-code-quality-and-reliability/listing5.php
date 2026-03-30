use PHPUnit\Framework\TestCase;
class IsEvenTest extends TestCase {
    public function testIsEvenForEvenNumbers() {
        // Test for even numbers
        $this->assertTrue(isEven(2));
        $this->assertTrue(isEven(4));
        $this->assertTrue(isEven(100));
    }
}