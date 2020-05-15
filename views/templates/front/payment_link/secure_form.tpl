{assign var=params value=['payment_type' => 'SecureForm']}
<p class="payment_module" id="paylane_payment_button">
    <a class="paylane-expander" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/secureform.jpg" alt="Zapłać przez moduł płatności" width="150" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
