/** * @dataProvider additionProvider */
Public function testAddition($a, $b, $expectedResult) {
    $result = add($a, $b);
    $this->assertEquals($expectedResult, $result);
}

Public function additionProvider() {
    Return [
        [1, 2, 3],
        [0, 0, 0],
        [-1, 1, 0],
        [10, -5, 5],
    ];
}