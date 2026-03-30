interface FactoryInterface
{
    public function newInstance();
}

class DocumentFactory implements FactoryInterface
{
    public function __construct(protected ContainerInterface $container)
    {                
    }
    
    public function newInstance(): DocumentRepository
    {
        return new DocumentRepository($this->container->get('document_store'));
    }
}