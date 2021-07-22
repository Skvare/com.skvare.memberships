<?php

use CRM_Memberships_ExtensionUtil as E;

class CRM_Memberships_Utils {

  public static function relatedContactsListing($form) {
    $group_members = [];

    // Get logged in user Contact ID
    $userID = $form->getLoggedInUserContactID();

    $primary_contact_params = [
      'version' => '3',
      'id' => $userID,
    ];
    // Get all Contact Details for logged in user
    $civi_primary_contact = civicrm_api('Contact', 'getsingle', $primary_contact_params);
    $civi_primary_contact['display_name'] .= ' (you)';
    $group_members[$userID] = $civi_primary_contact;

    //---
    $defaults = CRM_Memberships_Helper::getSettingsConfig();
    $relationships = $defaults['memberships_relationships'];
    $rab = [];
    $rba = [];
    foreach ($relationships as $r) {
      @ list($rType, $dir) = explode("_", $r, 2);
      if ($dir == NULL) {
        $rab[] = $rType;
        $rba[] = $rType;
      }
      elseif ($dir = "a_b") {
        $rab[] = $rType;
      }
      else {
        $rba[] = $rType;
      }
    }

    $contactIds = [$userID];
    if (!empty($rab)) {
      $relationshipsCurrentUserOnBSide = civicrm_api3('Relationship', 'get', [
        'return' => ["contact_id_a"],
        'contact_id_b' => "user_contact_id",
        'is_active' => TRUE,
        'relationship_type_id' => ['IN' => $rab]
      ]);
      foreach ($relationshipsCurrentUserOnBSide['values'] as $rel) {
        $contactIds[] = $rel['contact_id_a'];
      }
    }
    if (!empty($rba)) {
      $relationshipsCurrentUserOnASide = civicrm_api3('Relationship', 'get', [
        'return' => ["contact_id_b"],
        'contact_id_a' => "user_contact_id",
        'is_active' => TRUE,
        'relationship_type_id' => ['IN' => $rba]
      ]);
      foreach ($relationshipsCurrentUserOnASide['values'] as $rel) {
        $contactIds[] = $rel['contact_id_b'];
      }
    }

    //make it a unique list of contacts
    $contactIds = array_unique($contactIds);

    $returnField = ["display_name"];
    if (!empty($defaults['memberships_jcc_field'])) {
      $returnField[] = $defaults['memberships_jcc_field'];
    }
    if (!empty($defaults['memberships_siblings_number'])) {
      $returnField[] = $defaults['memberships_siblings_number'];
    }
    // Get all related Contacts for this user
    foreach ($contactIds as $cid) {
      // only look for parent / child relationship
      $group_members[$cid] = civicrm_api("Contact", "getsingle", [
          'return' => $returnField,
          'version' => 3,
          'contact_id' => $cid,
          'contact_is_deleted' => 0]
      );
      if ($userID == $cid) {
        $group_members[$cid]['display_name'] .= ' (you)';
      }
    }
    foreach ($group_members as $contactID => &$contactDetails) {
      $membershipDetails = self::_get_membership_details($contactID);
      if (!empty($membershipDetails)) {
        list($memberTo, $status) = self::_get_membership_status($membershipDetails);
        $statusColor = 'badge badge-warning';
        $statusMesg = 'Membership ended on';
        if ($status == 'Member') {
          $statusColor = 'badge badge-success';
          $statusMesg = 'current Membership through';
        }
        $membershipName = $membershipDetails['membership_name'];
        $contactDetails['membershipstatus'] = "<div1><span class='{$statusColor}'> $status ( $membershipName : $statusMesg $memberTo)</span></div1>";
      }
      else {
        $contactDetails['membershipstatus'] = "<div1><span class='badge badge-warning'> Non Member</span></div1>";
      }
    }
    if (!empty($defaults['memberships_siblings_number'])) {
      $siblingsNumberOrder = [];
      $normalOrder = [];
      foreach ($group_members as $contactID => &$contactDetails) {
        if (!empty($contactDetails[$defaults['memberships_siblings_number']])) {
          $siblingsNumberOrder[$contactID] = $contactDetails[$defaults['memberships_siblings_number']];
        }
        else {
          $normalOrder[$contactID] = '';
        }
      }
      asort($siblingsNumberOrder);
      $siblingsNumberOrder = $siblingsNumberOrder + $normalOrder;
      $group_members_sorted = [];
      if (!empty($siblingsNumberOrder)) {
        foreach ($siblingsNumberOrder as $contactID => $index) {
          $group_members_sorted[$contactID] = $group_members[$contactID];
        }

        return $group_members_sorted;
      }
    }

    return $group_members;
  }

