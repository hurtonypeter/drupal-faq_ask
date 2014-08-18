<?php

/**
 * @file
 * Contains \Drupal\faq_ask\Controller\FaqAskController.
 */

namespace Drupal\faq_ask\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\faq_ask\FaqAskHelper;

/**
 * Controller routines for FAQ Ask routes.
 */
class FaqAskController extends ControllerBase {
  
  /**
   * 
   * 
   * @return
   *   The form to ask question.
   */
  public function askPage() {
    $build = array();
    
    $build['form'] = $this->formBuilder()->getForm('Drupal\faq_ask\Form\AskForm');
    
    // just for testing purposes
    //FaqAskHelper::notifyExperts();
    
    return $build;
  }
  
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
  
  public function unanswered() {
    $build = array();
    
    $query = db_select('faq_ask_unanswered', 'u')->fields('u', array('nid'));
    $result = $query->execute()->fetchAllAssoc('nid');
    
    $nodes = Node::loadMultiple(array_keys($result));
    
    foreach($nodes as $node) {
      $build['nodes'][] = node_view($node, 'teaser');
    }
    
    return $build;
  }
  
}