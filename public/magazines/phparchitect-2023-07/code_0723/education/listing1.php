// Sample "Function" serverless code

<?php

return function($request) {

    return [
        'status' => 200,
        'body' => [
            'message' => 'Hello World',
	       ]
    ];
}