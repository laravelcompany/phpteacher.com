    function makeAdder(a) {
        return function(b) {
            return a + b;
        };
    }

    var add5 = makeAdder(5);
    var add10 = makeAdder(10);

    console.log(add5(2)); // Prints 7
    console.log(add10(2)); // Prints 12