{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">
        <h2>{$postParameters['paymentMethodLabel']|escape:'htmlall':'UTF-8'}</h2>

        <div id="paylane_message" style="display: none">
            <div class="alert alert-danger" id="paylane_message_alert" role="alert"></div>
        </div>

        <form action="{$postParameters['return_url']}" method="POST" id="paylane-form-credit-card" class="paylane-form paylane-credit-card" name="paylane-form-credit-card">
            <input type="hidden" name="payment[additional_information][type]" value="CreditCard">
            <input type="hidden" name="payment[additional_information][back_url]" value="{$postParameters['3dsreturn_url']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="payment_type" value="CreditCard">
            <input id="paylane-payment-token" type="hidden" name="payment[additional_information][token]" value="">
            <input id="paylane-payment-creditCardString" type="hidden" name="payment[additional_information][creditCardString]" value="">
            <input id="paylane-payment-credit-card-validate" type="hidden" value="1">
            <input type="hidden" name="amount" value="{$postParameters['amount']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="currency_code" value="{$postParameters['currency']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="back_url" value="{$postParameters['return_url']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="description" value="{$postParameters['transaction_id']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="hash" value="{$postParameters['hash']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="currency" value="{$postParameters['currency']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="transaction_description" value="{$postParameters['transaction_description']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="language" value="{$postParameters['language']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_name" value="{$postParameters['customer_firstname']|escape:'htmlall':'UTF-8'} {$postParameters['customer_lastname']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_email" value="{$postParameters['customer_email']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_address" value="{$postParameters['customer_address']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_zip" value="{$postParameters['customer_zip']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_city" value="{$postParameters['customer_city']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="customer_country" value="{$postParameters['customer_country']|escape:'htmlall':'UTF-8'}" />
            {if !$postParameters.isSingleClickActive || ($postParameters.isSingleClickActive && $postParameters.isFirstOrder)}
                {if $postParameters.creditCardsArray}
                    <div class="field">
                        <label for="payment_params:id_sale" class="control-label">{l s='Choose your credit card' mod='paylane'}</label>
                        <div class="input-box">
                            <select id="payment_params:id_sale" data-paylane="id-sale" class="form-control" name="payment[additional_information][id_sale]" onchange="dropdownHideOrShowForm()">
                                {foreach from=$postParameters.creditCardsArray item=creditCardSingle}
                                    <option value={$creditCardSingle.id_sale}|{$creditCardSingle.credit_card_number}>{$creditCardSingle.credit_card_number}</option>
                                {/foreach}
                                <option value="addNewCard">{l s='Add new credit card' mod='paylane'}</option>
                            </select>
                        </div>
                    </div>
                {/if}
                <div id="payment_form_paylane_creditcard">
                    <div class="form-list">
                        <div class="form-group" id="fg-payment_params:card_numer">
                            <label for="payment_params:card_numer" class="control-label">{l s='Card number' mod='paylane'}</label>
                            <div class="input-box">
                                <input type="text" id="payment_params:card_numer" data-paylane="cc-number" size="19" class="form-control">
                            </div>
                        </div>
                        <div class="form-group" id="fg-payment_params:name_on_card">
                            <label for="payment_params:name_on_card" class="control-label">{l s='Name on card' mod='paylane'}</label>
                            <div class="input-box">
                                <input type="text" id="payment_params:name_on_card" data-paylane="cc-name-on-card" size="50" class="form-control">
                            </div>
                        </div>
                        <div class="form-group" id="fg-payment_params:expiration_month">
                            <label for="payment_params:expiration_month" class="control-label">{l s='Expiration month' mod='paylane'}</label>
                            <div class="input-box">
                                <select id="payment_params:expiration_month" data-paylane="cc-expiry-month" class="form-control form-control-select">
                                    {foreach from=$postParameters.months item=month}
                                        <option value="{$month}">{$month}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="fg-payment_params:expiration_month">
                            <label for="payment_params:expiration_year" class="control-label">{l s='Expiration year' mod='paylane'}</label>
                            <div class="input-box">
                                <select id="payment_params:expiration_year" data-paylane="cc-expiry-year" class="form-control form-control-select">
                                    {foreach from=$postParameters.years item=year}
                                        <option value="{$year}">{$year}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="fg-payment_params:card_code">
                            <label for="payment_params:card_code" class="control-label">{l s='CVV/CVC' mod='paylane'}</label>
                            <div class="input-box">
                                <input type="text" id="payment_params:card_code" data-paylane="cc-cvv" size="4" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            {else}
                {if $postParameters.authorizeId}
                    <input type="hidden" id="payment_params:authorization_id" name="payment[additional_information][authorization_id]" value="{$postParameters.authorizeId}">
                    {l s='User authorized earlier - no additional data required' mod='paylane'}
                {else}
                    <input type="hidden" id="payment_params:sale_id" name="payment[additional_information][sale_id]" value="{$postParameters.lastSaleId}">
                    {l s='Using Single-click method - get card data from earlier order' mod='paylane'}
                {/if}
            {/if}

            <div class="cart_navigation">
                    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="btn btn-primary">{l s='Other payment methods' mod='paylane'}</a>
                    <button class="btn btn-primary float-xs-right" type="submit" id="button_paylane">{l s='Confirm order' mod='paylane'}</button>
            </div>

            <div class="row">&nbsp;</div>
        </form>

        <div class="center-block col-lg-3"></div>
    </div>
    {if $postParameters.creditCardsArray}
        <script>
         document.getElementById("paylane-payment-credit-card-validate").value = 0;
        </script>
    {/if}

    <script>

     try {
         document.getElementById("button_paylane").addEventListener("click", paylane_validate);

         function paylane_validate(e) {
             if (document.getElementById("paylane-payment-credit-card-validate").value === '0') {
                document['paylane-form-credit-card'].submit();
                return;
             }

             e.preventDefault();
             var payLaneInputs = document.querySelectorAll('[data-paylane]');
             var paylane_cc_error = {};
             for (var i=0; i < payLaneInputs.length; i++) {
                 var paylaneInput = payLaneInputs[i];
                 paylaneInput.addEventListener('focus', function(e) {
                     var el = document.getElementById('fg-' + e.target.id);
                     if (el) {
                         el.classList.remove("has-error");
                     }
                 });
                 var el = document.getElementById('fg-' + paylaneInput.id);
                 if (paylaneInput.value.trim() === '' && el) {
                     el.classList.add("has-error");
                     paylane_cc_error[paylaneInput.id] = true;
                 } else if (el) {
                     el.classList.remove("has-error");
                     delete paylane_cc_error[paylaneInput.id];
                 }
             }

             if (Object.keys(paylane_cc_error).length === 0) {
                 paylane_generateToken();
             }
         }

         document.addEventListener('DOMContentLoaded', function() {
             try{
                 var val = document.getElementById("payment_params:id_sale").value;
                 if(val!="addNewCard"){
                     var creditCardForm = document.getElementById("payment_form_paylane_creditcard");
                     creditCardForm.classList.add('paylane-credit-card-hide-form');
                     document.getElementById("paylane-payment-credit-card-validate").value = 0
                 }
             }catch(e){
                 console.log("Single-click not available");
             }
         }, false);

         function dropdownHideOrShowForm() {
             var val = document.getElementById("payment_params:id_sale").value;
             var creditCardForm = document.getElementById("payment_form_paylane_creditcard");
             if(val != 'addNewCard') {
                 creditCardForm.classList.add('paylane-credit-card-hide-form');
                 document.getElementById("paylane-payment-credit-card-validate").value = 0;
             }else{
                 creditCardForm.classList.remove('paylane-credit-card-hide-form');
                 document.getElementById("paylane-payment-credit-card-validate").value = 1;
             }
         }

         function paylane_generateToken() {
             var creditCardNumber = document.getElementById("payment_params:card_numer").value.trim();
             var creditCardArray = creditCardNumber.split('');
             for(i=4;i<creditCardArray.length-4;i++){
                 creditCardArray[i] = '*';
             }
             document.getElementById('paylane-payment-creditCardString').value = creditCardArray.join("");

             PayLane.setPublicApiKey('{$postParameters["public_key_api"]}');

             PayLane.card.generateToken({
                 cardNumber: document.getElementById("payment_params:card_numer").value.trim(),
                 expirationMonth: document.getElementById("payment_params:expiration_month").value.trim(),
                 expirationYear: document.getElementById("payment_params:expiration_year").value.trim(),
                 nameOnCard: document.getElementById("payment_params:name_on_card").value.trim(),
                 cardSecurityCode: document.getElementById("payment_params:card_code").value.trim(),
             },
                 function(token) {
                     document.getElementById('paylane-payment-token').value = token;
                     document['paylane-form-credit-card'].submit();
                 },
                 function(error) {
                     console.error(error);
                     var paylane_message = document.getElementById('paylane_message');
                     var paylane_message_alert = document.getElementById('paylane_message_alert');
                     paylane_message.style.display = 'block';
                     paylane_message_alert.innerHTML = 'Error code: ' + JSON.stringify(error);
                 });
         }

     } catch (e) {
         console.error(e);
     }

    </script>

{/block}