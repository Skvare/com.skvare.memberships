<div class="crm-block crm-form-block crm-memberships-group-form-block">
    {include file="CRM/common/WizardHeader.tpl"}
    <div class="help">
        <span class="bg-info note-text text-info">
        <p>{ts}Here is list of parent plus child contacts, it show current membership of each contact if present.{/ts}</p>
        </span>
    </div>
    <div><strong>Who would you like to pay for membership:</strong></div>

    {foreach from=$elementNames item=elementName}
      <div class="crm-section">
        <div class="label">{$form.$elementName.html}</div>
        <div class="content">{$form.$elementName.label}</div>
        <div class="clear"></div>
      </div>
    {/foreach}

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
