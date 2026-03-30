function add($a, $b) {
    return $a + $b;
}

use PHPUnit\Framework\TestCase;
class MathTest extends TestCase {
    public function testAdd() {
        $result = add(2, 3);
        $this->assertEquals(5, $result);
    }
}