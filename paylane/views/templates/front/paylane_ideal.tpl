{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.paymentMethodLabel}}</h3>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-ideal" name="paylane-form-ideal">
            <input type="hidden" name="payment_type" value="Ideal">
            <input type="hidden" name="payment[additional_information][type]" value="Ideal">
            <input type="hidden" name="payment[additional_information][back_url]" value="{$postParameters['return_url']|escape:'htmlall':'UTF-8'}" />
            <div class="paylane-form__container-title">{l s='Choose bank' mod='paylane'}:</div>
            <div class="form-list">
                <div class="form-group">
                    <div class="input-box" fg="fg-payment_params:bank_code">
                        <select data-paylane="bank_code" id="payment_params:bank_code" name="payment[additional_information][bank_code]" class="form-control form-control-select">
                            {foreach $postParameters.banks as $bank}
                                <option value="{$bank['bank_code']}">{$bank['bank_name']}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
            <div class="cart_navigation">
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-primary">
                    <i class="icon-chevron-left"></i>
                    {l s='Other payment methods' mod='paylane'}
                </a>
                <button class="button btn btn-primary button-medium" type="submit" id="button_paylane">
                    <span>{l s='Confirm order' mod='paylane'}<i class="icon-chevron-right right"></i></span>
                </button>
            </div>
        </form>

    </div>
    <div class="center-block col-lg-3"></div>


{/block}
