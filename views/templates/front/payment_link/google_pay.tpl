{assign var=params value=['payment_type' => 'GooglePay']}
<p class="payment_module paylane-googlepay-wrapper" id="paylane_payment_button">
    <a class="paylane-expander googlepay{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$googlePayLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/googlepay.jpg" alt="Zapłać przez moduł płatności" width="64" height="auto">{/if}
        {{$googlePayLabel}}
    </a>
</p>
