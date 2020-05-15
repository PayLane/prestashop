{assign var=params value=['payment_type' => 'BankTransfer']}
<p class="payment_module paylane-bank-transfer-wrapper" id="paylane_payment_button">
    <a class="paylane-expander bank-transfer{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="{$thisPath|escape:'htmlall':'UTF-8'}modules/paylane/views/img/banktransfer.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
