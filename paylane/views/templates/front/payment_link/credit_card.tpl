{assign var=params value=['payment_type' => 'CreditCard']}
<p class="payment_module paylane-credit-card-wrapper" id="paylane_payment_button">
    <a class="paylane-expander credit-card{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$creditCardLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/creditcard.jpg" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
