it('renders timeline component', function (): void {
  get(
    uri: action(InvokableController::class),
  )->assertOk()
   ->assertInertia(
     fn (AssertableInertia $page) =>
       $page->component('Timeline/View')
            ->has('posts',
                  15,
                  fn (AssertableInertia $page) =>
                    $page->where('topic', 'Foo Bar')
                         ->etc()
            )
   );
});