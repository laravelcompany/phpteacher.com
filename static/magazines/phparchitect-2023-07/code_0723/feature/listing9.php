<?php
// src/Twig/AppExtension.php

/**
 * Provides a new Twig filter (flysystem_asset) which
 * uses the flysystem adapter to generate public URLs.
 */

namespace App\Twig;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
  private FilesystemOperator $acmeStorage;

  public function __construct(
    FilesystemOperator $acmeStorage
  )
  {
    $this->acmeStorage = $acmeStorage;
  }

  public function getFilters(): array
  {
    return [
      new TwigFilter('flysystem_asset', [
        $this,
        'flysystemAsset',
      ]),
    ];
  }

  // Use the storage adapter to generate
  //   the public url.
  public function flysystemAsset(
    string $imgName
  ): string
  {
    if ( ! is_null($imgName) && $imgName) {
      try {
        return $this->acmeStorage->publicUrl($imgName);
      } catch (UnableToGeneratePublicUrl $e) {
        // Filesystem adapter doesn't generate
        //    public urls. Do it manually.
        return '/uploads/storage/' . $imgName;
      }
    }

    return '';
  }
}