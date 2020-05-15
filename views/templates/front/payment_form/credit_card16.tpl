{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

<h2>{l s='Order summary' mod='paylane'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{{$paymentMethodLabel}}</h3>

<form action="{$action}" method="POST" id="paylane-form-credit-card" class="paylane-form paylane-credit-card">
    <input type="hidden" name="payment_type" value="CreditCard">
    {* <input type="hidden" name="payment[additional_information][back_url]" value="{$postParameters['3dsreturn_url']|escape:'htmlall':'UTF-8'}" /> *}
    <input type="hidden" id="payment_params:back_url" name="payment[additional_information][back_url]" value="{$postParameters['3dsreturn_url']}">
    <input type="hidden" name="payment[additional_information][type]" value="CreditCard">
    <input id="paylane-payment-token" type="hidden" name="payment[additional_information][token]" value="">
    <input id="paylane-payment-creditCardString" type="hidden" name="payment[additional_information][creditCardString]" value="">
   
    {if !$isSingleClickActive || ($isSingleClickActive && $isFirstOrder)}
        {if $creditCardsArray}
        <div class="field">
            <label for="payment_params:id_sale" class="required"><em>*</em>{l s='Choose your credit card' mod='paylane'}</label>
            <div class="input-box">
                <select id="payment_params:id_sale" data-paylane="id-sale" class="required-entry" name="payment[additional_information][id_sale]" onchange="dropdownHideOrShowForm()">
                    {foreach from=$creditCardsArray item=creditCardSingle}
                        <option value={$creditCardSingle.id_sale}|{$creditCardSingle.credit_card_number}>{$creditCardSingle.credit_card_number}</option>
                    {/foreach}
                    <option value="addNewCard">{l s='Add new credit card' mod='paylane'}</option>
                </select>
            </div>
        </div>
        {/if}
        <div id="payment_form_paylane_creditcard">
        <div class="form-list">
        <div class="field">
            <label for="payment_params:card_numer" class="required">{l s='Card number' mod='paylane'}</label>
            <div class="input-box">
                <input type="text" id="payment_params:card_numer" data-paylane="cc-number" size="19" class="input-text required-entry">
            </div>
        </div>
        <div class="field">
            <label for="payment_params:name_on_card" class="required">{l s='Name on card' mod='paylane'}</label>
            <div class="input-box">
                <input type="text" id="payment_params:name_on_card" data-paylane="cc-name-on-card" size="50" class="input-text required-entry">
            </div>
        </div>
        <div class="field">
            <label for="payment_params:expiration_month" class="required">{l s='Expiration month' mod='paylane'}</label>
            <div class="input-box">
                <select id="payment_params:expiration_month" data-paylane="cc-expiry-month" class="required-entry">
                    {foreach from=$months item=month}
                    <option value="{$month}">{$month}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="field">
            <label for="payment_params:expiration_year" class="required">{l s='Expiration year' mod='paylane'}</label>
            <div class="input-box">
                <select id="payment_params:expiration_year" data-paylane="cc-expiry-year" class="required-entry">
                    {foreach from=$years item=year}
                    <option value="{$year}">{$year}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="field">
            <label for="payment_params:card_code" class="required">{l s='CVV/CVC' mod='paylane'}</label>
            <div class="input-box">
                <input type="text" id="payment_params:card_code" data-paylane="cc-cvv" size="4" class="input-text required-entry">
            </div>
        </div>
        </div>
        </div>
    {else}
        {if $authorizeId}
        <input type="hidden" id="payment_params:authorization_id" name="payment[additional_information][authorization_id]" value="{$authorizeId}">
            {l s='User authorized earlier - no additional data required' mod='paylane'}
        {else}
        <input type="hidden" id="payment_params:sale_id" name="payment[additional_information][sale_id]" value="{$lastSaleId}">
            {l s='Using Single-click method - get card data from earlier order' mod='paylane'}
        {/if}
    {/if}
    <div class="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-primary">
            <i class="icon-chevron-left"></i>
            {l s='Other payment methods' mod='paylane'}
        </a>
        <button class="button btn btn-primary button-medium" type="submit">
            <span>{l s='Confirm order' mod='paylane'}<i class="icon-chevron-right right"></i></span>
        </button>
    </div>
</form>
<script>

    document.addEventListener('DOMContentLoaded', function() {
        try{
            var val = document.getElementById("payment_params:id_sale").value;
            if(val!="addNewCard"){
                var creditCardForm = document.getElementById("payment_form_paylane_creditcard");
                creditCardForm.classList.add('paylane-credit-card-hide-form');
                submitBtn.disabled = false;
            }
        }catch(e){
            console.log("Single-Click not available");
        }
    }, false);

    function dropdownHideOrShowForm() {
        var val = document.getElementById("payment_params:id_sale").value;
        var creditCardForm = document.getElementById("payment_form_paylane_creditcard");
        if(val != 'addNewCard'){
            creditCardForm.classList.add('paylane-credit-card-hide-form');
            submitBtn.disabled = false;
        }else{
            creditCardForm.classList.remove('paylane-credit-card-hide-form');
            submitBtn.disabled = true;
        }
    }

    try {
        PayLane.setPublicApiKey('{$apiKey}');

        var submitBtn = document.querySelector('.cart_navigation button[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        var payLaneInputs = document.querySelectorAll('[data-paylane]');
        for (var i=0; i < payLaneInputs.length; i++) {
            var paylaneInput = payLaneInputs[i];

            paylaneInput.addEventListener('blur', function() {
                var filled = true;

                for (var j=0; j < payLaneInputs.length; j++) {
                    var paylaneElem = payLaneInputs[j];

                    if (!paylaneElem.value) {
                        filled = false;
                        break;
                    }
                }

                if (filled) {
                    var creditCardNumber = document.getElementById("payment_params:card_numer").value;;
                    var creditCardArray = creditCardNumber.split('');
                    for(i=4;i<creditCardArray.length-4;i++){
                        creditCardArray[i] = '*';
                    }
                    document.getElementById('paylane-payment-creditCardString').value = creditCardArray.join("");

                    PayLane.card.generateToken({
                        cardNumber: document.getElementById("payment_params:card_numer").value,
                        expirationMonth: document.getElementById("payment_params:expiration_month").value,
                        expirationYear: document.getElementById("payment_params:expiration_year").value,
                        nameOnCard: document.getElementById("payment_params:name_on_card").value,
                        cardSecurityCode: document.getElementById("payment_params:card_code").value
                    },
                    function(token) {
                        document.getElementById('paylane-payment-token').value = token;
                        submitBtn.disabled = false;
                    },
                    function(error) {
                        console.error(error);
                    });
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
</script>