{assign var=params value=['payment_type' => 'DirectDebit']}
<p class="payment_module paylane-direct-debit-wrapper" id="paylane_payment_button">
    <a class="paylane-expander direct-debit{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
            {if $withImage}<img src="/modules/paylane/views/img/directdebit.jpg" alt="Zapłać przez moduł płatności" width="64" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
