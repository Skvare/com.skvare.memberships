<div class="crm-block crm-form-block crm-memberships-group-form-block">
    {include file="CRM/common/WizardHeader.tpl"}
    {foreach from=$elementNames item=elementName}
      <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        <div class="content">{$form.$elementName.html}</div>
        <div class="clear"></div>
      </div>
    {/foreach}

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
{literal}
<script type="text/javascript">
    CRM.$(document).ready(function() {
        CRM.$('select[id^="membership_"] option:not(:selected)').attr('disabled', true);
    });
</script>
{/literal}