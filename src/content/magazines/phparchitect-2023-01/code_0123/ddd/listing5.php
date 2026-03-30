<?php
class AppController extends Controller
        implements CApiCodePrefix
{
    protected function processCtgoException(
        CtgoException $e
    ): void {
        $response = $e->getCtgoResponse();
        $statusCode = $response->getStatusCode();
        $statusText = $response->getStatusText();
        $success = $response->getErrorResponseBody();
        $this->set(compact('success'));
        $this->viewBuilder()
            ->setOption('serialize', 'success');
        $this->response = $this->response
            ->withStatus($statusCode, $statusText);
    }
}