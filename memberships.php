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
//function memberships_civicrm_preProcess($formName, &$form) {
//
//}

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
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      $formName = get_class($form);
      if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
        $currentContactID = $form->getLoggedInUserContactID();
        $allRelatedContact = CRM_Memberships_Utils::relatedContactsListing($form);
        $membershipTobWithContact = CRM_Memberships_Helper::getMembershipTobeProcessed($allRelatedContact);
        $isJccMember = FALSE;
        if (!empty($defaults['memberships_jcc_field']) && !empty($allRelatedContact[$currentContactID][$defaults['memberships_jcc_field']])) {
          $isJccMember = TRUE;
        }
        $calculatedAmount = CRM_Memberships_Helper::prepareMemberList
        ($currentContactID, $membershipTobWithContact, $isJccMember);
        $form->assign('membershipTobWithContact', $membershipTobWithContact);
        $form->setVar('processMembershipRecord', $membershipTobWithContact);
        $session = CRM_Core_Session::singleton();
        $session->set('membershipTobWithContact', $membershipTobWithContact);
        $form->assign('total_amount', $calculatedAmount);
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
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Memberships/Preview.tpl']);
      if ($form->_values['is_recur']) {
        $installmentOption = ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
        $form->removeElement('installments');
        $form->addElement('select', 'installments', NULL, $installmentOption, ['aria-label' => ts('installments')]);
      }
    }
  }
  elseif (in_array($formName, ['CRM_Contribute_Form_Contribution_Confirm', 'CRM_Contribute_Form_Contribution_ThankYou'])) {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
      $session = CRM_Core_Session::singleton();
      $membershipTobWithContact = $session->get('membershipTobWithContact');
      $form->assign('membershipTobWithContact', $membershipTobWithContact);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Memberships/Preview.tpl']);

      if ($formName == "CRM_Contribute_Form_Contribution_ThankYou") {
        $session = CRM_Core_Session::singleton();
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
        if (!empty($params['is_recur']) && !empty($params['installments'])) {
          $installmentAmount = $totalAmount / $params['installments'];
          $params['amount'] = $installmentAmount;
          $form->setVar('_params', $params);
          $form->set('amount', $installmentAmount);
        }
      }
    }
    if ($formName == "CRM_Contribute_Form_Contribution_Confirm") {
      $defaults = CRM_Memberships_Helper::getSettingsConfig();
      if (in_array($form->getVar('_id'), $defaults['memberships_contribution_page_id'])) {
        // set the contribution id generated in transaction in session. To be used in other hook ,
        // as $form->_contributionID no exist with $form object
        $session = CRM_Core_Session::singleton();
        $session->set('family_contributionID', $form->_contributionID);
      }
    }
  }
}
