class Router
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function handle($request)
    {
        $controllerIdentifier = $this->getControllerIdentifier(
             $request->getUrl());
        $controller = $this->container->get($controllerIdentifier);
        // ...
     }
}