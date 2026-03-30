class BlogCreateResponder
{
    public function __construct(
        Response $response,
        TemplateView $view
    ) {
        // ...
    }

    public function response(BlogModel $blog)
    {
        // is there an ID on the blog instance?
        if ($blog->id) {
            // yes, which means it was saved already.
            // redirect to editing.
            $this->response->setHeader(
                'Location',
                '/blog/edit/{$blog->id}'
            );
        } else {
            // no, which means it has not been
            // saved yet. show the creation form with
            // the current data.
            $html = $this->view->render(
                'create.php',
                ['blog' => $blog]
            );
            $this->response->setContent($html);
        }

        return $this->response;
    }
}