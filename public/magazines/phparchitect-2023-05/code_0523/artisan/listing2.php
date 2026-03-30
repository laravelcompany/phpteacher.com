class BlogCreateAction
{
    public function __construct(
        Request $request,
        BlogCreateResponder $responder,
        BlogService $domain
    ) {
        // ...
    }

    public function __invoke()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost('blog');
            $blog = $this->domain->create($data);
        } else {
            $blog = $this->domain->newInstance();
        }

        return $this->responder->response($blog);
    }
}