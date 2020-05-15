{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.paymentMethodLabel}}</h3>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-paypal">
            <input type="hidden" name="payment_type" value="PayPal">
            <input type="hidden" name="payment[additional_information][type]" value="PayPal">
            <input type="hidden" name="payment[additional_information][back_url]" value="{$postParameters['return_url']|escape:'htmlall':'UTF-8'}" />

            {l s='You will be redirected to PayPal website to pay for the order' mod='paylane'}
            <div class="cart_navigation">
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-primary">
                    <i class="icon-chevron-left"></i>
                    {l s='Other payment methods' mod='paylane'}
                </a>
                <button class="button btn btn-primary button-medium" type="submit">
                    <span>{l s='Confirm order' mod='paylane'}<i class="icon-chevron-right right"></i></span>
                </button>
            </div>
        </form>
    </div>
    <div class="center-block col-lg-3"></div>

{/block}
