<?php
/**
 * Wrapper for the FlySystem adapters.
 * <https://flysystem.thephpleague.com/docs/>
 */

namespace App\Service\Utility;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

class FlysystemService
{
  private array $storages;

  public function __construct(
    FilesystemOperator $acmeStorage
  ) {
    $this->storages['user_profile_pictures'] =
                  $acmeStorage;
  }

  public function getStorage(string $key)
  {
    return $this->storages[$key];
  }

  public function listContents(string $key)
  {
    $allPaths = $this->storages[$key]
      ->listContents('/')
      ->filter(fn(StorageAttributes $attributes) =>
              $attributes->isFile())
      ->map(fn(StorageAttributes $attributes) =>
              $attributes->path())
      ->toArray();
    return $allPaths;
  }

  public function delete(string $key, string $filename)
  {
    $this->storages[$key]->delete($filename);
  }
}