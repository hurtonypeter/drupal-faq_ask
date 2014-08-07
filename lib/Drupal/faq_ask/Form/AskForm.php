<?php

/**
 * @file
 * Contains \Drupal\faq\Form\AskForm.
 */

namespace Drupal\faq_ask\Form;

use Drupal\Core\Form\FormBase;
use Drupal\faq_ask\FaqAskHelper;

/**
 * Form for asking question.
 */
class AskForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_ask_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $faq_settings = $this->config('faq.settings');
    $faq_ask_settings = $this->config('faq_ask.settings');

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Question'),
      '#size' => 80,
      '#maxlength' => 80,
      '#required' => TRUE,
    );

    $form['detailed_question'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Detailed question'),
      '#description' => $this->t('Longer question text. Explain what do you want to ask.')
    );

    if ($faq_ask_settings->get('notify_asker')) {
      if ($this->currentUser()->isAnonymous()) {
        $form['notification_email'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Notification E-mail (optional)'),
          '#description' => $this->t('Write your e-mail here if you would like to be notified when the question is answered.')
        );
      }
      else {
        $form['notify'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Notify by E-mail (optional)'),
          '#default_value' => FALSE,
          '#description' => $this->t('Check this box if you would like to be notified when the question is answered.'),
        );
      }
    }

    if (!$faq_ask_settings->get('categorize')) {
      //TODO: move searching for faq-related terms to the settings page, and save there just faq-related vocabularies
      // that form is called rarely -> better performance; here we can just read that.
      $faq_type = \Drupal::entityManager()->getStorage('node')->create(array('type' => 'faq'));
      $faq_fields = $faq_type->getFieldDefinitions();
      $faq_node_edit_form = \Drupal::getContainer()->get('entity.form_builder')->getForm($faq_type);
      foreach ($faq_fields as $field) {
        if ($field instanceof \Drupal\field\Entity\FieldInstanceConfig && $field->getType() == 'taxonomy_term_reference') {
          $form['vocabularies'][$field->getName()] = $faq_node_edit_form[$field->getName()];
        }
      }
    }
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
  public function validateForm(array &$form, array &$form_state) {

    check_markup($form_state['values']['title']);
    check_markup($form_state['values']['detailed_question']);
    if (!empty($form_state['values']['notification_email']) && !valid_email_address($form_state['values']['notification_email'])) {
      $this->setFormError('notification_email', $form_state, $this->t('@email is not a valid email address.', array('@email' => $form_state['values']['notification_email'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $faq_ask_settings = $this->config('faq_ask.settings');

    // create node entity
    //TODO: asker provided categories are not saving yet
    $node = \Drupal::entityManager()->getStorage('node')->create(array(
      'type' => 'faq',
      'status' => 0,
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->id,
      'title' => $form_state['values']['title'],
      'field_detailed_question' => $form_state['values']['detailed_question'],
      'body' => $faq_ask_settings->get('unanswered_body'),
    ));
    //$node->save();
    
    if ($faq_ask_settings->get('notify_experts')) {
      FaqAskHelper::notifyExperts($node);
    }
    
    

    drupal_set_message($this->t('Thank you for your question!'));
  }

}
