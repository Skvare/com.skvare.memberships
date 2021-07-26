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
    $params = [
      'sequential' => 1,
      'options' => ['limit' => 1],
    ];
    if (!empty($defaults['memberships_membership_types'])) {
      $params['membership_type_id'] = ['IN' => $defaults['memberships_membership_types']];
    }
    if (!empty($defaults['memberships_type_field']) && !empty($defaults['memberships_type_operator']) && !empty($defaults['memberships_type_condition'])) {
      $params[$defaults['memberships_type_field']] = $defaults['memberships_type_condition'];
    }
    if (!empty($defaults['memberships_membership_allowed_status'])) {
      $params['status_id'] = ['IN' => $defaults['memberships_membership_allowed_status']];
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
                                           $isJccMember = FALSE,
                                           $pageFinancialTypeID = '') {
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
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    $returnField = ["group"];
    if (!empty($defaultsConfig['memberships_financial_discount_group_discount_amount'])) {
      $returnField[] = $defaultsConfig['memberships_financial_discount_group_discount_amount'];
      $returnField[] = $defaultsConfig['memberships_financial_discount_group_discount_type'];
    }
    $contactResult = civicrm_api3('Contact', 'getsingle', [
      'return' => $returnField,
      'id' => $currentUser,
    ]);

    $groupContact = [];
    if (!empty($contactResult['groups'])) {
      $groupContact = explode(',', $contactResult['groups']);
    }

    $originalTotalAmount = $totalAmount;
    $discountOther = [];
    if (!empty($defaultsConfig['memberships_financial_discount_group'])
      && !empty($defaultsConfig['memberships_financial_discount_group_discount_amount'])
      && !empty($defaultsConfig['memberships_financial_discount_group_discount_type'])
    ) {
      if (in_array($defaultsConfig['memberships_financial_discount_group'], $groupContact) &&
        !empty($contactResult[$defaultsConfig['memberships_financial_discount_group_discount_amount']]) &&
        !empty($contactResult[$defaultsConfig['memberships_financial_discount_group_discount_type']])
      ) {
        $type = 1;
        if ($contactResult[$defaultsConfig['memberships_financial_discount_group_discount_type']] == 'fixed_amount') {
          $type = 2;
        }
        [$newTotalAmount, $discountAmount, $newLabel] = self::_calc_discount
        ($originalTotalAmount, $contactResult[$defaultsConfig['memberships_financial_discount_group_discount_amount']], $type, 'Approved Financial Aid');
        $discountOther['1']['amount'] = $discountAmount;
        $discountOther['1']['label'] = $newLabel;
        $discountOther['1']['entity_table'] = 'civicrm_contribution';
        $discountOther['1']['financial_type_id'] = $pageFinancialTypeID;
        $totalAmount = $totalAmount + $discountAmount;
      }
    }

    if (!empty($defaultsConfig['memberships_special_discount_group'])
      && !empty($defaultsConfig['memberships_special_discount_amount'])
      && !empty($defaultsConfig['memberships_special_discount_type'])
    ) {
      if (in_array($defaultsConfig['memberships_special_discount_group'], $groupContact)) {
        $type = $defaultsConfig['memberships_special_discount_type'];
        [$newTotalAmount, $discountAmount, $newLabel] = self::_calc_discount($originalTotalAmount, $defaultsConfig['memberships_special_discount_amount'], $type, 'Subsidy');
        $discountOther['2']['amount'] = $discountAmount;
        $discountOther['2']['label'] = $newLabel;
        $discountOther['2']['entity_table'] = 'civicrm_contribution';
        $discountOther['2']['financial_type_id'] = $pageFinancialTypeID;
        $totalAmount = $totalAmount + $discountAmount;
      }
    }

    return [$totalAmount, $originalTotalAmount, $discountOther];
  }

  /**
   * @param $familyContributionID
   * @param $membershipTobWithContact
   */
  public static function processMembership($familyContributionID, $membershipTobWithContact) {
    $result = civicrm_api3('Contribution', 'getsingle', [
      'return' => ["contribution_status_id", "is_pay_later", 'total_amount', 'receive_date', 'currency', 'contact_id', 'contribution_recur_id'],
      'id' => $familyContributionID,
    ]);

    // create line item and membership record if either of one condition get
    // meet.
    if ($result['contribution_status'] == 'Completed' ||
      ($result['contribution_status'] == 'Pending' && $result['is_pay_later'] == 1) ||
      ($result['contribution_status'] == 'Pending' && !empty($result['contribution_recur_id']))) {
      $isPending = FALSE;
      $isPayLater = NULL;
      $additionalParams = [];
      if ($result['contribution_status'] == 'Pending' && $result['is_pay_later'] == 1) {
        $isPending = TRUE;
        $isPayLater = 1;
        $additionalParams = ['skipStatusCal' => TRUE];
      }
      elseif ($result['contribution_status'] == 'Pending' && !empty($result['contribution_recur_id'])) {
        $isPending = TRUE;
        $isPayLater = NULL;
        $additionalParams = ['skipStatusCal' => TRUE];
      }
      // Prepare the line item array
      $lineItems = CRM_Memberships_Utils::makeLineItemArray2($membershipTobWithContact);
      // Process membership based on payment status.
      CRM_Memberships_Utils::createMemberships($familyContributionID,
        $lineItems, $membershipTobWithContact, $isPending, $isPayLater, $additionalParams);

      // Add line items
      CRM_Memberships_Utils::addLineItems($result, $lineItems);

    }
  }

  /**
   * Calculate either a monetary or percentage discount.
   *
   * @param $amount
   * @param $discountAmount
   * @param int $type
   * @param string $label
   * @param string $currency
   * @return array
   */
  public static function _calc_discount($amount, $discountAmount, $type = 1, $label = '', $currency = 'USD') {
    $fmt_main_amount = CRM_Utils_Money::format($amount, $currency);
    if ($type == '2') {
      $newamount = CRM_Utils_Rule::cleanMoney($amount) - CRM_Utils_Rule::cleanMoney($discountAmount);
      $fmt_discount = CRM_Utils_Money::format($discountAmount, $currency);
      $newlabel = $label . " ({$fmt_discount})";
    }
    else {
      // Percentage
      $newamount = $amount - ($amount * ($discountAmount / 100));
      $newlabel = $label . " ({$discountAmount}% on {$fmt_main_amount})";
    }

    $newamount = round($newamount, 2);
    // Return a formatted string for zero amount.
    // @see http://issues.civicrm.org/jira/browse/CRM-12278
    if ($newamount <= 0) {
      $newamount = '0.00';
    }
    $discountAmount = $newamount - $amount;

    return [$newamount, $discountAmount, $newlabel];
  }

}