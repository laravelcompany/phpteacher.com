<?php

declare(strict_types=1);

use Subsystems\IT_Tools\Populate\KML_Import\RKml;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Kml
{
  private Twig_Environment $view;
  private RKml $repository;

  public function __construct(
    Twig_Environment $view,
    RKml $repository
  ) {
    $this->view = $view;
    $this->repository = $repository;
  }

  /**
   * Where to find the KML files to import
   *
   * @return string
   */
  public function getDataDir(): string
  {
    return realpath(__DIR__ . '/Data/Maps');
  }

  public function listFiles(): array
  {
    return glob($this->getDataDir() . '/*.kml');
  }

  public function execute(): void
  {
    foreach ($this->listFiles() as $file) {
      $this->repository->importKml($file);
    }
  }

  public function render(): string
  {
    $self = $_SERVER['PHP_SELF'];
    $files = $this->listFiles();
    $dataDir = $this->getDataDir();
    $twig = compact('self', 'files', 'dataDir');
    try {
      return $this->view->render('kml_html.twig', $twig);
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
      return $e->getMessage();
    }
  }
}