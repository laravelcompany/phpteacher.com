<?php

namespace Drupal\health_sites\Controller;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/*
 * Controller for Order content type.
 */
class HealthSitesController extends ControllerBase {
  
  /**
   * Display the markup of Hostnames list webpage.
   *
   * @return array
   *   Return markup array.
   */
  public function index(string $module_name, Request $request) {
    $routingFilePath = DRUPAL_ROOT . '/' . drupal_get_path('module', $module_name) . '/health_sites.routing.yml';
    $routingFileContents = file_get_contents($routingFilePath);
    $results = \Drupal\Core\Serialization\Yaml::decode($routingFileContents);

    $links = [];
    foreach ($results as $key => $item) {
      if ($key != 'health_sites.main') {
        $links[$item['path']] = $item['defaults']['_title'];
      }
    }
    
    return [
      '#theme' => 'health_sites_main_page',
      '#title' => $this->t('List of configure pages'),
      '#links' => $links,
    ];
  }
}
