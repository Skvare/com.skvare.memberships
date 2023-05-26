{if $recurringPaymentProcessor}
{literal}
<script type="text/javascript">
  CRM.$(document).ready(function() {
    // show block in right order
    CRM.$('.crm-event-form-block-is_recur_installments_number').insertAfter('.crm-contribution-form-block-is_recur_installments');
  });
</script>
{/literal}
<table style="display: none;">
  <tr class="crm-event-form-block-is_recur_installments_number"><td scope="row" class="label">{$form.is_recur_installments_number.label}</td>
    <td>{$form.is_recur_installments_number.html}<br />
      <span class="description">{ts}Restrict total number of installments.{/ts}</span></td>
  </tr>
</table>
{/if}
