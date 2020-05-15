{assign var=params value=['payment_type' => 'Sofort']}
<p class="payment_module paylane-sofort-wrapper" id="paylane_payment_button">
    <a class="paylane-expander sofort{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/sofort.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
