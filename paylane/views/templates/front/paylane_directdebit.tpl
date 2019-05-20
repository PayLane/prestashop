{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.paymentMethodLabel}}</h3>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-direct-debit" name="paylane-form-directdebit">
            <input type="hidden" name="payment_type" value="DirectDebit">
            <input type="hidden" name="payment[additional_information][type]" value="DirectDebit">
            <div class="form-list">
                <div class="form-group" id="fg-payment_params:account_holder">
                    <label for="payment_params:account_holder" class="required">{l s='Account holder' mod='paylane'}</label>
                    <div class="input-box">
                        <input type="text" data-paylane="account_holder" id="payment_params:account_holder" name="payment[additional_information][account_holder]" size="30" class="form-control">
                    </div>
                </div>
                <div class="form-group" id="fb-payment_params:account_country">
                    <label for="payment_params:account_country" class="required">{l s='Bank account country' mod='paylane'}</label>
                    <div class="input-box" id="fg-payment_params:account_country">
                        <select data-paylane="account_country" id="payment_params:account_country" name="payment[additional_information][account_country]" class="form-control form-control-select">
                            {foreach $postParameters.countries as $code => $label}
                                <option value="{$code}">{l s=$label mod='paylane'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group" id="fg-payment_params:iban">
                    <label for="payment_params:iban" class="required">{l s='IBAN Number' mod='paylane'}</label>
                    <div class="input-box">
                        <input type="text" data-paylane="iban" id="payment_params:iban" name="payment[additional_information][iban]" size="31" class="form-control">
                    </div>
                </div>
                <div class="form-group" id="fg-payment_params:bic">
                    <label for="payment_params:bic" class="required">{l s='Bank Identifier Code (BIC)' mod='paylane'}</label>
                    <div class="input-box">
                        <input type="text" data-paylane="bic" id="payment_params:bic" name="payment[additional_information][bic]" size="4" class="form-control">
                    </div>
                </div>
            </div>
            <div class="cart_navigation">
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-primary">
                    <i class="icon-chevron-left"></i>
                    {l s='Other payment methods' mod='paylane'}
                </a>
                <button class="button btn btn-primary button-medium" type="submit" id="button_paylane">
                    <span>{l s='Confirm order' mod='paylane'}<i class="icon-chevron-right right"></i></span>
                </button>
            </div>
        </form>

    </div>
    <div class="center-block col-lg-3"></div>
    <script>

     try {
         document.getElementById("button_paylane").addEventListener("click", paylane_validate);

         function paylane_validate(e) {
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
                 document['paylane-form-directdebit'].submit();
             }
         }
     } catch (e) {
         console.error(e);
     }

    </script>


{/block}
