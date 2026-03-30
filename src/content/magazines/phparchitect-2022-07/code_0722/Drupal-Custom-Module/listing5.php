use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

/**
* Implements hook_help().
*/
function health_sites_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.health_sites':
      $output = '';
      $output .= '<h3>' . t('About') . '</h3>';
      $output .= '<p>' . t('This is health_sites module.') . '</p>';
      $output .= '<p>' . t('This custom module permits to verify health of hostname/urls list.') . '</p>';
      
      return $output;
    
    default:
  }
}

/**
 * Implements hook_cron().
 */
function health_sites_cron() {
  
  $config = \Drupal::config('health_sites.settings');

  $hostnames = $config->get('hostnames_to_verify');
  $urls_to_verify = $config->get('urls_to_verify');

  $urls = [];
  foreach ($hostnames as $key => $hostname) {

    if (!isset($urls_to_verify[$key])) {
      $urls[] = $hostname;
    }
    else {
      foreach ($urls_to_verify[$key] as $url) {
        $hostname = strpos($hostname, 'http')===0 ? $hostname : 'http://'.$hostname;
        $urls[$hostname][] = $hostname . $url;
      }
    }
  }
  
//  \Drupal::logger('my_moduleXXX')->alert(print_r($urls,1));
  foreach ($urls as $hostname => $list_urls) {
    foreach ($list_urls as $url) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_TIMEOUT,10);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
      curl_setopt($ch, CURLOPT_REFERER, 'https://www.pignatelli.com/');    
      curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'history_url')
      ->condition('field_hostname', $hostname)
      ->condition('field_url', str_replace($hostname, "", $url));
      $nid = $query->execute();
      if (empty($nid)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->create([
          'type' => 'history_url',
          'title' => 'Status of ' . $url,
          'field_hostname' => $hostname,
          'field_url' => str_replace($hostname, "", $url),
          'langcode' => 'en',
          'status' => 1,
          'revision' => 1
        ]);
      }
      else {
        $nid = array_pop($nid);
        $node = Node::load($nid); 
        $node->setNewRevision();
        $node->revision_log = 'Created revision for node' . $nid . ' programmatically';
        $node->setRevisionCreationTime(REQUEST_TIME);
        $node->setRevisionUserId(1);
      }
      
      if ($httpcode!=200) {
        $node->set('field_status_up', 0);
      } else {
        $node->set('field_status_up', 1);
      }
    
      $node->save();
    }
  }
}
