{assign var=params value=['payment_type' => 'BankTransfer']}
<p class="payment_module paylane-bank-transfer-wrapper" id="paylane_payment_button">
    <a class="paylane-expander bank-transfer{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$paymentMethodLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/banktransfer.jpg" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$paymentMethodLabel}}
    </a>
</p>