  public static function _get_membership_details($contactID) {
    if (empty($contactID))
      return [];
    $resultMembership = civicrm_api3('Membership', 'get', [
      //'return' => ["join_date", "end_date", "status_id", "contact_id",
      // "membership_type_id"],
      'contact_id' => $contactID,
      //'membership_type_id' => "1",
      'status_id' => ['IN' => ["New", "Current", "Expired"]],
      'options' => ['sort' => "end_date desc", 'limit' => 1],
    ]);
    $membershipDetails = [];
    if (!empty($resultMembership['values'])) {
      $membershipDetails = reset($resultMembership['values']);
    }

    return $membershipDetails;
  }

  public static function _get_membership_status($membershipDetails) {
    $membershipSince = CRM_Utils_Date::calculateAge($membershipDetails['join_date']);

    $data = '';
    if (array_key_exists('years', $membershipSince)) {
      $data .= $membershipSince['years'] . ' Years';
    }
    if (array_key_exists('months', $membershipSince)) {
      $data .= ' ' . $membershipSince['months'] . ' months';
    }
    if ($data) {
      $data = '<b> ( ' . $data . ' )</b>';
    }
    $memberEnd = '';
    if ($membershipDetails['end_date']) {
      $membershipDetails['end_date'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime($membershipDetails['end_date'])) . " +1 day"));
      $memberEnd = date("n/j/y", strtotime(date("Y-m-d", strtotime($membershipDetails['end_date'])) . " +1 day"));
    }

    $is_Member = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $membershipDetails['status_id'], 'is_current_member', 'id');
    $status = 'Non Member';
    $statusColor = 'coral';
    if ($is_Member) {
      $status = 'Member';
      $statusColor = 'cadetblue';
    }
    $memberSince = CRM_Utils_Date::customFormat($membershipDetails['join_date']) . '' . $data;

    return [$memberEnd, $status];
  }

  public static function buildRecurForm(&$form, $defaultsConfig) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');
    $className = get_class($form);

    $form->assign('is_recur_interval', CRM_Utils_Array::value('memberships_is_recur_interval', $defaultsConfig));
    $form->assign('is_recur_installments', CRM_Utils_Array::value('memberships_is_recur_installments', $defaultsConfig));

    $paymentObject = $form->getVar('_paymentObject');
    $gotText = ts('Your installments will be processed automatically.');
    if ($paymentObject) {
      $form->assign('recurringHelpText', $gotText);
    }

    $form->add('checkbox', 'is_recur', ts('I want pay'), NULL);

    if (!empty($defaultsConfig['memberships_is_recur_interval'])) {
      $form->add('text', 'frequency_interval', ts('Every'), $attributes['frequency_interval'] + ['aria-label' => ts('Every')]);
      $form->addRule('frequency_interval', ts('Frequency must be a whole number (EXAMPLE: Every 1 months).'), 'integer');
    }
    else {
      // make sure frequency_interval is submitted as 1 if given no choice to user.
      $form->add('hidden', 'frequency_interval', 1);
    }

    $unitVals = array_keys($defaultsConfig['memberships_recur_frequency_unit']);

    $unitVals = array_values($unitVals);
    if (count($unitVals) == 1) {
      $form->assign('one_frequency_unit', TRUE);
      $unit = $unitVals[0];
      $form->add('hidden', 'frequency_unit', $unit);
      if (!empty($defaultsConfig['memberships_is_recur_interval'])) {
        $unit .= "(s)";
      }
      $form->assign('frequency_unit', $unit);
    }
    else {
      $form->assign('one_frequency_unit', FALSE);
      $units = [];
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE);
      foreach ($unitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $units[$val] = $frequencyUnits[$val];
          if (!empty($defaultsConfig['memberships_is_recur_interval'])) {
            $units[$val] = "{$frequencyUnits[$val]}(s)";
          }
        }
      }
      $frequencyUnit = &$form->addElement('select', 'frequency_unit', NULL, $units, ['aria-label' => ts('Frequency Unit')]);
    }

    $installmentOption = ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
    $form->addElement('select', 'installments', NULL, $installmentOption, ['aria-label' => ts('installments')]);
    $form->addRule('installments', ts('Number of installments must be a whole number.'), 'integer');
  }

  public static function processForm(&$form, $params) {
    $contactId = CRM_Core_Session::getLoggedInContactID();

    if (!$contactId) {
      throw new API_Exception("You do not have permission to access this api", 1);
    }
    $lineItems = self::makeLineItemArray2($params['contacts']);
    [$payment, $isSuccessfull, $errorMsg] = self::makeRegistrationPayment($params['paymentDetails'], $lineItems, $contactId);
    $template = CRM_Core_Smarty::singleton();
    $template->assign('isSuccessfull', $isSuccessfull);
    $template->assign('errorMsg', $errorMsg);
    $form->_status['isSuccessfull'] = $isSuccessfull;
    $form->_status['errorMsg'] = $errorMsg;
    if ($payment && $isSuccessfull) {
      $template->assign('trxn_id', $payment['trxn_id']);
      $form->_status['trxn_id'] = $payment['trxn_id'];
      self::createMemberships($payment['id'], $lineItems, $params['contacts']);

      // TODO: Not really happy that the confirmation email is unconditionally
      // dependent on a successful payment. This precludes free events. We should
      // have a look and compare the contribution receipt (what we are sending
      // below) to the event confirmation email (e.g.,
      // http://civicrm.stackexchange.com/a/15193/150).
      self::addLineItems($payment, $lineItems);

      /*
      if ($eventInfo['is_email_confirm'] == 1) {
        civicrm_api3('Contribution', 'sendconfirmation', [
          'id' => $payment['id'],
        ]);
      }
      */
    }
    $form->set('_status', $form->_status);
  }

  public static function makeRegistrationPayment($paymentDetails, $lineItems, $contactId) {
    $paymentInstrumentId = civicrm_api3('PaymentProcessor', 'getvalue', [
      'return' => "payment_instrument_id",
      'id' => $paymentDetails['payment_processor_id'],
    ]);
    $bltId = CRM_Core_BAO_LocationType::getBilling();
    $billingAddress = CRM_Contribute_BAO_Contribution::createAddress($paymentDetails, $bltId);
    $defaultsConfig = CRM_Memberships_Helper::getSettingsConfig();
    // Is Installment payment
    $numInstallments = $paymentDetails['installments']?? NULL;
    $frequencyInterval = $paymentDetails['frequency_unit']?? NULL;
    $contributionFirstAmount = $contributionRecurAmount = $paymentDetails['total_amount'];
    $paymentDetails['financial_type_id'] = $defaultsConfig['memberships_financial_type_id'];
    $additionalParams = [];
    if (!empty($paymentDetails['is_recur'])) {
      if ($numInstallments > 1 && !empty($frequencyInterval)) {
        $contributionRecurAmount = round(($paymentDetails['total_amount'] / $numInstallments), 2, PHP_ROUND_HALF_UP);
        $contributionFirstAmount = $contributionRecurAmount;
        // Create Params for Creating the Recurring Contribution Series and Create it
        $contributionRecurParams = [
          'contact_id' => $contactId,
          'frequency_interval' => $paymentDetails['frequency_interval']?? 1,
          'frequency_unit' => $frequencyInterval,
          'installments' => $numInstallments,
          'amount' => $contributionRecurAmount,
          'contribution_status_id' => 'In Progress',
          'currency' => CRM_Core_Config::singleton()->defaultCurrency,
          'payment_processor_id' => $paymentDetails['payment_processor_id'],
          'financial_type_id' => $paymentDetails['financial_type_id'],
        ];
        if (empty($paymentDetails['payment_processor_id'])) {
          $paymentDetails['payment_processor_id'] = 'null';
        }
        /*
        echo '<pre>$contributionRecurParams:'; print_r($contributionRecurParams); echo '</pre>';
        */
        $resultRecur = civicrm_api3('ContributionRecur', 'create', $contributionRecurParams);

        $additionalParams = [
          'contribution_recur_id' => $resultRecur['id'],
          'contributionRecurID' => $resultRecur['id'],
          'is_recur' => 1,
          'contactID' => $contactId,
          'frequency_interval' => $paymentDetails['frequency_interval']?? 1,
          'frequency_unit' => $frequencyInterval,
        ];
      }
    }

    $params = [
      'financial_type_id' => $paymentDetails['financial_type_id'],
      'payment_instrument_id' => $paymentInstrumentId,
      'currency' => CRM_Core_Config::singleton()->defaultCurrency,
      'payment_processor' => $paymentDetails['payment_processor_id'],
      'month' => CRM_Utils_Array::value('M', $paymentDetails['credit_card_exp_date']),
      'year' => CRM_Utils_Array::value('Y', $paymentDetails['credit_card_exp_date']),

      /**
       * Skip line item here is used to keep the contribution
       * from creating one default line item that has no
       * detail in it. Because we cannot pass in all the
       * line-item details to be associated with participant
       * records that don't exist.
       * See FIS-28
       */
      'skipLineItem' => 1,

      'source' => "FOIS Membership Payment",
      'is_test' => 0,
      'address_id' => $billingAddress,
      'total_amount' => $contributionFirstAmount,
      'contact_id' => $contactId,
    ];

    $params = array_merge($params, $paymentDetails);

    // In case these are not provided, fetch contact details so that
    // transactions in the payment processor portal can more easily be matched
    // to CiviCRM contacts.
    $contactFieldNames = ['first_name', 'last_name', 'email',];
    $contactDetails = civicrm_api3('Contact', 'getsingle', [
      'id' => $params['contact_id'],
      'return' => $contactFieldNames,
    ]);

    // SUP-907: Ensure we are not adding more items to the params array than we
    // mean to.
    $params += array_intersect_key($contactDetails, array_flip($contactFieldNames));

    // Hack to circumvent API message about total_amount being a required param.
    // Not sure where in the code base the error is raised, but casting to a
    // string zero when the total_amount isn't truthy seems to address it. The
    // alternative is to branch the code and not run zero-value transactions
    // through Contribution.transact (e.g., instead run them through
    // Contribution.create). Not branching is preferable because it's
    // less code to maintain and because we have assurances that the
    // contributions and related entities will be created in a consistent way.
    if (!CRM_Utils_Array::value('total_amount', $params)) {
      $params['total_amount'] = '0';
    }

    //create the contribution

    $errorMsg = '';
    try {
      $transaction = civicrm_api3('Contribution', 'transact', $params + $additionalParams);
      if (empty($transaction['id'])) {
        if (!empty($transaction['error_message'])) {
          $errorMsg = $transaction['error_message'];
        }
        else {
          $errorMsg = ts('Transaction failed. Please verify all billing fields are correct.');
        }

        return [$transaction, FALSE, $errorMsg];
      }

      return [$transaction['values'][$transaction['id']], TRUE, $errorMsg];
    }
    catch (CiviCRM_API3_Exception $exception) {
      return [[], FALSE, $exception->getMessage()];
    }
  }

  /**
   * This function does some of the close-out functions
   * of the registration payment process.
   *
   * @param $contributionId
   * @param $payingParticipant
   */
  public static function completeRegistrationPayment($contributionId, $payingMembers) {

    civicrm_api3('MembershipPayment', 'create', [
      'sequential' => 1,
      'membership_id' => $payingMembers,
      'contribution_id' => $contributionId,
    ]);

  }

  /**
   * This function loops over the participants and creates
   * them
   *
   * @param array $eventInfo
   *   Information about the event (e.g., output of CRM_Multireg_Utils::getEventInfo())
   * @param array $lineItems
   *   See @CRM_Multireg_Register::makeLineItemArray()
   * @param int|string $registrant
   *   Contact ID of the acting user (i.e., the one registering the participants)
   * @param array $participants
   *   Raw submitted data about the parties to be registered
   * @return mixed
   */
  public static function createMemberships($memberContributionID, array &$lineItems, array $contacts, $isPending = FALSE, $isPayLater = NULL, $additionalParams = []) {
    $isTest = FALSE;
    $numTerms = 1;
    // format the custom field to get used while processing usign CiviCRM core function, not using api here for
    // creating or renewing membership, as this function deal with all the things..
    $customFieldsFormatted = [];
    $contactMembershipID = NULL;
    foreach ($lineItems['members'] as $cid => $data) {
      // get existing Membership record if exist
      if (!empty($contacts['membership_id'])) {
        $contactMembershipID = $contacts['membership_id'];
      }
      else {
        $getContactMembership = [
          'version' => '3',
          'contact_id' => $cid,
          'membership_type_id' => $contacts[$cid]['membership_type_id'],
          'check_permissions' => 0,
        ];

        $contactMembership = civicrm_api('Membership', 'get', $getContactMembership);
        // Existing Membership ID
        $contactMembershipID = $contactMembership['id'] ?? NULL;
      }
      [$membership, $renewalMode, $dates,] = CRM_Member_BAO_Membership::processMembership(
        $cid, $contacts[$cid]['membership_type_id'], $isTest,
        date('YmdHis'), CRM_Core_Session::getLoggedInContactID() ?? NULL,
        $customFieldsFormatted, $numTerms, $contactMembershipID, $isPending,
        NULL, 'FOIS Membership Payment2', $isPayLater, $additionalParams,
        [], NULL, []);
      if (!empty($contactMembershipID)) {
        $lineItems['members'][$cid]['membershipId'] = $contactMembershipID;
      }
      else {
        $lineItems['members'][$cid]['membershipId'] = $membership->id;
        $contactMembershipID = $membership->id;
      }

      // create mapping between Team Dummy contribution id  and team membership id
      $membership_payment = civicrm_api('MembershipPayment', 'get', ['version' => 3, 'contribution_id' => $memberContributionID, 'membership_id' => $contactMembershipID]);
      if (empty($membership_payment['values'])) {
        $result = civicrm_api3('MembershipPayment', 'create', [
          'membership_id' => $contactMembershipID,
          'contribution_id' => $memberContributionID,
        ]);
      }
    }
  }


  /**
   * Helper function to add all the line items to the contribution.
   *
   * @param $contributionId
   * @param $lineItems
   */
  public static function addLineItems($contribution, $lineItems) {

    $defaultParams = [
      'entity_table' => "civicrm_membership",
      'contribution_id' => $contribution['id'],
    ];

    /**
     * This block of code supports the creation of
     * Financial items and EntityFinancialTrxn
     *
     * FIS-28 HACK
     */

    $transaction = NULL;
    $transactions = civicrm_api3("Payment", "get", [
      "contribution_id" => $contribution['id'],
    ]);
    foreach ($transactions['values'] as $trxn) {
      if ($trxn['total_amount'] == $contribution['total_amount']) {
        $transaction = $trxn;
        break;
      }
    }

    $fiParams = [
      //'created_date' => $contribution['receive_date'],
      'transaction_date' => $contribution['receive_date'],
      'currency' => $contribution['currency'],
      'entity_table' => "civicrm_line_item",
      'status_id' => 1,
      'financial_account_id' => $transaction['to_financial_account_id'],
    ];

    /****End FIS-28 HACK ****/

    foreach ($lineItems['members'] as $cid => $contact) {
      $defaultParams['entity_id'] = $contact['membershipId'];
      foreach ($contact['lineItems'] as $lineItem) {
        $params = array_merge($defaultParams, $lineItem);
        $line = civicrm_api3('LineItem', 'create', $params);

        /**
         * None of the following code SHOULD be necessary
         * however, there are bugs in Contribution.transact
         * see: civicrm/api/v3/contribution.php:350
         * The entity transactions are not saved, so we have to create them manually.
         *
         * I followed the code and it doesn't look like there is any way to
         * get the lineitem.create api to do this for us.
         * So we are doing it manually here.
         *
         * Hopefully the Contribution.transact api will get fixed in future
         * versions and we can remove this code.
         * -NTL
         *
         * FIS-28 HACK
         */
        if (!empty($transaction['id'])) {
          $financialItemParams = $fiParams;
          $financialItemParams['contact_id'] = $cid;
          $financialItemParams['description'] = $lineItem['label'];
          $financialItemParams['amount'] = $lineItem['line_total'];
          $financialItemParams['entity_id'] = $line['id'];

          $financialItem = civicrm_api3('FinancialItem', 'create', $financialItemParams);

          civicrm_api3('EntityFinancialTrxn', 'create', [
            'entity_table' => "civicrm_financial_item",
            'entity_id' => $financialItem['id'],
            'financial_trxn_id' => $transaction['id'],
            'amount' => $lineItem['line_total'],
          ]);
        }
        /***** End FIS-28 HACK ****/
      }
    }

    //  Discount Line item
    foreach ($lineItems['otherDiscount'] as $otherDiscount) {
      $defaultParams['entity_table'] = $otherDiscount['entity_table'];
      $params = array_merge($defaultParams, $otherDiscount);
      $line = civicrm_api3('LineItem', 'create', $params);
      if (!empty($transaction['id'])) {
        $financialItemParams = $fiParams;
        $financialItemParams['contact_id'] = $contribution['contact_id'];
        $financialItemParams['description'] = $otherDiscount['label'];
        $financialItemParams['amount'] = $otherDiscount['line_total'];
        $financialItemParams['entity_id'] = $line['id'];

        $financialItem = civicrm_api3('FinancialItem', 'create', $financialItemParams);

        civicrm_api3('EntityFinancialTrxn', 'create', [
          'entity_table' => "civicrm_financial_item",
          'entity_id' => $financialItem['id'],
          'financial_trxn_id' => $transaction['id'],
          'amount' => $otherDiscount['line_total'],
        ]);
      }
    }
  }

  /**
   * @param $contacts
   * @return array
   */
  public static function makeLineItemArray2($contacts) {
    $result = civicrm_api3('PriceField', 'get', [
      'sequential' => 1,
      'price_set_id' => "default_membership_type_amount",
      'api.PriceFieldValue.get' => [],
    ]);

    $priceFields = $result['values']['0']['api.PriceFieldValue.get']['values'];

    $resultContribution = civicrm_api3('PriceField', 'get', [
      'sequential' => 1,
      'price_set_id' => "default_contribution_amount",
      'api.PriceFieldValue.get' => [],
    ]);

    $priceFieldsContribution = reset($resultContribution['values']['0']['api.PriceFieldValue.get']['values']);

    $total = 0;
    foreach ($contacts as $cid => &$contact) {
      //fetch the display name because it may have been updated
      $displayName = $contact['display_name'];
      $subTotal = 0;
      $details = [];
      $details['lineItems'] = [];

      foreach ($priceFields as $priceField) {
        if ($priceField['membership_type_id'] == $contact['membership_type_id']) {
          $priceFieldDetails = self::getPriceFees($contact['membership_type_id']);
          $item = [];
          $item['0'] = [];
          $item['0']['qty'] = 1;
          $item['0']['financial_type_id'] = $priceFieldDetails['financial_type_id'];
          $item['0']['price_field_id'] = $priceFieldDetails['field_id'];
          $item['0']['price_field_value_id'] = $priceFieldDetails['field_value_id'];
          $item['0']['unit_price'] = $contact['fee_amount'];
          $item['0']['line_total'] = $contact['fee_amount'];
          $item['0']['label'] = "{$displayName}: Membership - {$priceField['label']}";

          if (FALSE && !empty($contact['fee_amount_sibling'])) {
            $item['1'] = [];
            $item['1']['qty'] = 1;
            $item['1']['financial_type_id'] = $priceFieldDetails['financial_type_id'];
            $item['1']['price_field_id'] = $priceFieldsContribution['price_field_id'];
            $item['1']['price_field_value_id'] = $priceFieldsContribution['id'];
            $item['1']['unit_price'] = $contact['fee_amount_sibling'];
            $item['1']['line_total'] = $contact['fee_amount_sibling'];
            $item['1']['label'] = "{$displayName}: - Sibling Disount";
          }
          $details['lineItems'] = $item;
          $subTotal += $contact['fee_amount'];
        }
      }
      $details['subtotal'] = $subTotal;
      $subTotals[$cid] = $details;
      $total += $subTotal;
    }
    $session = CRM_Core_Session::singleton();
    $otherDiscounts = $session->get('otherDiscounts');
    $itemOther = [];
    foreach ($otherDiscounts as $index => $otherDiscount) {
      $itemOther[$index] = [];
      $itemOther[$index]['qty'] = 1;
      $itemOther[$index]['financial_type_id'] = $priceFieldDetails['financial_type_id'];
      $itemOther[$index]['unit_price'] = $otherDiscount['amount'];
      $itemOther[$index]['line_total'] = $otherDiscount['amount'];
      $itemOther[$index]['label'] = $otherDiscount['label'];
      $itemOther[$index]['entity_table'] = $otherDiscount['entity_table'];
      $total = $total + $otherDiscount['amount'];
    }

    return ["total" => $total, "members" => $subTotals, 'otherDiscount' => $itemOther];
  }

  /**
   * Creates an array of lineItem data for each participant so we can loop and add participants
   *
   * @param $eventId
   * @param $participants
   * @return array
   */
  public static function makeLineItemArray($contacts) {
    $feesMetadata = self::getMembershipTypeFields($contacts);
    $priceFields = [];
    foreach ($feesMetadata['fields'] as $field) {
      $details = [];
      $details['id'] = str_replace("price_", "", $field['name']);
      $details['label'] = $field['label'];

      if (empty($field['options'])) {
        $details['price'] = $field['price'];
        $details['fieldValueId'] = civicrm_api3('PriceFieldValue', 'getvalue', [
          'return' => "id",
          'price_field_id' => $details['id'],
        ]);
        $details['financialType'] = civicrm_api3('PriceFieldValue', 'getvalue', [
          'return' => "financial_type_id",
          'price_field_id' => $details['id'],
        ]);
        $details['membershipType'] = civicrm_api3('PriceFieldValue', 'getvalue',
          [
            'return' => "membership_type_id",
            'price_field_id' => $details['id'],
          ]);
      }
      else {
        $details['options'] = [];
        foreach ($field['options'] as $option) {
          $details['options'][$option['value']]['price'] = $option['price'];
          $details['options'][$option['value']]['label'] = $option['label'];
          $details['options'][$option['value']]['financialType'] = civicrm_api3('PriceFieldValue', 'getvalue', [
            'return' => "financial_type_id",
            'id' => $option['value'],
          ]);
          try {
            $details['options'][$option['value']]['membershipType'] = civicrm_api3('PriceFieldValue', 'getvalue', [
              'return' => "membership_type_id",
              'id' => $option['value'],
            ]);
          }
          catch (Exception $e) {
            $details['options'][$option['value']]['membershipType'] = 0;
          }
        }
      }

      $details['widget'] = $field['widget'];
      $priceFields[$field['name']] = $details;
    }
    $total = 0;
    $subTotals = [];
    foreach ($contacts as $cid => &$contact) {
      $priceField = self::getPriceFees($contact['membership_type_id']);
      $contact['membershipFee']['price_' . $priceField['field_id']] = $priceField['field_value_id'];
    }

    foreach ($contacts as $cid => $contact2) {
      //fetch the display name because it may have been updated
      $displayName = $contact['display_name'];
      $subTotal = 0;
      $details = [];
      $details['lineItems'] = [];
      foreach ($contact2['membershipFee'] as $fieldName => $value) {

        if (array_key_exists($fieldName, $priceFields)) {
          switch ($priceFields[$fieldName]['widget']) {
            case "crm-render-select":
            case "crm-render-radio":
              $item = [];
              $item['qty'] = 1;
              $item['financial_type_id'] = $priceFields[$fieldName]['options'][$value]['financialType'];
              $item['price_field_id'] = $priceFields[$fieldName]['id'];
              $item['price_field_value_id'] = $value;
              $item['unit_price'] = $priceFields[$fieldName]['options'][$value]['price'];
              $item['line_total'] = $priceFields[$fieldName]['options'][$value]['price'];
              $item['label'] = "{$displayName}: {$priceFields[$fieldName]['label']} - {$priceFields[$fieldName]['options'][$value]['label']}";
              $details['lineItems'][] = $item;

              $subTotal += $priceFields[$fieldName]['options'][$value]['price'];
              break;
            case "crm-render-text":

              $item = [];
              $item['qty'] = $value;
              $item['financial_type_id'] = $priceFields[$fieldName]['financialType'];
              $item['price_field_id'] = $priceFields[$fieldName]['id'];
              $item['price_field_value_id'] = $priceFields[$fieldName]['fieldValueId'];
              $item['unit_price'] = $priceFields[$fieldName]['price'];
              $item['line_total'] = ($value * $priceFields[$fieldName]['price']);
              $item['label'] = "{$displayName}: {$priceFields[$fieldName]['label']}";
              $details['lineItems'][] = $item;

              $subTotal += ($value * $priceFields[$fieldName]['price']);
              break;
            case "crm-render-checkbox":
              foreach ($value as $key => $checked) {
                if ($checked) {
                  $item = [];
                  $item['qty'] = 1;
                  $item['financial_type_id'] = $priceFields[$fieldName]['options'][$key]['financialType'];
                  $item['price_field_id'] = $priceFields[$fieldName]['id'];
                  $item['price_field_value_id'] = $key;
                  $item['unit_price'] = $priceFields[$fieldName]['options'][$key]['price'];
                  $item['line_total'] = $priceFields[$fieldName]['options'][$key]['price'];
                  $item['label'] = "{$displayName}: {$priceFields[$fieldName]['label']} - {$priceFields[$fieldName]['options'][$key]['label']}";
                  $details['lineItems'][] = $item;

                  $subTotal += $priceFields[$fieldName]['options'][$key]['price'];
                }
              }
              break;
          }
        }
      }
      $details['subtotal'] = $subTotal;
      $subTotals[$cid] = $details;
      $total += $subTotal;

    }

    return ["total" => $total, "members" => $subTotals];
  }

  public static function getMembershipTypeFields($contacts) {
    $fees = [];

    if (TRUE) {
      $priceSetId = 2;// self::getEventPriceSet($eventId);

      $result = civicrm_api3("Fieldmetadata", "get", [
        "entity" => "PriceSet",
        "entity_params" => ["id" => $priceSetId],
        "context" => "Angular"
      ]);

      $fees = $result['values'];

      // Filter out fields which should not be displayed due to visibility settings.
      $visibleFields = array_filter($fees['fields'], function ($priceField) {
        return in_array($priceField['visibility'], ['public', 'public_and_listings', 'user']);
      });
      $fees['fields'] = $visibleFields;
    }

    return $fees;
  }

  public static function getPriceFees($membershipTypeID) {
    $query = "select f.id as field_id, fv.id as field_value_id, fv.financial_type_id
      from civicrm_price_set s
      inner join civicrm_price_field f on (s.id = f.price_set_id)
      inner join civicrm_price_field_value fv on (fv.price_field_id = f.id)
      where 
      s.id = 2
      and fv.membership_type_id = $membershipTypeID";
    $dao = CRM_Core_DAO::executeQuery($query);
    $priceField = [];
    while ($dao->fetch()) {
      $priceField['field_id'] = $dao->field_id;
      $priceField['field_value_id'] = $dao->field_value_id;
      $priceField['financial_type_id'] = $dao->financial_type_id;
    }

    return $priceField;
  }
}