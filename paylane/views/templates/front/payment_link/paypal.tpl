{assign var=params value=['payment_type' => 'PaylanePayPal']}
<p class="payment_module paylane-paypal-wrapper" id="paylane_payment_button">
    <a class="paylane-expander paypal{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/paypal.jpg" alt="Zapłać przez moduł płatności" width="64" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
