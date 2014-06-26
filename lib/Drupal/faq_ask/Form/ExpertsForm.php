<?php

/**
 * @file
 * Contains \Drupal\faq\Form\GeneralForm.
 */

namespace Drupal\faq_ask\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

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
    $faq_settings = $this->config('faq.settings');
    $faq_ask_settings = $this->config('faq_ask.settings');
    $moduleHandler = \Drupal::moduleHandler();

    // Set a basic message that will be unset once we pass the error checking.
    $form['error'] = array('#value' => $this->t('Errors were found, please correct them before proceeding.'), '#weight' => -10);

    $faq_use_categories = $faq_settings->get('use_categories');
    if (!$faq_use_categories) {
      drupal_set_message($this->t('The Faq_Ask module requires that FAQ "Categorize questions."') . ' ' . $this->t('Please go to the <a href="@url">settings page</a> to configure this module.', array('@url' => url('admin/config/content/faq/categories'))), 'error');
      return parent::buildForm($form, $form_state);
    }

    $vocabs = Vocabulary::loadMultiple();
    if (count($vocabs) == 0) {
      drupal_set_message(t('The Faq_Ask module requires that at least one vocabulary apply to the "faq" content type. Please go to the Taxonomy <a href="@taxo_uri">configuration page</a> to do this.', array('@taxo_uri' => url('admin/structure/taxonomy'))), 'error');
      return parent::buildForm($form, $form_state);
    }

    // Get the admin's name.
    $query1 = db_select('users', 'u');
    $query1->addField('u', 'name');
    $query1->condition('u.uid', '1');
    $admin = ucwords($query1->execute()->fetchField());

    // ---------------------------------------------
    // Get the Simplenews newsletters if they exists
    $sn_newsletters = array('0' => $this->t('No newsletter'));

    if ($moduleHandler->moduleExists('simplenews')) {
      // SimpleNews doesn't exists in D8 yet
      //if (!function_exists('simplenews_get_newsletters')) {
      //  drupal_set_message(t('The Simplenews integration is not compatible with this version of Simplenews. Please download a later version.'), 'error');
      //}
      //else {
      //  $list = simplenews_get_newsletters(variable_get('simplenews_vid', ''));
      //  foreach ($list as $key => $object) {
      //    $list[$key] = $object->name;
      //  }
      //  $sn_newsletters += $list;
      //}
    }

    // ---------------------------------------------
    // Get the MailChimp newsletters if they exists
    // mailchimp_subscribe_user
    $mc_newsletters = array('0' => t('No newsletter'));

    if ($moduleHandler->moduleExists('mailchimp_lists')) {
      // MailChimp doesn't exists in D8 yet
      //if (!function_exists('mailchimp_lists_get_available_lists')) {
      //  drupal_set_message(t('The MailChimp integration is not compatible with this version of MailChimp. Please download a later version.'), 'error');
      //}
      //else {
      //  $mc_lists =  mailchimp_get_lists();
      //  foreach ($mc_lists as $key => $object) {
      //    $mc_lists[$object['id']] = $object['name'];
      //  }
      //  $mc_newsletters += $mc_lists;
      //}
    }

    $form['notification'] = array(
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => TRUE,
    );

    $form['notification']['faq_ask_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify experts'),
      '#description' => $this->t('If this box is checked, the expert(s) for the question will be notified via email that a question awaits them. If you do not choose this option, the "Unanswered Questions" block will be the only way they will know they have questions to answer.'),
      '#default_value' => $faq_ask_settings->get('notify'),
    );

    $form['notification']['notify_asker'] = Array(
      '#type' => 'fieldset',
      '#title' => $this->t('Asker notification'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $form['notification']['notify_asker']['faq_ask_asker_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify askers'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be notified via email that their question is answered.'),
      '#default_value' => $faq_ask_settings->get('notify_asker'),
    );

    $form['notification']['notify_asker']['faq_ask_asker_notify_cron'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use cron for asker notification'),
      '#description' => $this->t('If this box is checked, the asker notifications will be sendt via cron.'),
      '#default_value' => $faq_ask_settings->get('notify_by_cron'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE)),
      ),
    );

    $form['notification']['notify_asker']['simplenews'] = array(
      '#type' => 'details',
      '#title' => $this->t('Simplenews newsletter integration'),
      '#open' => $moduleHandler->moduleExists('simplenews'),
    );

    // If the Simplenews module is loaded we can add functionality to add anonymous askers to a newsletter
    $form['notification']['notify_asker']['simplenews']['faq_ask_notify_asker_simplenews'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add anonymous asker to newsletter'),
      '#default_value' => $faq_ask_settings->get('notify_asker_simplenews_tid'),
      '#options' => $sn_newsletters,
      '#description' => ($moduleHandler->moduleExists('simplenews') ? $this->t('Select a newsletter you want anonymous askers to be assigned to.') : $this->t('This functionality needs the <a href="http://drupal.org/project/simplenews">Simplenews module</a> to be activated.')),
      '#disabled' => !$moduleHandler->moduleExists('simplenews'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE))),
    );

    $form['notification']['notify_asker']['simplenews']['faq_ask_notify_asker_simplenews_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm subscription to newsletter'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be asked to confirm the subscription of the newsletter.'),
      '#default_value' => $faq_ask_settings->get('notify_asker_simplenews_confirm'),
      '#disabled' => !$moduleHandler->moduleExists('simplenews'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE))),
    );

    $form['notification']['notify_asker']['mailchimp'] = array(
      '#type' => 'details',
      '#title' => $this->t('MailChimp newsletter integration'),
      '#open' => $moduleHandler->moduleExists('mailchimp_lists'),
    );

    // If the MailChimp module is loaded we can add functionality to add anonymous askers to a newsletter
    $form['notification']['notify_asker']['mailchimp']['faq_ask_notify_asker_mailchimp'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add anonymous asker to newsletter'),
      '#default_value' => $faq_ask_settings->get('notify_asker_mailchimp_lid'),
      '#options' => $mc_newsletters,
      '#description' => ($moduleHandler->moduleExists('mailchimp_lists') ? $this->t('Select a newsletter you want anonymous askers to be assigned to.') : $this->t('This functionality needs the <a href="http://drupal.org/project/mailchimp_lists">MailChimp module</a> to be activated.')),
      '#disabled' => !$moduleHandler->moduleExists('mailchimp_lists'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE))),
    );

    $form['notification']['notify_asker']['mailchimp']['faq_ask_notify_asker_mailchimp_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm subscription to newsletter'),
      '#description' => $this->t('If this box is checked, the asker creating the question will be asked to confirm the subscription of the newsletter.'),
      '#default_value' => $faq_ask_settings->get('notify_asker_simplenews_confirm'),
      '#disabled' => !$moduleHandler->moduleExists('mailchimp_lists'),
      '#states' => array(
        'visible' => array(':input[name="faq_ask_asker_notify"]' => array('checked' => TRUE))),
    );

    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
    );

    $form['options']['faq_ask_categorize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Only expert can categorize'),
      '#description' => $this->t('If this box is checked, only an expert answering a question can add a category.'),
      '#default_value' => $faq_ask_settings->get('categorize'),
      '#weight' => 1,
    );

    $give_options = array(
      0 => $this->t('Asker retains ownerhsip'),
      1 => $this->t('Anonymous questions reassigned to expert'),
      2 => $this->t('All questions reassigned to expert'),
    );

    $form['options']['faq_ask_expert_own'] = array(
      '#type' => 'radios',
      '#options' => $give_options,
      '#title' => $this->t('Give ownership to the expert'),
      '#description' => $this->t('This determines if questions will be reassigned to the expert when answered.'),
      '#default_value' => $faq_ask_settings->get('expert_own'),
      '#weight' => 3,
    );

    $form['options']['faq_ask_unanswered'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Default unanswered body text'),
      '#cols' => 60,
      '#rows' => 1,
      '#description' => $this->t('This text will be inserted into the body of questions when they are asked. This helps make editing easier'),
      '#default_value' => $faq_ask_settings->get('unanswered_body'),
      '#weight' => 4,
    );

    $form['options']['faq_ask_expert_advice'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Answer advice for the expert'),
      '#cols' => 60,
      '#rows' => 1,
      '#description' => $this->t('This text will be shown at the bottom of the "Unanswered questions" block.'),
      '#default_value' => $faq_ask_settings->get('expert_advice'),
      '#weight' => 4,
    );

    $form['options']['advice']['faq_ask_admin_advice'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Advice for an administrator/editor'),
      '#cols' => 60,
      '#rows' => 1,
      '#default_value' => $faq_ask_settings->get('admin_advice'),
      '#weight' => 5,
    );

    $form['options']['advice']['faq_ask_asker_advice'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Advice for an asker'),
      '#cols' => 60,
      '#rows' => 1,
      '#default_value' => $faq_ask_settings->get('asker_advice'),
      '#weight' => 6,
    );

    $help_default = $faq_ask_settings->get('help_text');
    $form['options']['faq_ask_help_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text for the asker'),
      '#cols' => 60,
      '#rows' => drupal_strlen($help_default) / 60,
      '#description' => $this->t('This text will be shown at the top of the "Ask a Question" page.'),
      '#default_value' => $help_default,
      '#weight' => 7,
    );

    $form['experts'] = array(
      '#type' => 'details',
      '#title' => $this->t('Experts'),
      '#open' => TRUE,
    );

    // Use the list of vocabularies from above.
    if (count($vocabs) == 1) {
      // Single vocabulary, don't bother with a selection box, just set it.
      $vid = key($vocabs);
      $def_vid = array($vid => $vid);
      $faq_ask_settings->set('vocabularies', array($vid => $vid));
      $vobj = $vocabs[$vid];
      $free = $vobj->name;
    }
    else {
      // Multiple vocabs available.
      $voc_list = array();  // Clear vocabulary list
      $def_vid = array();  // Clear default selected list
      foreach ($vocabs as $vid => $vobj) {
        $voc_list[$vid] = $vobj->name;  // Create selection list
        if ($vobj->name == 'FAQ') {
          $def_vid[$vid] = $vid;        // Create default selected list
        }
      }

      if (empty($def_vid))              // If no default selected vocabs, then default select all of them
        $def_vid = array_keys($voc_list);  // variable_get('efaq_ask_vocabularies', 0)?

      /* Issue #161406 by phazer: Categories not included in the FAQ list are showing up on the Expert Grid
       * Changed default vids to reflect an array rather than a separate vocab.
       * Also changed the vocab list terms retrieved are based upon
       */

      $form['experts']['faq_ask_vocabularies'] = array(
        '#type' => 'select',
        '#options' => $voc_list,
        '#title' => t('Use these vocabularies'),
        '#multiple' => TRUE,
        '#default_value' => $faq_ask_settings->get('vocabularies'),
        '#description' => $this->t('Only the terms from the selected vocabularies will be included in the list below.')
        . ' ' . $this->t("Simply adding the 'FAQ' content type to a vocabulary will not make it eligible for experts; you must return to here to add it.")
        . '<br/><big>' . $this->t('If you select different vocabularies, you must save the configuration BEFORE selecting users below.') . '</big>',
        '#weight' => 8,
      );
    } // End multiple vocabs.


    $roles = Role::loadMultiple();
    $role_list = array();
    foreach ($roles as $role) {
      if ($role->hasPermission('answer question')) {
        $role_list[$role->id()] = $role->label();
      }
    }

    if (empty($role_list)) {
      drupal_set_message($this->t('No roles with "answer question" permission were found; only @admin is currently eligible to be an expert. You may want to go to the <a href="@access">Permissions page</a> to update your permissions.', array('@access' => url('admin/user/permissions'), '@admin' => $admin), array('langcode' => 'en')), 'error');
    }

    // Get all terms associated with FAQ.
    $vocabs_array = $faq_ask_settings->get('vocabularies');
    $result = db_select('taxonomy_term_data', 'td')
      ->condition('td.vid', $vocabs_array, 'IN')
      ->fields('td', array('tid', 'name', 'description__value'))
      ->orderBy('td.weight')->orderBy('td.name')
      ->execute()
      ->fetchAllAssoc('tid');

    $faq_terms = array();
    foreach ($result as $term) {
      // Show term hierarchy?
      $term_name = /* str_repeat('--', $term['depth']) . */ check_plain($term->name);
      if (substr($term->description__value, 0, 9) == 'suggested') {
        $faq_terms[$term->tid] = $term_name . '<br/>--<small>' . strip_tags($term->description) . '</small>';
      }
      else {
        $faq_terms[$term->tid] = $term_name;
      }
    }
    if (count($faq_terms) == 0) {
      drupal_set_message($this->t('No vocabularies or terms were found for the "faq" content type . Please go to the <a href="@access">Categories page</a> to update your vocabulary.', array('@access' => url('admin/structure/taxonomy'))), 'error');
      return parent::buildForm($form, $form_state);
    }

    // Get all users associated with the roles.
    $faq_expert_names = array();
    // User/1 typically is not assigned roles, but should be in the list.
    $faq_expert_names[1] = $admin;

    $rids = $faq_ask_settings->get('expert_role');
    if (!empty($rids)) {
      if (in_array(DRUPAL_AUTHENTICATED_RID, $rids)) {
        // Authenticated users may be experts, so get all active users.
        // No other roles matter.
        //$result = db_query("SELECT u.uid, u.name FROM {users} u WHERE status=1");
        $result = db_select('users', 'u')
          ->condition('status', 1)
          ->fields('u', array('uid', 'name'))
          ->execute()
          ->fetchAllKeyed();
      }
      else {
        // Only specific roles may be experts.
        //$result = db_query('SELECT DISTINCT(u.uid), u.name FROM {users_roles} ur JOIN {users} u USING (uid) WHERE ur.rid IN (' . db_placeholders($rids) . ')', $rids);
        $query = db_select('users_roles', 'ur');
        $query->join('users', 'u', 'ur.uid = u.uid');
        $query->condition('ur.rid', $rids, 'IN')->fields('u', array('uid', 'name'))->distinct();
        $result = $query->execute()->fetchAllKeyed();
      }

      foreach ($result as $uid => $name) {
        if ($uid != 1) {
          $faq_expert_names[$uid] = ucwords($name);
        }
      }
      // Put them in alphabetical order.
      asort($faq_expert_names);
    }

    if (!empty($role_list)) {
      $form['experts']['faq_expert_role'] = array(
        '#type' => 'select',
        '#title' => $this->t('Expert Roles'),
        '#options' => $role_list,
        '#multiple' => TRUE,
        '#default_value' => $faq_ask_settings->get('expert_role'),
        '#description' => $this->t('User 1 (@admin) will always be in the list, regardless of roles.', array('@admin' => $admin)) . '<br/><big>' . $this->t('If you select different roles, you must save the configuration BEFORE selecting users below.') . '</big>',
        '#weight' => 9,
      );
    }
    $more_experts_than_terms = count($faq_expert_names) > count($faq_terms);

    // If there is only one eligible expert, we might as well preset all categories.
    $expert_msg = NULL;
    $only_one_expert = (count($faq_expert_names) == 1);

    $count = 0;
    if ($more_experts_than_terms) {
      // Experts go down the left; terms go across the top.
      $top = NULL;
      if ($only_one_expert) {
        $top .= '<p>' . $this->t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</p>';
      }
      $top .= '<table id="faq_experts"><tr><th> </th><th>' . implode('</th><th>', $faq_terms) . '</th></tr>';
      if ($only_one_expert) {
        $top .= '<tr><td colspan="100">' . $this->t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</td></tr>';
      }
      foreach ($faq_expert_names as $uid => $name) {
        ++$count;
        $class = $count & 1 ? 'odd' : 'even';
        $left = '<tr class="' . $class . '"><td><strong>' . $name . '</strong></td>';
        foreach ($faq_terms as $tid => $term_name) {
          $box_name = 'expert_' . $uid . '_' . $tid;
          $form['experts'][$box_name] = array(
            '#type' => 'checkbox',
            '#default_value' => $only_one_expert,
            '#prefix' => $top . $left . '<td align="center">',
            '#suffix' => '</td>',
          );
          $top = NULL;
          $left = NULL;
        }
        $form['experts'][$box_name]['#suffix'] .= '</tr>';
      }
      $form['experts'][$box_name]['#suffix'] .= '</table>';
    }
    else {
      // Experts go across the top; terms go down the left.
      $top = NULL;
      if ($only_one_expert) {
        $top .= '<p>' . $this->t('Note: Even though the check boxes below are checked, you must still click the "Save configuration" button to save the expert settings.') . '</p>';
      }
      $top .= '<table id="faq_experts"><tr><th> </th><th>' . implode('</th><th>', $faq_expert_names) . '</th></tr>';
      foreach ($faq_terms as $tid => $term_name) {
        ++$count;
        $class = $count & 1 ? 'odd' : 'even';
        $left = '<tr class="' . $class . '"><td><strong>' . $term_name . '</strong></td>';
        foreach ($faq_expert_names as $uid => $name) {
          $box_name = 'expert_' . $uid . '_' . $tid;
          $form['experts'][$box_name] = array(
            '#type' => 'checkbox',
            '#default_value' => $only_one_expert,
            '#prefix' => $top . $left . '<td align="center">',
            '#suffix' => '</td>',
          );
          $top = NULL;
          $left = NULL;
        }
        $form['experts'][$box_name]['#suffix'] .= '</tr>';
      }
      $form['experts'][$box_name]['#suffix'] .= '</table>';
    }

    $form['experts'][$box_name]['#suffix'] .= $this->t('Those who will be answering questions will need both "answer question" and "edit faq" permissions.');

    $result = db_select('faq_expert', 'fe')
      ->fields('fe', array('uid', 'tid'))
      ->execute()
      ->fetchAll();
    foreach ($result as $expert) {
      $box_name = 'expert_' . $expert->uid . '_' . $expert->tid;
      if (isset($form['experts'][$box_name])) { // Might not be present any more.
        $form['experts'][$box_name]['#default_value'] = TRUE;
      }
      else {
        // Expert 0 means default expert; overlook it.
        if ($expert->tid != 0) {
          drupal_set_message($this->t("@name doesn't exist. If you have just changed your role selections this may be okay.", array('@name' => $box_name)), 'warning');
        }
      }
    }

    if ($only_one_expert) {
      // Create a form value to set default expert to admin.
      $form['experts']['faq_ask_default_expert'] = array(
        '#type' => 'value',
        '#value' => 1,
      );
    }
    else {
      $form['experts']['faq_ask_default_expert'] = array(
        '#type' => 'select',
        '#options' => $faq_expert_names,
        '#multiple' => FALSE,
        '#title' => $this->t('Default expert'),
        '#description' => t('The selected user will be assigned as the expert for all terms that are added to the selected vocabularies until you return to this page and update it.'),
        '#default_value' => $faq_ask_settings->get('default_expert'),
      );
    }

    // Get rid of error element.
    unset($form['error']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    
    $faq_ask_settings = $this->config('faq_ask.settings');
    
    // Save the simple stuff.
    if (isset($form_state['values']['faq_expert_role'])) {
      $faq_ask_settings->set('expert_role', $form_state['values']['faq_expert_role'])->save();
    }

    if (isset($form_state['values']['faq_ask_vocabularies'])) {
      $faq_ask_settings->set('vocabularies', $form_state['values']['faq_ask_vocabularies'])->save();
    }
    $faq_ask_settings->set('categorize', $form_state['values']['faq_ask_categorize'])
      ->set('expert_own', $form_state['values']['faq_ask_expert_own'])
      ->set('notify', $form_state['values']['faq_ask_notify'])
      ->set('notify_asker', $form_state['values']['faq_ask_asker_notify'])
      ->set('notify_asker_simplenews_tid', $form_state['values']['faq_ask_notify_asker_simplenews'])
      ->set('notify_asker_simplenews_confirm', $form_state['values']['faq_ask_notify_asker_simplenews_confirm'])
      ->set('notify_asker_mailchimp_lid', $form_state['values']['faq_ask_notify_asker_mailchimp'])
      ->set('notify_asker_mailchimp_confirm', $form_state['values']['faq_ask_notify_asker_mailchimp_confirm'])
      ->set('notify_by_cron', $form_state['values']['faq_ask_asker_notify_cron'])
      ->set('unanswered_body', $form_state['values']['faq_ask_unanswered'])
      ->set('default_expert', $form_state['values']['faq_ask_default_expert'])
      ->set('expert_advice', $form_state['values']['faq_ask_expert_advice'])
      ->set('help_text', $form_state['values']['faq_ask_help_text'])
      ->set('admin_advice', $form_state['values']['faq_ask_admin_advice'])
      ->set('asker_advice', $form_state['values']['faq_ask_asker_advice'])
      ->save();

    // Get all the selected expert/category options.
    // First, we'll include the default expert for tid=0.
    $values = array();
    $values[] = array('uid' => $form_state['values']['faq_ask_default_expert'], 'tid' => 0);
    foreach ($form_state['values'] as $name => $value) {
      if (substr($name, 0, 7) == 'expert_') {
        if ($value) {
          list($junk, $uid, $tid) = explode('_', $name);
          $values[] = array('uid' => $uid, 'tid' => $tid);
        }
      }
    }

    // Delete the current values and save the new ones.
    if (!empty($values)) {

      db_delete('faq_expert')->execute();

      $db_query = db_insert('faq_expert')->fields(array('uid', 'tid'));
      foreach ($values as $pair) {
        $db_query->values($pair);
      }
      $db_query->execute();
    }

    parent::submitForm($form, $form_state);
  }

}
