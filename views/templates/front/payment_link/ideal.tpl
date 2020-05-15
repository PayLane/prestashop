{assign var=params value=['payment_type' => 'Ideal']}
<p class="payment_module paylane-ideal-wrapper" id="paylane_payment_button">
    <a class="paylane-expander ideal{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/ideal.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
