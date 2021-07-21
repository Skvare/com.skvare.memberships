<div class="crm-block crm-form-block crm-memberships-group-form-block">
    <table class="selector">
        <tr class="columnheader">
            <th>{ts}Contact Name{/ts}</th>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Rate{/ts}</th>
            <th>{ts}Membership Fee{/ts}</th>
        </tr>
        {counter start=0 skip=1 print=false}
        {foreach from=$membershipTobWithContact key=contactID item=contact}
            <tr id='rowid{$contactID}' class="{cycle values="odd-row,even-row"}">
                <td>{$contact.display_name}</td>
                <td>{$contact.membership_type_name}</td>
                <td>
                    {if $contact.discount} {$contact.discount_name}: &nbsp;
                        {$contact.discount|crmMoney}{else}-{/if}

                    {if $contact.fee_amount_sibling}
                        <br/>
                        Sibling Discount : {$contact.fee_amount_sibling|crmMoney}
                    {/if}
                </td>
                <td>
                    {$contact.fee_amount|crmMoney}
                </td>

            </tr>
        {/foreach}
        {if $originalTotalAmount && $otherDiscounts}
        <tr class="{cycle values="odd-row,even-row"}">
            <td colspan="3">Additional Discounts</td><td>{$originalTotalAmount|crmMoney}</td>
        </tr>
        {/if}
        {foreach from=$otherDiscounts item=otherDiscount}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>-</td>
                <td>{$otherDiscount.label}</td>
                <td>
                    {$otherDiscount.amount|crmMoney}
                </td>
                <td>
                    -
                </td>

            </tr>
        {/foreach}
        {if $total_amount}
        <tr class="{cycle values="odd-row,even-row"}">
            <td colspan="3">Total</td><td>{$total_amount|crmMoney}</td>
        </tr>
        {/if}
    </table>
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