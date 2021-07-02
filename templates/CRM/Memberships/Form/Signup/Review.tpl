<div class="crm-block crm-form-block crm-memberships-group-form-block">
    {include file="CRM/common/WizardHeader.tpl"}
    {strip}
        <table class="selector">
            <tr class="columnheader">
                <th>{ts}Contact Name{/ts}</th>
                <th>{ts}Membershp Type{/ts}</th>
                <th>{ts}Discount?{/ts}</th>
                <th>{ts}Membership Fee{/ts}</th>
            </tr>
            {counter start=0 skip=1 print=false}
            {foreach from=$contact_details key=contactID item=contact}
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
            <tr class="{cycle values="odd-row,even-row"}">
                <td colspan="3">Total</td><td>{$total_amount|crmMoney}</td>
            </tr>
        </table>
    {/strip}
    {if $form.is_recur}
    {literal}
        <script type="text/javascript">
            CRM.$(document).ready(function() {
                // show block in right order
                cj('#recurHelp').hide();
                var moneyFormat    = '{/literal}{$moneyFormat}{literal}';
                cj('#pricevalue, #installments, #is_recur').change(function() {
                    var total_amount_tmp =  cj('input[name="total_amount"]').val();
                    if (total_amount_tmp && cj('#installments').val() && cj('#is_recur:checked').length) {
                        cj('#recurHelp').show();
                        //var installments = cj('#installments').val();
                        var installments = cj('#installments :selected').val()
                        var newAmount = total_amount_tmp / installments;
                        var newAmountFormatted = CRM.formatMoney(newAmount, false, moneyFormat);
                        cj('#amountperinstallment').html(newAmountFormatted);
                    }
                    else {
                        cj('#recurHelp').hide();
                    }
                });
            });
        </script>
    {/literal}
        <div id="event_recurring_block" class="crm-public-form-item crm-section{$form.is_recur.name}-section">
            <div class="label">&nbsp;</div>
            <div class="content">
                {$form.is_recur.html}
                {$form.is_recur.label} <span id="amountperinstallment"></span> fee {ts}every{/ts}
                {if $is_recur_interval}
                    {$form.frequency_interval.html}
                {/if}
                {if $one_frequency_unit}
                    {$frequency_unit}
                {else}
                    {$form.frequency_unit.html}
                {/if}
                {if $is_recur_installments}
                    <span id="recur_installments_num">
          {ts}for{/ts} {$form.installments.html} {$form.installments.label}
          </span>
                {/if}
                <div id="paymentSummary" class="description"></div>
                <div id="recurHelp" class="description">
                    {ts}Your installments will be processed automatically.{/ts}
                </div>
            </div>
            <div class="clear"></div>
        </div>
    {/if}
    <div>
        {if $form.payment_processor_id.html}
            <fieldset class="crm-public-form-item crm-group payment_options-group">
                <legend>{ts}Payment Options{/ts}</legend>
                <div class="crm-public-form-item crm-section payment_processor-section">
                    <div class="label">{$form.payment_processor_id.label}</div>
                    <div class="content">{$form.payment_processor_id.html}</div>
                    <div class="clear"></div>
                </div>
            </fieldset>
        {/if}

        {if $is_pay_later}
            <fieldset class="crm-public-form-item crm-group pay_later-group">
                <legend>{ts}Payment Options{/ts}</legend>
                <div class="crm-public-form-item crm-section pay_later_receipt-section">
                    <div class="label">&nbsp;</div>
                    <div class="content">
                        [x] {$pay_later_text}
                    </div>
                    <div class="clear"></div>
                </div>
            </fieldset>
        {/if}

        {include file="CRM/Core/BillingBlockWrapper.tpl"}
    </div>

    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
