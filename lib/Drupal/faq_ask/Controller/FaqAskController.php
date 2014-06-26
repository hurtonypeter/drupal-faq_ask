<?php

/**
 * @file
 * Contains \Drupal\faq_ask\Controller\FaqAskController.
 */

namespace Drupal\faq_ask\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for FAQ Ask routes.
 */
class FaqAskController extends ControllerBase {
  
  /**
   * Renders the form for the FAQ Settings page - Experts tab.
   *
   * @return
   *   The form code inside the $build array.
   */
  public function settingsPage() {
    $build = array();
    
    $build['faq_ask_experts_settings_form'] = $this->formBuilder()->getForm('Drupal\faq_ask\Form\ExpertsForm');
  
    return $build;
  }
  
}