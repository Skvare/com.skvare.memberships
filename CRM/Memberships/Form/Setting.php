<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Setting extends CRM_Core_Form {

  public function buildQuickForm() {

    $this->add('select', 'memberships_financial_type_id', 'Financial type',
      CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
      TRUE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]
    );

    $this->add('select', 'memberships_relationships', 'Relationshiop type',
      CRM_Memberships_Helper::relationshipTypes(),
      TRUE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]);

    $membershipTypes = CRM_Memberships_Helper::membershipTypeCurrentDomain();
    $this->add('select', 'memberships_membership_types', 'Membership Type',
      $membershipTypes, TRUE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]);

    $tags = ['' => '-- select --'] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);
    $this->add('select', 'memberships_tags_full_paid', 'Tag Contact on Full one time payment',
      $tags, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    $this->add('select', 'memberships_tags_partial_paid', 'Tags Contact on partial payment',
      $tags, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
    
    $this->assign('membershipTypes', $membershipTypes);

    $civicrmFields = CRM_Memberships_Helper::getCiviCRMFields();
    $operators = ['' => ts('-operator-')] +
      CRM_Memberships_Helper::getOperators();
    foreach($membershipTypes as $membershipTypeID => $membershipTypeName) {
      $this->add('text', "memberships_type_rule[$membershipTypeID][regular]", "Regular Fee", [], FALSE);
      $this->add('text', "memberships_type_rule[$membershipTypeID][discount]", "Discounted Fee", [], FALSE);
      $this->add('datepicker', "memberships_type_rule[$membershipTypeID][discount_date]", ts('Discount before Date'), [], FALSE, ['time' => TRUE]);

      $this->add('select', "memberships_type_rule[$membershipTypeID][field]", "CiviCRM Field", $civicrmFields, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
      $this->add('select', "memberships_type_rule[$membershipTypeID][operator]", "Operator", $operators, FALSE, ['class' => 'crm-select2', 'placeholder' => ts('- any -')]);
      $this->add('text', "memberships_type_rule[$membershipTypeID][condition]", 'Conitional Value', ['size' => 20]);
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
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

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
    $elementNames = array();
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

    return $defaults;
  }
}
