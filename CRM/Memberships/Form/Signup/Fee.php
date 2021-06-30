<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Signup_Fee extends CRM_Memberships_Form_Registration {
  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_params = $this->controller->exportValues('Listing');
    $this->_selectedRelatedContact = $this->get('_selectedRelatedContact');
    $this->_allRelatedContact = $this->get('_allRelatedContact');
    //echo '<pre>'; print_r($this->_allRelatedContact);  echo '</pre>';
    parent::preProcess();

  }
  public function buildQuickForm() {

    // add form elements
    // add form elements
    $membershipTypes = CRM_Memberships_Helper::membershipType();
    foreach ($this->_selectedRelatedContact as $contactID) {
      $this->add('select', "membership_{$contactID}",
        $this->_allRelatedContact[$contactID]['display_name'],
        $membershipTypes, TRUE);
    }

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Go Back'),
      ],
      [
        'type' => 'upload',
        'name' => E::ts('Review'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $defaults = [];
    foreach ($this->_selectedRelatedContact as $contactID) {
      $defaults["membership_{$contactID}"]
        = CRM_Memberships_Helper::getDefaultFeeOption($contactID);
    }

    return $defaults;
  }


  public function postProcess() {
    $values = $this->exportValues();
    $membershipTypeContactMapping = [];
    foreach ($values as $k => $v) {
      if (strpos($k, 'membership_') === 0) {
        $contactID = substr($k, 11);
        $membershipTypeContactMapping[$contactID] = ['membership_type_id' => $v];
      }
    }
    if (!empty($membershipTypeContactMapping)) {
      $this->_membershipTypeContactMapping = $membershipTypeContactMapping;
      $this->set('_membershipTypeContactMapping', $membershipTypeContactMapping);
    }
    parent::postProcess();
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
    $elementNames = array();
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
    return ts('Membership Fee');
  }

}
