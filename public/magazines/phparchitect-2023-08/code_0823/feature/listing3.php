final class IntegrationServiceProvider
        extends ServiceProvider
        implements DeferableServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            abstract: Client::class,
            concrete: fn () => new Client(
                request: Http::baseUrl(
                    config('service.name.url')
                )->asJson()
                 ->withToken(
                        config('service.name.token')
                ),
            ),
        );
    }

    public function provides(): array
    {
        return [
            Client::class,
        ];
    }
}