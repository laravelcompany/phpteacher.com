<?php

/**
 * @file
 * Contains \Drupal\health_sites\Form\HealthSitesConfigureForm.
 */

namespace Drupal\health_sites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Configuration form definition for hostname to verify.
*/
class HealthSitesConfigureForm extends FormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'health_sites_configuration_form';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];
    
    $config = \Drupal::config('health_sites.settings');
    $hostnames = $config->get('hostnames_to_verify');
    $hostnames = implode(chr(10), $hostnames);
    
    $form['hostnames'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Hostnames to verify'),
      '#default_value' => $hostnames,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if($form_state->getValue('hostnames')==="") {
      $form_state->setErrorByName('hostnames', $this->t('Please enter hostnames to verify'));
    }
  }
  
  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $hostnames = $form_state->getValue('hostnames');
    $hostnames = !$hostnames ? [] : explode(chr(13).chr(10), $hostnames);
    
    $config = \Drupal::service('config.factory')->getEditable('health_sites.settings');
    $config->set('hostnames_to_verify', $hostnames) ->save();
    
    \Drupal::messenger()->addMessage(t("All Hostnames are saved."));
  }
}
