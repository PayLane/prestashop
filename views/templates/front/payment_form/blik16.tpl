{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

