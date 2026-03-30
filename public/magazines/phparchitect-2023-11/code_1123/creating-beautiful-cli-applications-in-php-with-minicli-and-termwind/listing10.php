<?php

declare(strict_types=1);

namespace App\Command\Resize;

use App\Command\BaseController;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalidDimensionException;

final class DefaultController extends BaseController
{
    /**
     * @throws FileNotFoundException
     * @throws InvalidDimensionException
     */
    public function handle(): void
    {
      $question = 'Which image do you want to resize?';
      $askQuestion = $this->buildQuestion($question);
      $imagePath = $this->ask($askQuestion);

      if ( ! realpath($imagePath)) {
        throw new FileNotFoundException();
      }

      $imageInfo = $this->app->image->info($imagePath);
      $this->printImageInfo($imageInfo);

      $newW = $this->ask(
        $this->buildQuestion(
           "What is the new WIDTH? " .
           "[10..$imageInfo->width]"
		)
      );

      if ($newW < 10 || $newW > $imageInfo->width) {
        throw new InvalidDimensionException(
            $imageInfo->width
        );
      }

      $newH = $this->ask(
        $this->buildQuestion(
            "What is the new HEIGHT?" .
            "[10..{$imageInfo->height}]"
		)
      );

      if ($newH < 10 || $newH > $imageInfo->height) {
        throw new InvalidDimensionException(
            $imageInfo->height
        );
      }

      $result = $this->app->image->resize(
					$imagePath, 
					(int) $newW, 
					(int) $newH, 
					$this->app->config->output_path
	  );

      $this->successMessage('Resized successfully!' .
            'Check the details below:');
      $this->printImageInfo($result);
  }
}