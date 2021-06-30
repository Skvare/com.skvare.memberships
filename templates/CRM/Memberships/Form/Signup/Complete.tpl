<div class="crm-block crm-form-block crm-memberships-group-form-block">
    {include file="CRM/common/WizardHeader.tpl"}

    <div id="thankyou_text" class="crm-section thankyou_text-section">
        {if $isSuccessfull}
            Transaction is successfull.
            <br>
            Transsaction ID : {$trxn_id}
        {else}
            Transaction is Failed.
            {$errorMsg}
        {/if}
    </div>
</div>
