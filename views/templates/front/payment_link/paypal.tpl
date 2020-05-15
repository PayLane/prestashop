{assign var=params value=['payment_type' => 'PayPal']}
<p class="payment_module paylane-paypal-wrapper" id="paylane_payment_button">
    <a class="paylane-expander paypal{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="{$thisPath|escape:'htmlall':'UTF-8'}modules/paylane/views/img/paypal.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
