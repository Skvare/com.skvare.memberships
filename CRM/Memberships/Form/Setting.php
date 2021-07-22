<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Setting extends CRM_Core_Form {
  const NUM_DISCOUNT = 6;

  public function buildQuickForm() {
    $groups = ['' => '-- select --'] + CRM_Core_PseudoConstant::nestedGroup();
    $tags = ['' => '-- select --'] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);
    $this->add('select', 'memberships_financial_type_id', 'Financial type',
      CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
      FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]
    );

    $this->add('select', 'memberships_relationships', 'Relationship type',
      CRM_Memberships_Helper::relationshipTypes(),
      TRUE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]);

    $membershipTypes = CRM_Memberships_Helper::membershipTypeCurrentDomain();
    $this->add('select', 'memberships_membership_types', 'Membership Type',
      $membershipTypes, TRUE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]);

    $this->add('select', 'memberships_group_full_paid', 'Add Contact to Group on Full one time payment',
      $groups, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('select', 'memberships_group_partial_paid', 'Add Contact to Group on partial payment',
      $groups, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

    $this->add('select', 'memberships_tag_full_paid', 'Tag Contact on Full one time payment',
      $groups, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('select', 'memberships_tag_partial_paid', 'Tag Contact on partial payment',
      $groups, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

    $this->assign('membershipTypes', $membershipTypes);

    $civicrmFields = CRM_Memberships_Helper::getCiviCRMFields();
    $operators = ['' => ts('-operator-')] +
      CRM_Memberships_Helper::getOperators();

    $attribute = ['class' => 'crm-select2', 'placeholder' => ts('- any -')];
    // <<<<
    $this->add('select', 'memberships_special_discount_group', ts('Special Discount Group'),
      $groups, FALSE, $attribute);
    $this->add('text', "memberships_special_discount_amount", ts('Special Discount Amount'), ['size' =>
      20]);
    $this->add('select', 'memberships_special_discount_type', ts('Special Discount Type'),
      [
        1 => E::ts('Percent'),
        2 => E::ts('Fixed Amount'),
      ],
      FALSE, $attribute);

    $this->add('select', 'memberships_financial_discount_group', ts('Financial assistance/discount'),
      $groups, FALSE, $attribute);
    $this->add('select', "memberships_financial_discount_group_discount_amount",
      "Field for Amount",
      $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('select', "memberships_financial_discount_group_discount_type", "Field for Discount Type",
      $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

    $this->add('select', "memberships_siblings_number",
      "Sort Child using Siblings Number Field",
      $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

    //    ---
    $contributionPage = CRM_Contribute_PseudoConstant::contributionPage();
    $this->add('select', 'memberships_contribution_page_id', ts('Online Contribution Page'),
      $contributionPage, FALSE, $attribute + ['multiple' => 'multiple']);
    $this->add('select', "memberships_jcc_field", "JCC Field Name",
      $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

    $this->add('select', "memberships_type_field", "CiviCRM Field", $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('select', "memberships_type_operator", "Operator", $operators, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('text', "memberships_type_condition", 'Conitional Value', ['size' => 20]);

    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    if (!empty($defaults['memberships_membership_types'])) {
      $this->assign('memberships_membership_types', $defaults['memberships_membership_types']);
      foreach ($defaults['memberships_membership_types'] as $membershipTypeID) {
        $this->add('text', "memberships_type_rule[$membershipTypeID][regular]", "Default Fee", [], FALSE);

        for ($i = 1; $i <= self::NUM_DISCOUNT; $i++) {
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][discount_name]", ts('Discount Name'), ['size' => 20]);
          $this->add('datepicker', "memberships_type_rule[$membershipTypeID][$i][discount_start_date]", ts('Discount Start Date'), [], FALSE, ['time' => TRUE]);
          $this->add('datepicker', "memberships_type_rule[$membershipTypeID][$i][discount_end_date]", ts('Discount End Date'), [], FALSE, ['time' => TRUE]);

          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_1]", ts('Child 1'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_2]", ts('Child 2'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_3]", ts('Child 3'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_4]", ts('Child 4'), ['size' => 5]);

          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_jcc_1]", ts('JCC Child 1'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_jcc_2]", ts('JCC Child 2'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_jcc_3]", ts('JCC Child 3'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][child_jcc_4]", ts('JCC Child 4'), ['size' => 5]);

          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][sibling_1]", ts('Sibling 1'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][sibling_2]", ts('Sibling 2'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][sibling_3]", ts('Sibling 3'), ['size' => 5]);
          $this->add('text', "memberships_type_rule[$membershipTypeID][$i][sibling_4]", ts('Sibling 4'), ['size' => 5]);
        }
        /*
        $this->add('select', "memberships_type_rule[$membershipTypeID][field]", "CiviCRM Field", $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
        $this->add('select', "memberships_type_rule[$membershipTypeID][operator]", "Operator", $operators, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);

        $this->add('text', "memberships_type_rule[$membershipTypeID][condition]", 'Conitional Value', ['size' => 20]);
        */

      }
    }

    $paymentProcessor = CRM_Core_PseudoConstant::paymentProcessor();

    $this->assign('paymentProcessor', $paymentProcessor);
    $this->addCheckBox('memberships_payment_processor', ts('Payment Processor'),
      array_flip($paymentProcessor),
      NULL, NULL, NULL, NULL,
      ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
    );

    $paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('live');
    $recurringPaymentProcessor = [];

    if (!empty($paymentProcessors)) {
      foreach ($paymentProcessors as $id => $processor) {
        if (!empty($processor['is_recur'])) {
          $recurringPaymentProcessor[] = $id;
        }
      }
    }
    if (!empty($recurringPaymentProcessor)) {
      if (count($recurringPaymentProcessor)) {
        $this->assign('recurringPaymentProcessor', $recurringPaymentProcessor);
      }
      $this->addElement('checkbox', 'memberships_is_recur', ts('Enable Installment'), NULL,
        ['onclick' => "showHideByValue('memberships_is_recur',true,'recurFields','table-row','radio',false);"]
      );
      $this->addCheckBox('memberships_recur_frequency_unit', ts('Supported recurring units'),
        CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE),
        NULL, NULL, NULL, NULL,
        ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>'], TRUE
      );
      $this->addElement('checkbox', 'memberships_is_recur_interval', ts('Support recurring intervals'));
      $this->addElement('checkbox', 'memberships_is_recur_installments', ts('Offer installments'));
    }
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    // use settings as defined in default domain
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);
    $membershipConfig = [];
    foreach ($values as $k => $v) {
      if (strpos($k, 'memberships') === 0) {
        $membershipConfig[$k] = $v;
      }
    }
    if (!empty($membershipConfig)) {
      CRM_Memberships_Helper::setSettingsConfig($membershipConfig);
    }

    CRM_Core_Session::setStatus(E::ts('Setting updated successfully'));
    $session = CRM_Core_Session::singleton();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons". These
    // items don't have labels. We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }

    return $elementNames;
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    $defaults = CRM_Memberships_Helper::getSettingsConfig();

    //echo '<pre>';print_r($defaults); echo '</pre>';
    return $defaults;
  }
}
