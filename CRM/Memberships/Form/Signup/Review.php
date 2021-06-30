<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Signup_Review extends CRM_Memberships_Form_Registration {
  use CRM_Core_Form_EntityFormTrait;

  public $allMembershipTypeDetails;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_mode = 'live';
    $this->_contactID = $this->getLoggedInUserContactID();
    $this->set('cid', $this->_contactID);
    $this->_params = $this->controller->exportValues('Listing');
    $this->_selectedRelatedContact = $this->get('_selectedRelatedContact');
    $this->_allRelatedContact = $this->get('_allRelatedContact');
    $this->_membershipTypeContactMapping = $this->get('_membershipTypeContactMapping');
    $this->allMembershipTypeDetails = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, [], TRUE);
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    //echo '<pre>';print_r($defaultsConfig); echo '</pre>';
    parent::preProcess();

  }

  public function buildQuickForm() {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    //echo '<pre>$defaultsConfig';print_r($defaultsConfig); echo '</pre>';
    // add form elements
    // add form elements
    $membershipTypes = CRM_Memberships_Helper::membershipTypeCurrentDomain();
    $totalAmount = 0;
    foreach ($this->_membershipTypeContactMapping as $contactID => &$details) {
      [$originalFee, $sellingFee, $discountAmount] =
      CRM_Memberships_Helper::getMembershipFee
      ($details['membership_type_id']);
      $details['membership_type_name'] = $membershipTypes[$details['membership_type_id']];
      $details['original_amount'] = $originalFee;
      $details['fee_amount'] = $sellingFee;
      $totalAmount += $details['fee_amount'];
      $details['display_name'] = $this->_allRelatedContact[$contactID]['display_name'];
      $details['discount'] = $discountAmount;
    }
    $this->assign('contact_details', $this->_membershipTypeContactMapping);
    $this->assign('total_amount', $totalAmount);
    $this->_params['total_amount'] = $totalAmount;
    $this->add('hidden', "total_amount", $totalAmount);

    $this->addPaymentProcessorSelect(TRUE, TRUE, FALSE);
    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, $this->getDefaultPaymentInstrumentId());
    $this->assign('recurProcessor', json_encode($this->_recurPaymentProcessors));
    if (!empty($defaultsConfig['memberships_is_recur'])) {
      CRM_Memberships_Utils::buildRecurForm($this, $defaultsConfig);
    }

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Go Back'),
      ],
      [
        'type' => 'upload',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);
    $this->addFormRule(['CRM_Memberships_Form_Signup_Review', 'formRule'], $this);
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults = $this->getBillingDefaults($defaults);

    return $defaults;
  }

  public static function formRule($params, $files, $self) {
    $errors = [];
    if (!empty($params['payment_processor_id'])) {
      // validate payment instrument (e.g. credit card number)
      CRM_Core_Payment_Form::validatePaymentInstrument($params['payment_processor_id'], $params, $errors, NULL);
    }

    return empty($errors) ? TRUE : $errors;
  }

  public function postProcess() {
    $values = $this->exportValues();
    $formmatedArray = $this->prepreaFormattedData($values);
    CRM_Memberships_Utils::processForm($this, $formmatedArray);
    parent::postProcess();
  }


  /**
   * @param $submittedValues
   * @return array
   */
  protected function prepreaFormattedData($submittedValues) {
    $formmatedArray = [];
    $formmatedArray['contacts'] = $this->_membershipTypeContactMapping;
    $formmatedArray['paymentDetails'] = $submittedValues;

    return $formmatedArray;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }

    return $elementNames;
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Review');
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Membership';
  }
}
