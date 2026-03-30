Public function testExceptionMessage() {
    $this->assertThrows(
        MyCustomException::class,
        function () {
            // Code that should throw MyCustomException
            //    with specific message
        },
        'Expected exception message'
    );
}