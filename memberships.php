<?php

require_once 'memberships.civix.php';
// phpcs:disable
use CRM_Memberships_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function memberships_civicrm_config(&$config) {
  _memberships_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function memberships_civicrm_xmlMenu(&$files) {
  _memberships_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function memberships_civicrm_install() {
  _memberships_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function memberships_civicrm_postInstall() {
  _memberships_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function memberships_civicrm_uninstall() {
  _memberships_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function memberships_civicrm_enable() {
  _memberships_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function memberships_civicrm_disable() {
  _memberships_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function memberships_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _memberships_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function memberships_civicrm_managed(&$entities) {
  _memberships_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function memberships_civicrm_caseTypes(&$caseTypes) {
  _memberships_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function memberships_civicrm_angularModules(&$angularModules) {
  _memberships_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function memberships_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _memberships_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function memberships_civicrm_entityTypes(&$entityTypes) {
  _memberships_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function memberships_civicrm_themes(&$themes) {
  _memberships_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
function memberships_civicrm_preProcess($formName, &$form) {
  if ($formName == "CRM_Contribute_Form_Contribution_Main") {
    $session = CRM_Core_Session::singleton();
    // set default session values
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      $session = CRM_Core_Session::singleton();
      $session->set('membershipTobWithContact', []);
      $session->set('otherDiscounts', []);
      $session->set('originalTotalAmount', '');
      $session->set('membership_custom_signup', TRUE);
      $session->set('membership_custom_signup_page', $form->getVar('_id'));
    }
    else {
      $session->set('membership_custom_signup', FALSE);
    }
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function memberships_civicrm_navigationMenu(&$menu) {
  _memberships_civix_insert_navigation_menu($menu, 'Administer/CiviMember', [
    'label' => E::ts('Custom Membership Signup Setting'),
    'name' => 'custom_membership_signup_setting',
    'url' => 'civicrm/admin/memberships/setting',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _memberships_civix_navigationMenu($menu);
}

function memberships_civicrm_buildAmount($pageType, &$form, &$amount) {
  if (($pageType == "contribution" || $pageType = 'membership')) {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    // if this page is part of custom setup then prcoess it.
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      $formName = get_class($form);
      if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
        $currentContactID = $form->getLoggedInUserContactID();
        // get related contact of logged in user based on relationship type
        // configured on setting pagg
        $allRelatedContact = CRM_Memberships_Utils::relatedContactsListing($form);
        // get contact having membership records
        $membershipTobWithContact = CRM_Memberships_Helper::getMembershipTobeProcessed($allRelatedContact);
        $_values = $form->getVar('_values');

        // check parent custom field to JCC Discounted Fee.
        $isJccMember = FALSE;
        $pageFinancialTypeID = $_values['financial_type_id'];
        if (!empty($defaults['memberships_jcc_field']) && !empty($allRelatedContact[$currentContactID][$defaults['memberships_jcc_field']])) {
          $isJccMember = TRUE;
        }
        [$calculatedAmount, $originalTotalAmount, $otherDiscount] =
          CRM_Memberships_Helper::prepareMemberList
          ($currentContactID, $membershipTobWithContact, $isJccMember,
            $pageFinancialTypeID);

        // get value to form and session to recall these value on different pages..
        $session = CRM_Core_Session::singleton();
        $form->assign('membershipTobWithContact', $membershipTobWithContact);
        $form->setVar('processMembershipRecord', $membershipTobWithContact);
        $session->set('membershipTobWithContact', $membershipTobWithContact);

        $form->assign('otherDiscounts', $otherDiscount);
        $form->setVar('otherDiscounts', $otherDiscount);
        $session->set('otherDiscounts', $otherDiscount);

        $form->assign('originalTotalAmount', $originalTotalAmount);
        $session->set('originalTotalAmount', $originalTotalAmount);
        $form->assign('total_amount', $calculatedAmount);
        // update the fee amount based on different discount..
        foreach ($amount as $fee_id => &$fee) {
          if (!is_array($fee['options'])) {
            continue;
          }

          foreach ($fee['options'] as $option_id => &$option) {
            if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
              $option['amount'] = $calculatedAmount;
              $option['is_default'] = 1;
            }
          }
        }
      }
    }
  }
}

function memberships_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Contribute_Form_Contribution_Main") {
    /*
    $defaultsCard = [];
    $defaultsCard['credit_card_number'] = '4111111111111111';
    $defaultsCard['cvv2'] = '111';
    $defaultsCard['credit_card_exp_date']['M'] = '5';
    $defaultsCard['credit_card_exp_date']['Y'] = '2024';
    $defaultsCard['credit_card_type'] = 'Visa';
    $form->setDefaults($defaultsCard);
    */

    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    // if recurring setting enabled then show drop down list instead of text
    // field to defined number of installment.
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Memberships/Preview.tpl']);
      if ($form->_values['is_recur']) {
        $installmentOption = ['2' => '2', '3' => '3', '4' => '4'];
        $form->removeElement('installments');
        $form->addElement('select', 'installments', 'installments', $installmentOption, ['aria-label' => ts('installments')]);
        CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Memberships/RecuringHelp.tpl']);
      }
    }
  }
  elseif (in_array($formName, ['CRM_Contribute_Form_Contribution_Confirm', 'CRM_Contribute_Form_Contribution_ThankYou'])) {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      $session = CRM_Core_Session::singleton();
      // Get the value from sesson and show on confirm and thank you page for
      // table listing of children.
      $membershipTobWithContact = $session->get('membershipTobWithContact');
      $form->assign('membershipTobWithContact', $membershipTobWithContact);
      $form->assign('otherDiscounts', $session->get('otherDiscounts'));
      $form->assign('originalTotalAmount', $session->get('originalTotalAmount'));
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Memberships/Preview.tpl']);

      if ($formName == "CRM_Contribute_Form_Contribution_ThankYou") {
        $session = CRM_Core_Session::singleton();
        // Process the Memebership once contribution is processed.
        $familyContributionID = $session->get('family_contributionID');
        if (!empty($familyContributionID)) {
          CRM_Memberships_Helper::processMembership($familyContributionID, $membershipTobWithContact);
        }
      }
    }
  }
}

function memberships_civicrm_postProcess($formName, &$form) {
  if ($formName == "CRM_Contribute_Form_Contribution_Main") {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      if ($form->_values['is_recur']) {
        $params = $form->getVar('_params');
        $totalAmount = $form->get('amount');
        // update the processing amount if recurring payment is enabled.
        if (!empty($params['is_recur']) && !empty($params['installments'])) {
          $installmentAmount = $totalAmount / $params['installments'];
          $params['amount'] = $installmentAmount;
          $form->setVar('_params', $params);
          $form->set('amount', $installmentAmount);
        }
      }
    }
  }
  elseif ($formName == "CRM_Contribute_Form_Contribution_Confirm") {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      // set the contribution id generated in transaction in session. To be used in other hook ,
      // as $form->_contributionID no exist with $form object
      $session = CRM_Core_Session::singleton();
      CRM_Core_Error::debug_var('Confirm $form->_contributionID', $form->_contributionID);
      $session->set('family_contributionID', $form->_contributionID);
    }
  }
}

function memberships_civicrm_pre($op, $objectName, $objectId, &$objectRef ) {
  if ($objectName == 'Contribution') {
    $session = CRM_Core_Session::singleton();
    if ($session->get('membership_custom_signup')) {
      $defaults = CRM_Memberships_Helper::getSettingsConfig();
      // skip default line item , otherwise total amount on line item is more
      // than paid amount.
      if (in_array($session->get('membership_custom_signup_page'), $defaults['memberships_contribution_page_id'])) {
        unset($objectRef['line_item']);
        $objectRef['skipLineItem'] = TRUE;
      }
    }
  }
}

function memberships_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($op != 'delete' && $objectName == 'Contribution') {
    $result = civicrm_api3('Contribution', 'getsingle', [
      'return' => ["contribution_status_id", "is_pay_later", 'total_amount', 'contact_id', 'contribution_recur_id', 'contribution_page_id'],
      'id' => $objectId,
    ]);
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($result['contribution_page_id'], $defaults['memberships_contribution_page_id'])) {
      // Add contact of group if payment is partially paid.
      if ($result['contribution_status'] == 'Completed' && !empty($result['contribution_recur_id'])) {
        if (!empty($defaults['memberships_group_partial_paid'])) {
          civicrm_api3('GroupContact', 'create', [
            'contact_id' => $result['contact_id'],
            'group_id' => $defaults['memberships_group_partial_paid'],
          ]);
        }
        if ($defaults['memberships_tag_partial_paid']) {
          civicrm_api3('EntityTag', 'create', [
            'entity_table' => 'civicrm_contact',
            'entity_id' => $result['contact_id'],
            'tag_id' => $defaults['memberships_tag_partial_paid'],
          ]);
        }
      }
      elseif ($result['contribution_status'] == 'Completed') {
        if (!empty($defaults['memberships_group_full_paid'])) {
          // else add contact to Fully paid group.
          civicrm_api3('GroupContact', 'create', [
            'contact_id' => $result['contact_id'],
            'group_id' => $defaults['memberships_group_full_paid'],
          ]);
        }
        if ($defaults['memberships_tag_full_paid']) {
          civicrm_api3('EntityTag', 'create', [
            'entity_table' => 'civicrm_contact',
            'entity_id' => $result['contact_id'],
            'tag_id' => $defaults['memberships_tag_full_paid'],
          ]);
        }
      }
    }
  }
}

/*
 * Implementation of hook_civicrm_alterMailParams
 */
function memberships_civicrm_alterMailParams(&$params, $context) {
  if ($context == 'messageTemplate') {
    /*
     // Use follwing html in online reciept template above line item element
      {if $custom_membership_signup and $membershipDetails}
         <tr><td colspan="2">{$membershipDetails}</td></tr>
      {/if}
     */
    if (($params['groupName'] == 'msg_tpl_workflow_contribution' && $params['valueName'] == 'contribution_online_receipt')) {
      $defaults = CRM_Memberships_Helper::getSettingsConfig();
      if (in_array($params['tplParams']['contributionPageId'], $defaults['memberships_contribution_page_id'])) {
        $session = CRM_Core_Session::singleton();
        if ($session->get('membershipTobWithContact')) {
          $template = CRM_Core_Smarty::singleton();
          $template->assign('membershipTobWithContact', $session->get('membershipTobWithContact'));
          $template->assign('otherDiscounts', $session->get('otherDiscounts'));
          $template->assign('originalTotalAmount', $session->get('originalTotalAmount'));
          $membershipDetails = $template->fetch('CRM/Memberships/Preview.tpl');
          $params['tplParams']['membershipDetails'] = $membershipDetails;
          $params['tplParams']['custom_membership_signup'] = TRUE;
        }
      }
    }
  }
}