<?php

/**
 * @file
 * Contains \Drupal\faq\Form\GeneralForm.
 */

namespace Drupal\faq_ask\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Form for the FAQ settings page - experts tab.
 */
class ExpertsForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_ask_experts_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $faq_ask_settings = $this->config('faq_ask.settings');
    
    parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    
    parent::submitForm($form, $form_state);
  }
  
}