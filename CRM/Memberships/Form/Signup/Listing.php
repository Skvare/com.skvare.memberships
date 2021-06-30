<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Signup_Listing extends CRM_Memberships_Form_Registration {
  public function buildQuickForm() {
    $this->_allRelatedContact = CRM_Memberships_Utils::relatedContactsListing($this);
    foreach ($this->_allRelatedContact as $contactID => $contact) {
      $this->add('checkbox', "contacts_{$contactID}", $contact['display_name']. '-'. $contact['membershipstatus']);
    }
    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => E::ts('Next'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $selectedContacts = [];
    foreach ($values as $k => $v) {
      if (strpos($k, 'contacts_') === 0) {
        $contactID = substr($k, 9);
        $selectedContacts[$k] = $contactID;
      }
    }
    if (!empty($selectedContacts)) {
      $this->_selectedRelatedContact = $selectedContacts;
      $this->set('_selectedRelatedContact', $selectedContacts);
      $this->set('_allRelatedContact', $this->_allRelatedContact);
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
    return ts('Contact Selection');
  }


}
