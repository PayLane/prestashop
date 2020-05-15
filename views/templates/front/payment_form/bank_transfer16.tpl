{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

<h2>{l s='Order summary' mod='paylane'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{{$paymentMethodLabel}}</h3>

<form action="{$action}" method="POST" class="paylane-form paylane-form--bank-transfer">
    <input type="hidden" name="payment_type" value="BankTransfer">
    <input type="hidden" name="payment[additional_information][type]" value="BankTransfer">
    <div class="paylane-form__container-title">{l s='Choose your bank' mod='paylane'}:</div>
    <ul class="form-list paylane-form__payment-types-list">
        {foreach $paymentTypes as $code => $data}
            <li>
                <input type="radio" name="payment[additional_information][payment_type]" id="payment_type_{$code}" value="{$code}">
                <label for="payment_type_{$code}">
                    <img src="{$smarty.const._MODULE_DIR_}paylane/views/img/banks/{$code}.png" title="{$data['label']}" alt="{$data['label']}" />
                    <span>{$data['label']}</span>
                </label>
            </li>
        {/foreach}
    </ul>
    <div class="clearfix"></div>
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
    var bankInputs = document.querySelectorAll('.paylane-form--bank-transfer .paylane-form__payment-types-list input[type="radio"]');

    for (var i = 0; i < bankInputs.length; i++) {
        bankInputs[i].addEventListener('click', function(ev) {
            var labels = document.querySelectorAll('.paylane-form--bank-transfer .paylane-form__payment-types-list label');
            for (var j = 0; j < labels.length; j++) {
                labels[j].classList.remove('checked');
            }
            document.querySelector('label[for="'+this.id+'"]').classList.add('checked');
        });
    }
</script>
