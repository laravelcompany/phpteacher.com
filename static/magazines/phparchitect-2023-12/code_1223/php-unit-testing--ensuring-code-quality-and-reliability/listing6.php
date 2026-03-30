use PHPUnit\Framework\TestCase;
class IsEvenTest extends TestCase {
    public function
            testIsEvenReturnsTrueForEvenNumbers() {
        $this->assertTrue(isEven(2));
        $this->assertTrue(isEven(4));
        $this->assertTrue(isEven(100));
    }

    public function
            testIsEvenReturnsFalseForOddNumbers() {
        $this->assertFalse(isEven(1));
        $this->assertFalse(isEven(3));
        $this->assertFalse(isEven(99));
    }

    public function
            testIsEvenReturnsTrueForZero() {
        $this->assertTrue(isEven(0));
    }

    public function
      testIsEvenReturnsFalseForNegativeEvenNumbers() {
        $this->assertFalse(isEven(-2));
        $this->assertFalse(isEven(-4));
    }

    public function
      testIsEvenReturnsFalseForNegativeOddNumbers() {
        $this->assertFalse(isEven(-1));
        $this->assertFalse(isEven(-3));
    }
}