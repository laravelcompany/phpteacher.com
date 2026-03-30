public function testAssetIndexReturnsExpectedAssets()
{
    Asset::factory()->count(3)->create();

    $this->actingAsForApi(
            User::factory()->superuser()->create()
        )->getJson(
            route('api.assets.index', [
                'sort' => 'name',
                'order' => 'asc',
                'offset' => '0',
                'limit' => '20',
            ]))
        ->assertOk()
        ->assertJsonStructure([
            'total',
            'rows',
        ])
        ->assertJson(
            fn(AssertableJson $json) =>
                          $json->has('rows', 3)->etc()
        );
}