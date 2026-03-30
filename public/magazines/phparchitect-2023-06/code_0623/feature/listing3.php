public function its_props_are_ro(): void
{
  try {
    $this->name = 'bar';
  } catch (\Throwable $e) {
  }
  
	$this->name->shouldEqual(self::NAME);
}