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

  public static function getMembershipFee($membershipTypeID, $contactID,
                                          $currentUserID, $childNumber,
                                          $isJccMember = FALSE) {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $membershipFeeDetails = $defaultsConfig['memberships_type_rule'][$membershipTypeID];
    $currentDate = strtotime(date('YmdHis'));
    //$currentDate = strtotime("15 September 2021");
    //$currentDate = strtotime("15 January 2022");
    //$currentDate = strtotime("15 March 2022");
    $childNumber = ($childNumber >= 4) ? 4 : $childNumber;
    $defaultFee = $sellFee = $membershipFeeDetails['regular'];
    $siblingDiscountFee = 0;
    $discountName = '';
    if ($contactID != $currentUserID) {
      foreach ($membershipFeeDetails as $discountDetails) {
        if ($isJccMember && is_array($discountDetails) && !empty($discountDetails['child_jcc_' . $childNumber])) {
          $discountName = $discountDetails['discount_name'];
          $startDate = self::cleanDate($discountDetails['discount_start_date']);
          $endDate = self::cleanDate($discountDetails['discount_end_date']);
          if ($startDate && $endDate && ($currentDate >= $startDate && $currentDate <= $endDate)) {
            $sellFee = $discountDetails['child_jcc_' . $childNumber];
            break;
          }
          elseif (!empty($startDate) && empty($endDate) && ($currentDate >= $startDate)) {
            $sellFee = $discountDetails['child_jcc_' . $childNumber];
            break;
          }
          elseif (empty($startDate) && !empty($endDate) && ($currentDate <= $endDate)) {
            $sellFee = $discountDetails['child_jcc_' . $childNumber];
            break;
          }
        }
        elseif (is_array($discountDetails) && !empty($discountDetails['child_' . $childNumber])) {
          $discountName = $discountDetails['discount_name'];
          $startDate = self::cleanDate($discountDetails['discount_start_date']);
          $endDate = self::cleanDate($discountDetails['discount_end_date']);
          if ($startDate && $endDate && ($currentDate >= $startDate && $currentDate <= $endDate)) {
            $sellFee = $discountDetails['child_' . $childNumber];
            break;
          }
          elseif (!empty($startDate) && empty($endDate) && ($currentDate >= $startDate)) {
            $sellFee = $discountDetails['child_' . $childNumber];
            break;
          }
          elseif (empty($startDate) && !empty($endDate) && ($currentDate <= $endDate)) {
            $sellFee = $discountDetails['child_' . $childNumber];
            break;
          }
        }
      }

      foreach ($membershipFeeDetails as $discountDetails) {
        if (is_array($discountDetails) && !empty($discountDetails['sibling_' . $childNumber])) {
          $startDate = self::cleanDate($discountDetails['discount_start_date']);
          $endDate = self::cleanDate($discountDetails['discount_end_date']);
          if ($startDate && $endDate && ($currentDate >= $startDate && $currentDate <= $endDate)) {
            $siblingDiscountFee = $discountDetails['sibling_' . $childNumber];
            break;
          }
          elseif (!empty($startDate) && empty($endDate) && ($currentDate >= $startDate)) {
            $siblingDiscountFee = $discountDetails['sibling_' . $childNumber];
            break;
          }
          elseif (empty($startDate) && !empty($endDate) && ($currentDate <= $endDate)) {
            $siblingDiscountFee = $discountDetails['sibling_' . $childNumber];
            break;
          }
        }
      }
    }
    $discountAmount = $defaultFee - $sellFee;
    $sellFee = $sellFee - $siblingDiscountFee;

    return [$defaultFee, $sellFee, $discountAmount, $siblingDiscountFee, $discountName];
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

  public static function membershipTypeCurrentDomain() {
    $result = civicrm_api3('MembershipType', 'get', [
      'sequential' => 1,
      'return' => ["id", "name"],
    ]);
    $membershipType = [];
    foreach ($result['values'] as $details) {
      $membershipType[$details['id']] = $details['name'];
    }

    return $membershipType;
  }

  public static function membershipType() {
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $membershipTypes = CRM_Memberships_Helper::membershipTypeCurrentDomain();
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

  public static function cleanDate($date) {
    if (empty($date))
      return;
    $mysqlDate = CRM_Utils_Date::isoToMysql($date);

    return $mysqlDate = strtotime($mysqlDate);
  }

  public static function getMembershipTobeProcessed($allRelatedContact) {
    $membershipContact = [];
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    $returnField = ["display_name"];
    if (!empty($defaults['memberships_jcc_field'])) {
      $returnField[] = $defaults['memberships_jcc_field'];
    }
    $params = [
      'sequential' => 1,
      'options' => ['limit' => 1],
    ];
    if (!empty($defaults['memberships_type_field']) && !empty($defaults['memberships_type_operator']) && !empty($defaults['memberships_type_condition'])) {
      $params[$defaults['memberships_type_field']] = $defaults['memberships_type_condition'];
    }

    foreach ($allRelatedContact as $cid => $contactDetails) {
      $params['contact_id'] = $cid;
      $result = civicrm_api3('Membership', 'get', $params);
      if (!empty($result['values'])) {
        $membershipContact[$cid] = $contactDetails;
        $membership = reset($result['values']);
        $membershipContact[$cid]['membership_id'] = $membership['id'];
        $membershipContact[$cid]['membership_name'] = $membership['membership_name'];
        $membershipContact[$cid]['membership_type_id'] = $membership['membership_type_id'];
      }
    }

    return $membershipContact;
  }

  public static function prepareMemberList($currentUser,
                                           &$membershipTobWithContact,
                                           $isJccMember) {
    $membershipTypes = CRM_Memberships_Helper::membershipTypeCurrentDomain();
    $totalAmount = 0;
    $childNumber = 0;
    $membershipTypeContactMapping = [];
    foreach ($membershipTobWithContact as $contactID => &$details) {
      if ($contactID != $currentUser) {
        $childNumber++;
      }
      [$originalFee, $sellingFee, $discountAmount, $siblingDiscountFee, $discountName] =
        CRM_Memberships_Helper::getMembershipFee($details['membership_type_id'], $contactID, $currentUser, $childNumber, $isJccMember);
      $details['membership_type_name'] = $membershipTypes[$details['membership_type_id']];
      $details['original_amount'] = $originalFee;
      $details['fee_amount_sibling'] = $siblingDiscountFee;

      $totalAmount += $sellingFee;
      $details['fee_amount'] = $sellingFee;
      $details['discount'] = $discountAmount;
      $details['discount_name'] = $discountName;
    }

    return $totalAmount;
  }

  public static function processMembership($familyContributionID,
                                           $membershipTobWithContact) {
    $result = civicrm_api3('Contribution', 'getsingle', [
      'return' => ["contribution_status_id", "is_pay_later", 'total_amount', 'receive_date', 'currency'],
      'id' => $familyContributionID,
    ]);
    if ($result['contribution_status'] == 'Completed' || ($result['contribution_status'] == 'Pending' && $result['is_pay_later'] == 1)) {
      $isPending = FALSE;
      $isPayLater = NULL;
      $additionalParams = [];
      if ($result['contribution_status'] == 'Pending' && $result['is_pay_later'] == 1) {
        $isPending = TRUE;
        $isPayLater = 1;
        $additionalParams = ['skipStatusCal' => TRUE];
      }
      $lineItems = CRM_Memberships_Utils::makeLineItemArray2($membershipTobWithContact);
      CRM_Memberships_Utils::createMemberships($familyContributionID,
        $lineItems, $membershipTobWithContact, $isPending, $isPayLater, $additionalParams);

      CRM_Memberships_Utils::addLineItems($result, $lineItems);
    }
  }
}