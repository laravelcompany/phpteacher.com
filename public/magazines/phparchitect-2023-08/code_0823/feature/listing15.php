public function create(NewFolder $payload): Folder
{
  if ($payload->validate()) {
        // throw a validation exception here or a
        // custom domain-specific exception.
  }

  $response = $this->client->post(
        '/folders',
        $payload->toArray()
  );

  if ($response->failed()) {
    throw new FailedToFetchFolders(
      response: $response,
    );
  }

  return Folder::fromRequest($response->json('data'));
}