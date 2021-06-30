<?php

use CRM_Memberships_ExtensionUtil as E;

class CRM_Memberships_Helper {

  public static function relationshipTypes() {
    $result = civicrm_api3('RelationshipType', 'get', [
      'sequential' => 1,
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);


    $relationshipTypes = [];
    foreach ($result['values'] as $type) {
      if ($type['label_a_b'] == $type['label_b_a']) {
        $relationshipTypes[$type['id']] = $type['label_a_b'];
      }
      else {
        $relationshipTypes[$type['id'] . '_a_b'] = $type['label_a_b'];
        $relationshipTypes[$type['id'] . '_b_a'] = $type['label_b_a'];
      }
    }

    return $relationshipTypes;
  }

  public static function financialTypes() {
    return CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search');
  }

  public static function getDomainSettings() {
    $extraSettings = civicrm_api3('setting', 'getfields', ['filters' => ['group' => 'com.skvare.memberships']]);
    $existing = civicrm_api3('setting', 'get', ['return' => array_keys($extraSettings['values'])]);
    $defaults = [];
    $domainID = CRM_Core_Config::domainID();
    foreach ($existing['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }

    return $defaults;
  }

  public static function setSettingsConfig($value) {
    // use settings as defined in default domain
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);
    $settings->set('memberships_config_' . $domainID, $value);
  }

  public static function getSettingsConfig() {
    // use settings as defined in default domain
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);

    return $settings->get('memberships_config_' . $domainID);
  }

  public static function getCiviCRMFields() {
    $civicrmFields = CRM_Contact_Form_Search_Builder::fields();
    $cleanFields = [];
    foreach ($civicrmFields as $fieldName => $fieldDetail) {
      $cleanFields[$fieldName] = $fieldDetail['title'];
    }

    return $cleanFields;
  }

  public static function getMembershipFee($membershipTypeID) {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $membershipFeeDetails = $defaultsConfig['memberships_type_rule'][$membershipTypeID];
    if (!empty($membershipFeeDetails['discount_date']) && !empty($membershipFeeDetails['discount'])) {
      $isOverDue = CRM_Utils_Date::overdue($membershipFeeDetails['discount_date']);
      if (!$isOverDue) {
        $discountAmount =  $membershipFeeDetails['regular'] - $membershipFeeDetails['discount'];
        return [$membershipFeeDetails['regular'], $membershipFeeDetails['discount'], $discountAmount];
      }
    }

    return [$membershipFeeDetails['regular'], $membershipFeeDetails['regular'], 0];
  }

  public static function getDefaultFeeOption($contactID) {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $fields = [];
    foreach ($defaultsConfig['memberships_type_rule'] as $membershipTypeID => $membershipConfig) {
      if (in_array($membershipTypeID, $defaultsConfig['memberships_membership_types']) && !empty($membershipConfig['field'])) {
        $fields[$membershipTypeID] = $membershipConfig['field'];
      }

    }
    $fieldsUnique = array_unique($fields);
    $resultContact = civicrm_api3('Contact', 'getsingle', [
      'return' => $fieldsUnique,
      'id' => $contactID,
    ]);

    return self::getMachingType($defaultsConfig, $resultContact);
  }

  public static function membershipType() {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $membershipTypes = CRM_Member_PseudoConstant::membershipType();
    $membershipTypesFiltered = [];
    foreach ($membershipTypes as $id => $name) {
      if (in_array($id, $defaultsConfig['memberships_membership_types'])) {
        $membershipTypesFiltered[$id] = $name;
      }
    }

    return $membershipTypesFiltered;
  }

  public static function getMachingType($defaultsConfig, $resultContact) {
    $retunValue = '';
    foreach ($defaultsConfig['memberships_type_rule'] as $membershipTypeID => $membershipConfig) {
      if (in_array($membershipTypeID, $defaultsConfig['memberships_membership_types']) && !empty($membershipConfig['field'])) {
        $contactFieldValue = $resultContact[$membershipConfig['field']];
        if ($membershipConfig['operator'] == '=') {
          if ($contactFieldValue ==
            $membershipConfig['condition']
          ) {
            $retunValue = $membershipTypeID;
            break;
          }
        }
        elseif ($membershipConfig['operator'] == 'IN') {
          $membershipConfig['field'] = explode(',', $membershipConfig['condition']);
          if (in_array($contactFieldValue,
            $membershipConfig['field'])) {
            $retunValue = $membershipTypeID;
            break;
          }
        }
        if ($membershipConfig['operator'] == '<=>') {
          $membershipConfig['condition'] = explode(',', $membershipConfig['condition']);
          if ($contactFieldValue >=
            $membershipConfig['condition'][0] &&
            $contactFieldValue <= $membershipConfig['condition'][1]
          ) {
            $retunValue = $membershipTypeID;
            break;
          }
        }
      }
    }

    return $retunValue;
  }

  /**
   * Search builder operators.
   *
   * @return array
   */
  public static function getOperators() {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      '<=>' => ts('Between'),
      'IN' => ts('In'),
      'NOT IN' => ts('Not In'),
    ];
  }

}