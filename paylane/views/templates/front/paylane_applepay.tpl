{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.applePayLabel}}</h3>

        <div id="paylane_message" style="display: none">
            <div class="alert alert-danger" id="paylane_message_alert" role="alert"></div>
        </div>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-applepay">
            <div id="apple-pay-active">
                <input type="hidden" name="payment_type" value="ApplePay">
                <input type="hidden" name="payment[additional_information][type]" value="ApplePay">

                <input id="payment_params_token" type="hidden" name="payment[additional_information][token]" value="">
                {l s='You will be redirected to Apple Pay payment sheet after clicking button below. You have to agree to the Terms of Service also.' mod='paylane'}
                <div id="apple-pay-button" class="apple-pay-button" title="Pay with Apple Pay"></div>
            </div>
            <div id="apple-pay-disabled">
                {l s='This payment method is not available for your device' mod='paylane'}
            </div>
        </form>
        <script>
         try {
             var paymentRadios = document.querySelectorAll(".payment-option input[type=\"radio\"]");
             var applePayButton = document.getElementById("apple-pay-button");

             function isApplePayChecked() {
                 var checkedRadioId = null;
                 for(var i=0; i < paymentRadios.length; i++) {
                     if (paymentRadios[i].checked) {
                         checkedRadioId = paymentRadios[i].id;
                         break;
                     }
                 };

                 if (checkedRadioId) {
                     var labelSpan = document.querySelector("label[for=\""+checkedRadioId+"\"] span");
                     return labelSpan && labelSpan.innerText === "{$postParameters.applePayLabel}";
                 } else {
                     return false;
                 }
             }

             function hideApplePayRadioButton() {
                 var container = document.querySelector('.paylane-applepay-wrapper');
                 if (container) {
                     container.style.display = "none";
                 }
             }

             function handleTermsOfService() {
                 var termsOfService = document.getElementById("conditions_to_approve[terms-and-conditions]");
                 if (termsOfService && termsOfService.checked) {
                     applePayButton.style.visibility = "visible";
                 } else {
                     applePayButton.style.visibility = "hidden";
                 }
             }

             PayLane.setPublicApiKey('{$postParameters.public_key_api}');
             for(var i=0; i < paymentRadios.length; i++) {
                 paymentRadios[i].addEventListener('click', function() {
                     if (isApplePayChecked()) {
                         document.querySelector("#payment-confirmation .ps-shown-by-js button").style.display = "none";
                         handleTermsOfService();
                     } else {
                         document.querySelector("#payment-confirmation .ps-shown-by-js button").style.display = "inline-block";
                     }
                 })
             };

             var payLaneApplePayOnAuthorized = function(paymentResult, completion) {
                 try {
                     console.info('Apple Pay result', paymentResult);
                     // perform PayLane sale/authorization on server side
                     var data = JSON.stringify(paymentResult);
                     var headers = {
                         'user-agent': 'Mozilla/4.0 MDN Example',
                         'content-type': 'application/json'
                     };
                     var fetchData = {
                         method: 'POST',
                         headers: headers,
                         body: data
                     };

                     if (paymentResult && paymentResult.card && paymentResult.card.token) {
                         document.getElementById("payment_params_token").value = paymentResult.card.token;
                         completion(ApplePaySession.STATUS_SUCCESS);
                         setTimeout(function() {
                             document.querySelector("form.paylane-applepay").submit();
                         }, 2500);
                     } else {
                         completion(ApplePaySession.STATUS_FAILURE);
                     }
                 } catch (e) {
                     alert(JSON.stringify(e.message));
                 }
             }

             var payLaneApplePayPaymentRequest = {
                 countryCode: "{$postParameters.countryCode}",
                 currencyCode: "{$postParameters.currencyCode}",
                 total: {
                     label: "{$postParameters.paymentDescription}",
                     amount: "{$postParameters.amount}"
                 }
             };

             var payLaneApplePayOnError = function(result) {
                 console.error(result)
             };

             applePayButton.addEventListener('click', function() {
                 try {
                     var applePaySession = PayLane.applePay.createSession(
                         payLaneApplePayPaymentRequest,
                         payLaneApplePayOnAuthorized,
                         payLaneApplePayOnError
                     );
                 } catch (e) {
                     alert(JSON.stringify(e.message));
                 }
             });

             setTimeout(function() {
                 PayLane.applePay.checkAvailability((available) => {
                     if (!available) {
                         hideApplePayRadioButton();
                         document.getElementById("apple-pay-active").style.display = "none";
                         document.getElementById("apple-pay-disabled").style.display = "none";
                         var paylane_message_alert = document.getElementById('paylane_message_alert');
                         paylane_message.style.display = 'block';
                         paylane_message_alert.innerHTML = 'Apple Pay not available';
                         return console.warn('Apple Pay not available');
                     }  else {
                         document.getElementById("apple-pay-active").style.display = "block";
                         document.getElementById("apple-pay-disabled").style.display = "none";
                     }
                 });
             }, 2500);
         } catch (e) {
             console.error(e);
         }
        </script>

    </div>
    <div class="center-block col-lg-3"></div>

{/block}
