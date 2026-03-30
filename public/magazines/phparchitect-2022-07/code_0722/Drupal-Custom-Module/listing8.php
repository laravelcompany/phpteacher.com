<?php

/**
 * @file
 * Contains \Drupal\health_sites\Form\HealthSitesConfigureUrlsForm.
 */

namespace Drupal\health_sites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
* Configuration form definition for urls of hostnames to verify.
*/
class HealthSitesConfigureUrlsForm extends FormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'health_sites_configuration_urls_form';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('health_sites.settings');
    $hostnames = $config->get('hostnames_to_verify');
    if (empty($hostnames)) {
      $url = Link::fromTextAndUrl(t('Configure Hostname'), Url::fromUri('base:admin/health_sites'))->toString();
    
      $form['warning'] = array(
        '#markup' => $this->t('<b style="color:red;">No Hostnames. Insert into %url page</b>', ['%url' => $url]),
      );
    }
    else {
      
      $form = [];
      
      $form['hostname'] = array(
        '#type' => 'select',
        '#title' => $this->t('Hostname'),
        '#required' => true,
        '#options' => ['' => t('Select Hostname')] + $hostnames,
        '#ajax' => [
          'callback' => '::getUrlsAjaxCallback',
          'disable-refocus' => FALSE,
          'event' => 'change',
          'wrapper' => 'wrapper-urls',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      );

      $form['urls'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Urls to verify'),
        '#prefix' => '<div id="wrapper-urls">',
        '#suffix' => '</div>',
      );
      
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      );
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlsAjaxCallback(array &$form, FormStateInterface $form_state) {
    $selectedValue = $form_state->getValue('hostname');
    if ($selectedValue !== "") {
      $config = \Drupal::config('health_sites.settings');
      $urls_to_verify = $config->get('urls_to_verify');
      
      if (isset($urls_to_verify[$selectedValue])) {
        $form['urls']['#value'] = implode(chr(10), $urls_to_verify[$selectedValue]);
      }
      else {
        $form['urls']['#value'] = '';
      }
    }

    return $form['urls'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if($form_state->getValue('urls')==="") {
      $form_state->setErrorByName('urls', $this->t('Please enter urls to verify'));
    }
  }
  
  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::config('health_sites.settings');
    $urls_to_verify = $config->get('urls_to_verify');
    
    $key = $form_state->getValue('hostname');
    
    $urls = $form_state->getValue('urls');
    $urls_to_verify[$key] = explode(chr(13).chr(10), $urls);

    $config = \Drupal::service('config.factory')->getEditable('health_sites.settings');
    $config->set('urls_to_verify', $urls_to_verify) ->save();
    \Drupal::messenger()->addMessage
        (t("All Urls of %hostname Hostname are saved.",
            ['%hostname' => $form['hostname']['#options'][$key]]));
  }
}

