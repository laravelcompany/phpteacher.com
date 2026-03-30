public function testGetParts(): void
{
    $e = CronExpression::factory('0 22 * * 1-5');
    $parts = $e->getParts();

    $this->assertSame('0', $parts[0]);
    $this->assertSame('22', $parts[1]);
    $this->assertSame('*', $parts[2]);
    $this->assertSame('*', $parts[3]);
    $this->assertSame('1-5', $parts[4]);
}