{assign var=params value=['payment_type' => 'ApplePay']}
<p class="payment_module paylane-applepay-wrapper" id="paylane_payment_button">
    <a class="paylane-expander applepay{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$applePayLabel}}">
        {if $withImage}<img src="{$thisPath|escape:'htmlall':'UTF-8'}modules/paylane/views/img/applepay.png" alt="Zapłać przez moduł płatności" width="80" height="auto">{/if}
        {{$applePayLabel}}
    </a>
</p>
