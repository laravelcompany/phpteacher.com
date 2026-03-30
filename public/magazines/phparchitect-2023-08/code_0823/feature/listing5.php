it('will do some test that we need our SDK for',
    function (): void {
        Http::fake([
            '*' => Http::response(
                    ['response' => 'body'],
                    Status::CODE,
                    ['headers' => 'here']
            ),
        ]);

        $sdk = app()->make(Client::class);

       $sdk->folders();
    }
);