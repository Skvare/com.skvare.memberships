<?php

use CRM_Memberships_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Memberships_Form_Signup_Complete extends CRM_Memberships_Form_Registration {
  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_status = $this->get('_status');
    foreach ($this->_status as $k => $v) {
      $this->assign($k, $v);
    }
    parent::preProcess();
  }

  public function buildQuickForm() {
    /*
    $this->_allRelatedContact = [];
    $this->_selectedRelatedContact = [];
    $this->_membershipTypeContactMapping = [];
    $this->_params = [];
    */
    $this->controller->reset();
  }

  public function postProcess() {
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Complete');
  }

}
