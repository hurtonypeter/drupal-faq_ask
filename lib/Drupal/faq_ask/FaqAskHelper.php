<?php

/**
 * @file
 * Contains \Drupal\faq_ask\Controller\FaqAskController.
 */

namespace Drupal\faq_ask;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\Crypt;

/**
 * Static helper functions to FAQ Ask.
 */
class FaqAskHelper {

  public static function notifyExperts(/* NodeInterface $node */) {
    $node = \Drupal\node\Entity\Node::load(5);
    $experts = FaqAskHelper::getFaqExpertsAndEmails($node->id());

    //var_dump($node->language()->id);

    drupal_mail('faq_ask', 'newquestion', array_values($experts), $node->language()->id, array('node' => $node), TRUE);
  }

  /**
   * Returns expert user names and emails to a selected node.
   * 
   * @param integer $nid
   *   Node id.
   * @return array Returns the expert's name and email to the given node id.
   */
  public static function getFaqExpertsAndEmails($nid) {
    $query = db_select('node', 'n');
    $query->join('taxonomy_index', 'ti', 'n.nid = ti.nid');
    $query->join('faq_expert', 'fe', 'ti.tid = fe.tid');
    $query->join('users', 'u', 'fe.uid = u.uid');
    $query->fields('u', array('name', 'mail'));
    $query->condition('n.nid', $nid);
    $query->condition('u.status', 1);
    $results = $query->execute()->fetchAllKeyed();

    return $results;
  }

  public static function getFaqTerms($nid) {
    $query = db_select('node', 'n');
    $query->join('taxonomy_index', 'ti', 'n.nid = ti.nid');
    $query->fields('ti', array('tid'));
    $query->condition('n.nid', $nid);
    $result = $query->execute()->fetchCol();
    var_dump(Term::loadMultiple($result));
  }

  /**
   * Returns a HMAC token to the given string
   * 
   * @param string $value
   *   A string which generate to.
   * @return string HMAC token
   */
  public static function getToken($value = '') {
    return Crypt::hmacBase64($value, \Drupal::service('private_key')->get());
  }
  
  /**
   * Tells that the given token is valid or not to the provided value.
   * 
   * @param type $token
   *   The provided token.
   * @param type $value
   *   Token value.
   * @param bool $skip_anonymous
   *   Skip the anonymous user?
   * @return bool Is the token valid.
   */
  public static function validToken($token, $value = '', $skip_anonymous = FALSE) {
    $user = \Drupal::currentUser();
    return (($skip_anonymous && $user->id() == 0) || ($token == FaqAskHelper::getToken($value)));
  }
}
