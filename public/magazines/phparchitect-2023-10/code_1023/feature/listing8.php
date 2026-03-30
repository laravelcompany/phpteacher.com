final class GitHub
{
  public function __construct(
    public function ClientInterface $client,
  ) {}

  public static function new(
    ClientInterface $client
  ): GitHub {
    return new GitHub(
      client: $client,
    );
  }

  public static function discover(): GitHub
  {
    return new GitHub(
      client: Psr18ClientDiscovery::find(),
    );
  }
}