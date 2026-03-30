class ServiceLocatorExample
{
    protected $service;

    public function__construct(ContainerInterface $container)
    {
        $this->service = $container->get('service');
    }
}