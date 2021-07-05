<div class="crm-block crm-form-block crm-memberships-group-form-block">
    <table class="selector">
        <tr class="columnheader">
            <th>{ts}Contact Name{/ts}</th>
            <th>{ts}Membershp Type{/ts}</th>
            <th>{ts}Discount?{/ts}</th>
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
                        Sibling : {$contact.fee_amount_sibling|crmMoney}
                    {/if}
                </td>
                <td>
                    {$contact.fee_amount|crmMoney}
                    {if $contact.discount}
                        &nbsp;({$contact.original_amount|crmMoney})
                    {/if}
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

        });
    </script>
{/literal}
