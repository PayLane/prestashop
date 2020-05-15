{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

<h2>{l s='Order summary' mod='paylane'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{{$paymentMethodLabel}}</h3>

<form action="{$action}" method="POST" class="paylane-form paylane-paypal">
    <input type="hidden" name="payment_type" value="PayPal">
    <input type="hidden" name="payment[additional_information][type]" value="PayPal">
    {l s='You will be redirected to PayPal website to pay for the order' mod='paylane'}
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
