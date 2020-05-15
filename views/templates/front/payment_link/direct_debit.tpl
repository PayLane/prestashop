{assign var=params value=['payment_type' => 'DirectDebit']}
<p class="payment_module paylane-direct-debit-wrapper" id="paylane_payment_button">
    <a class="paylane-expander direct-debit{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
            {if $withImage}<img src="{$thisPath|escape:'htmlall':'UTF-8'}modules/paylane/views/img/directdebit.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
