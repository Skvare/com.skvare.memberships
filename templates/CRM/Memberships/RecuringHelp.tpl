{literal}
<script type="text/javascript">
    CRM.$(function($) {
        cj('#installments, #is_recur').change(function () {
            recurringHelp();
        });
    });
    function recurringHelp() {
        var total_amount_tmp = cj('#pricevalue').data('raw-total');
        if (total_amount_tmp && cj('#installments').val() && cj('#is_recur:checked').length) {
            var installments = cj('#installments :selected').val()
            var newAmount = total_amount_tmp / installments;
            var newAmountFormatted = CRM.formatMoney(newAmount, false, moneyFormat);
            var originalAmount = CRM.formatMoney(total_amount_tmp, false, moneyFormat);
            console.log(originalAmount);
            cj("label[for='is_recur']").html('I want to divide ' + originalAmount + ' amount and pay ' + newAmountFormatted);
            cj('#recurHelp').html('');
        }
        else if (!cj('#is_recur:checked').length) {
            cj("label[for='is_recur']").html('Pay in installments');
        }
    }
    recurringHelp();
</script>
{/literal}

