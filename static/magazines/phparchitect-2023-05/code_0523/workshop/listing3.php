<?php
namespace App\Command\Demo;
use Minicli\Command\CommandController;
class DefaultController extends CommandController
{
  public function handle(): void
  {
    $this->getPrinter()
        ->success('Hello php[architect] :D' , false);
    $this->getPrinter()
        ->info('Info message with background' , true);
    $this->getPrinter()
        ->error('Error Message :(', false);
  }
}