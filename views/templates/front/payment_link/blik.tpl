{assign var=params value=['payment_type' => 'Blik']}
<p class="payment_module paylane-blik-wrapper" id="paylane_payment_button">
    <a class="paylane-expander blik{if $withImage} with-image{/if}" href="{$link->getModuleLink('paylane', 'payment', $params)|escape:'html'}" title="{{$blikLabel}}">
        {if $withImage}<img src="/modules/paylane/views/img/blik.jpg" alt="Zapłać przez moduł płatności" width="64" height="auto">{/if}
        {{$blikLabel}}
    </a>
</p>
