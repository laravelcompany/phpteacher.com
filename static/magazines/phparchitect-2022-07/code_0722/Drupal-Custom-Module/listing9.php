<?php

/**
 * @file
 * Contains \Drupal\health_sites\Form\HealthSitesVerifyHealthForm.
 */

namespace Drupal\health_sites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
* Configuration form verify status of urls.
*/
class HealthSitesVerifyHealthForm extends FormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'health_sites_verify_health_form';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('health_sites.settings');
    $hostnames = $config->get('hostnames_to_verify');
    $hostnames = array_combine($hostnames, $hostnames);
    
    $form = [];
    
    $form['hostnames'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Hostnames'),
      '#required' => false,
      '#options' => $hostnames,
    );
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#ajax' => array(
        'callback' => '::verifyUrlsAjaxCallback',
        'wrapper' => 'box',
      ),
      '#value' => t('Submit'),
    );
    
    $form['box'] = array(
      '#type' => 'markup',
      '#prefix' => '<div id="box"><h1>List of urls verified</h1>',
      '#suffix' => '</div>',
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected = false;
    foreach ($form_state->getValue('hostnames') as $hostname) {
      if ($hostname) {
        $selected = true;
      }
    }
    
    if(!$selected) {
      $form_state->setErrorByName('hostnames', $this->t('Please select hostnames to verify'));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function verifyUrlsAjaxCallback(array &$form, FormStateInterface $form_state) {

    $form['box']['#markup'] = '';
    
    $selectedValues = $form_state->getValue('hostnames');

    $config = \Drupal::config('health_sites.settings');
    
    $hostnames = $config->get('hostnames_to_verify');
    $urls_to_verify = $config->get('urls_to_verify');

    $hostnames = array_intersect($hostnames, $selectedValues);
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

    foreach ($urls as $hostname => $list_urls) {
      foreach ($list_urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
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
      
        $form['box']['#markup'] .= t('Status of %url: %status',
                ['%url' => $url, '%status' => $node->
                    get('field_status_up')->getValue()[0]['value']]) . '<br>';
      }
    }

    return $form['box'];
  }

  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
