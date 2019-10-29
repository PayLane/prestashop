{*
 *  prestashop 1.6
 *}
<script>
 setTimeout(function() {
     var paymentOptions = document.querySelector(".payment-option");

     if (paymentOptions !== undefined && paymentOptions.length === undefined) { // only one payment method active
         var inputId = paymentOptions.id.replace('-container', '');
         document.getElementById(inputId).checked = true;
         var additionalInformation = document.getElementById(inputId + '-additional-information');
         if (additionalInformation) {
             additionalInformation.style.display = "block";
         }
         var formObj = document.getElementById('pay-with-' + inputId + '-form');
         if (formObj) {
             formObj.style.display = "block";
         }
     }
 }, 500);
</script>
