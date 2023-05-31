<div class="crm-block crm-form-block crm-memberships-group-form-block">
    {if $existingActiveMembershipContacts}
      <div>{ts}The following contacts have active memberships and are not going to be processed again for membership payment.{/ts}</div>
      <table class="selector">
        <tr class="columnheader">
          <th>{ts}Contact Name{/ts}</th>
          <th>{ts}Membership Type{/ts}</th>
          <th>{ts}Start Date{/ts}</th>
          <th>{ts}End Date{/ts}</th>
        </tr>
          {counter start=0 skip=1 print=false}
          {foreach from=$existingActiveMembershipContacts key=contactID item=contact}
            <tr id='rowid{$contactID}' class="{cycle values="odd-row,even-row"}">
              <td>{$contact.display_name}</td>
              <td>{$contact.membership_name}</td>
              <td>{$contact.start_date|crmDate}</td>
              <td>{$contact.end_date|crmDate}</td>
            </tr>
          {/foreach}
      </table>
    <hr><br/>
    {/if}
    {if $membershipTobWithContact}
    <div>{ts}The below are contacts that will be processed for membership payment and upon completion will be active members.{/ts}
      {if $existingActiveMembershipContacts}
        {ts}These children get a sibling discount considering their current active memberships.{/ts}
      {/if}
    </div>
    <table class="selector">
        <tr class="columnheader">
            <th>{ts}Contact Name{/ts}</th>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Discount{/ts}</th>
            <th>{ts}Membership Fee{/ts}</th>
        </tr>
        {counter start=0 skip=1 print=false}
        {foreach from=$membershipTobWithContact key=contactID item=contact}
            <tr id='rowid{$contactID}' class="{cycle values="odd-row,even-row"}">
                <td>{$contact.display_name}</td>
                <td>{$contact.membership_type_name}</td>
                <td>
                    {if $contact.discount} {$contact.discount_name}: &nbsp;{$contact.discount|crmMoney}{/if}

                    {if $contact.fee_amount_sibling}
                        <br/>
                        {ts}Sibling Discount{/ts} : {$contact.fee_amount_sibling|crmMoney}
                    {/if}
                </td>
                <td>
                    {$contact.fee_amount|crmMoney}
                </td>

            </tr>
        {/foreach}
        {if $originalTotalAmount && $otherDiscounts}
        <tr class="{cycle values="odd-row,even-row"}">
            <td colspan="2">&nbsp;</td>
            <td>{ts}Sub Total{/ts}</td>
            <td>{$originalTotalAmount|crmMoney}</td>
        </tr>
            <tr class="{cycle values="odd-row,even-row"}">
                <td colspan="4" style="background: lightgray;">{ts}Additional Discounts{/ts}</td>
            </tr>

        {foreach from=$otherDiscounts item=otherDiscount}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>&nbsp;</td>
                <td>{$otherDiscount.label}</td>
                <td>{$otherDiscount.amount|crmMoney}</td>
                <td>&nbsp;</td>

            </tr>
        {/foreach}
        {/if}
        {if $total_amount}
        <tr class="{cycle values="odd-row,even-row"}">
            <td colspan="3">{ts}Total{/ts}</td><td>{$total_amount|crmMoney}</td>
        </tr>
        {/if}
    </table>
    {else}
    <div class="messages status continue_instructions-section">
        <p>{ts}No Child Available for Membership Signup.{/ts}</p>
    </div>
    {/if}
</div>
{literal}
    <script type="text/javascript">
        CRM.$(function($) {
            $('.crm-memberships-group-form-block').insertAfter('#priceset-div');
            $('.crm-memberships-group-form-block').insertAfter('.amount_display-group');
            $('#priceset-div').hide();
            cj('#pricevalue, #installments, #is_recur').change(function() {
                var total_amount_tmp =  cj('input[name="total_amount"]').val();
                if (total_amount_tmp && cj('#installments').val() && cj('#is_recur:checked').length) {
                    //var installments = cj('#installments').val();
                    var installments = cj('#installments :selected').val()
                    var newAmount = total_amount_tmp / installments;
                    var newAmountFormatted = CRM.formatMoney(newAmount, false, moneyFormat);
                    display(newAmount);
                }

            });

        });
    </script>
{/literal}
