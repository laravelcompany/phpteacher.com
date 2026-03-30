public function share(Request $request): array
{
  $auth = Auth::check();

  return array_merge(parent::share($request), [
    'auth' => [
      'user' => $auth ?
        new UserResource(
          resource: Auth::user(),
        ) : null,
      ],
      'projects' => $auth ?
        ProjectResource::collection(
          resource: Cache::remember(
            key: Auth::id() . '-projects',
            ttl: CacheTime::HOUR->value * 5,
            callback: static fn () =>
              Project::query()
                ->where('user_id', Auth::id())->get(),
          )
        ) : null,
      'ziggy' => function () use ($request) {
        return array_merge((new Ziggy)->toArray(), [
          'location' => $request->URL(),
        ]);
      },
  ]);
}