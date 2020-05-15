{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

<h2>{l s='Order summary' mod='paylane'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{{$paymentMethodLabel}}</h3>

<form action="{$action}" method="POST" class="paylane-form paylane-blik" name="paylane-form-blik">
            <input type="hidden" name="payment_type" value="BLIK">
            <input type="hidden" name="payment[additional_information][back_url]" value="{$postParameters['return_url']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="payment[additional_information][type]" value="BLIK">
            <input type="hidden" name="currency" value="{$postParameters['currency']|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="amount" value="{$postParameters['amount']|escape:'htmlall':'UTF-8'}" />

            <div class="form-list">
                <div class="form-group" id="fg-payment_params:BLIK">
                    <label for="payment_params:BLIK" class="control-label">{l s='Blik code' mod='paylane'}</label>
                    <div class="input-box">
                        <input type="text" 
                               id="payment_params:BLIK" 
                               data-paylane="BLIK" 
                               name="payment[additional_information][code]"
                               size="6"   
                               placeholder="000000"
                               class="blik-button">
                    </div>
                </div>
            </div>
            
            <div class="cart_navigation">
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-primary">
                    <i class="icon-chevron-left"></i>
                    {l s='Other payment methods' mod='paylane'}
                </a>
                <button class="button btn btn-primary button-medium" type="submit" id="button_paylane" onclick="$('#loading').show();">
                    
                    <span>{l s='Confirm order' mod='paylane'}<i class="icon-chevron-right right"></i></span>
                    <div id="loading" style="display:none;"><img src="{$thisPath|escape:'htmlall':'UTF-8'}modules/paylane/views/img/loading.gif" width="40" height="40" alt="Processing" /></div>
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
                 document['paylane-form-blik'].submit();
             }
         }
     } catch (e) {
         console.error(e);
     }

    </script>