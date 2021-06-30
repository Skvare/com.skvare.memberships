<div id="help">
  <p>This CiviCRM Custom Membership application setting form.</p>
</div>

<div class="crm-block crm-form-block">

  <table class="form-layout">
    <tr>
      <td class="label">{$form.memberships_financial_type_id.label}</td>
      <td>
          {$form.memberships_financial_type_id.html}
        <div class="description">Financial type will be assigned to payment
          record associated with membership.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.memberships_relationships.label}</td>
      <td>
          {$form.memberships_relationships.html}
        <div class="description">Select Relationship with appropriate direction to pull the list of contact in relationship with logged in user. This Contacts will be available for main listing page.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.memberships_membership_types.label}</td>
      <td>
          {$form.memberships_membership_types.html}<br/>
        <div class="description">Enabled membership types for form.</div>
      </td>
    </tr>

    <tr>
      <td class="label">{$form.memberships_tags_full_paid.label}</td>
      <td>
          {$form.memberships_tags_full_paid.html}<br/>
        <div class="description">Assign Tag to contact on full payment, this tag are required to register for an event.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.memberships_tags_partial_paid.label}</td>
      <td>
          {$form.memberships_tags_partial_paid.html}<br/>
        <div class="description">Assign Tag to contact on partial payment, this tag are required to register for an event.</div>
      </td>
    </tr>
      {foreach from=$membershipTypes key=type_id item=label}

        <tr><td colspan="2">
            <fieldset>
              <legend>{$label}</legend>
              <table class="form-layout">
                <tr>
                  <td class="label">{$form.memberships_type_rule.$type_id.regular.label}</td>
                  <td>
                      {$form.memberships_type_rule.$type_id.regular.html}<br/>
                    <span class="description">Regular Membersihp Fee.</span>
                  </td>
                </tr>
                <tr>
                  <td class="label">{$form.memberships_type_rule.$type_id.discount.label}</td>
                  <td>
                      {$form.memberships_type_rule.$type_id.discount.html}<br/>
                    <span class="description">Discounted Membership Fee.</span>
                  </td>
                </tr>
                <tr>
                  <td class="label">{$form.memberships_type_rule.$type_id.discount_date.label}</td>
                  <td>
                      {$form.memberships_type_rule.$type_id.discount_date.html}<br/>
                  </td>
                </tr>
              </table>
              <table class="form-layout">
                <tr>
                  <td class="label">Set Condition</td>
                  <td>{$form.memberships_type_rule.$type_id.field.html}</td>
                  <td>{$form.memberships_type_rule.$type_id.operator.html}</td>
                  <td>{$form.memberships_type_rule.$type_id.condition.html}
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td colspan="3">
                    <span class="description">
                    In case Custom Field, use option value for comparting value.
                    <br/>
                      For In , Between Operator, use comma between the value.</span>
                  </td>
                </tr>
              </table>
            </fieldset>
          </td></tr>
      {/foreach}
  </table>
  <fieldset>
    <legend>Processor</legend>
    <table>
    <tr class="crm-manage-fee-form-block-payment_processor">
      <td class="label" style="width: 17%;">{$form.memberships_payment_processor.label}</td>
      <td>
          {$form.memberships_payment_processor.html}<br/>
      </td>
    </tr>

    <tr>
      <td colspan="2">
          {if true}
          {literal}
            <script type="text/javascript">
                var paymentProcessorMapper = [];
                {/literal}
                {foreach from=$recurringPaymentProcessor item="paymentProcessor" key="index"}{literal}
                paymentProcessorMapper[{/literal}{$index}{literal}] = '{/literal}{$paymentProcessor}{literal}';
                {/literal}
                {/foreach}
                {literal}
                CRM.$(document).ready(function() {
                    // show block in right order
                    // CRM.$('#recurringFields').insertAfter('#priceSet');
                    // show/hide recurring block
                    CRM.$('.crm-manage-fee-form-block-payment_processor input[type="checkbox"]').change(function(){
                        showRecurring( checked_payment_processors() );
                    });
                    showRecurring( checked_payment_processors() );
                });
                function checked_payment_processors() {
                    var ids = [];
                    CRM.$('.crm-manage-fee-form-block-payment_processor input[type="checkbox"]').each(function () {
                        if (CRM.$(this).prop('checked')) {
                            var id = CRM.$(this).attr('id').split('_')[3];
                            ids.push(id);
                        }
                    });
                    return ids;
                }

                function showRecurring( paymentProcessorIds ) {
                    //console.log(paymentProcessorIds);
                    console.log(paymentProcessorMapper);
                    var display = true;
                    cj.each(paymentProcessorIds, function (k, id) {
                        console.log('id:' + id);
                        if (cj.inArray(id, paymentProcessorMapper) == -1) {
                            display = false;
                        }
                    });
                    console.log('display:' + display);
                    if (display) {
                        cj('#recurringContribution').show();
                        cj('#recurFields').show();
                    } else {
                        if (cj('#memberships_is_recur').prop('checked')) {
                            cj('#memberships_is_recur').prop('checked', false);
                            cj('#recurFields').hide();
                        }
                        cj('#recurringContribution').hide();
                    }
                }
            </script>
          {/literal}
            <div id="recurringFields">
              <table class="form-layout-compressed">
                <tr id="recurringContribution" class="crm-event-form-block-is_recur"><td scope="row" class="label" width="20%">{$form.memberships_is_recur.label}</td>
                  <td>{$form.memberships_is_recur.html}<br />
                    <span class="description">{ts}Check this box if you want to give users the option to make recurring membership payment. This feature requires that you use a payment processor which supports recurring billing / subscriptions functionality.{/ts} {docURL page="user/contributions/payment-processors"}</span>
                  </td>
                </tr>
                <tr id="recurFields" class="crm-event-form-block-recurFields"><td>&nbsp;</td>
                  <td>
                    <table class="form-layout-compressed">
                      <tr class="crm-event-form-block-recur_frequency_unit"><td scope="row" class="label">{$form.memberships_recur_frequency_unit.label}<span class="crm-marker" title="This field is required.">*</span></td>
                        <td>{$form.memberships_recur_frequency_unit.html}<br />
                          <span class="description">{ts}Select recurring units supported for recurring payments.{/ts}</span></td>
                      </tr>
                      <tr class="crm-event-form-block-is_recur_interval"><td scope="row" class="label">{$form.memberships_is_recur_interval.label}</td>
                        <td>{$form.memberships_is_recur_interval.html}<br />
                          <span class="description">{ts}Can users also set an interval (e.g. every '3' months)?{/ts}</span></td>
                      </tr>
                      <tr class="crm-event-form-block-is_recur_installments"><td scope="row" class="label">{$form.memberships_is_recur_installments.label}</td>
                        <td>{$form.memberships_is_recur_installments.html}<br />
                          <span class="description">{ts}Give the user a choice of installments (e.g. donate every month for 6 months)? If not, recurring donations will continue indefinitely.{/ts}</span></td>
                      </tr>
                    </table>
                  </td>
                </tr>

              </table>
            </div>
              {if $form.memberships_is_recur}
                  {include file="CRM/common/showHideByFieldValue.tpl"
                  trigger_field_id    ="memberships_is_recur"
                  trigger_value       ="true"
                  target_element_id   ="recurFields"
                  target_element_type ="table-row"
                  field_type          ="radio"
                  invert              = "false"
                  }
              {/if}
          {/if}
      </td>

    </tr>



  </table>
  </fieldset>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
