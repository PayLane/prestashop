<div class="row">
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-credit-card"></i>
                {if {l s='BACKEND_GENERAL_INFORMATION' mod='paylane'} == "BACKEND_GENERAL_INFORMATION"}PAYMENT INFORMATION{else}{l s='BACKEND_GENERAL_INFORMATION' mod='paylane'}{/if}
                <span class="badge">{if {l s='BACKEND_TT_BY_PAYLANE' mod='paylane'} == "BACKEND_TT_BY_PAYLANE"}by Paylane{else}{l s='BACKEND_TT_BY_PAYLANE' mod='paylane'}{/if}</span>
            </div>
            <div id="paymentinfos" class="well col-xs-12">
                <form method='POST' action="">
                    <div id="paymentinfo">
                        <div class="form-group">
                            <label class="col-lg-12">{$paymentInfo.name|escape:'htmlall':'UTF-8'}</label>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_ORDER_STATUS' mod='paylane'} == "PAYLANE_BACKEND_ORDER_STATUS"}Payment status{else}{l s='PAYLANE_BACKEND_ORDER_STATUS' mod='paylane'}{/if}</label>
                            <label class="control-label col-lg-9">{$paymentInfo.status|escape:'htmlall':'UTF-8'}</label>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_ORDER_PM' mod='paylane'} == "PAYLANE_BACKEND_ORDER_PM"}Used payment method{else}{l s='PAYLANE_BACKEND_ORDER_PM' mod='paylane'}{/if}</label>
                            <label class="control-label col-lg-9">{$paymentInfo.method|escape:'htmlall':'UTF-8'}</label>
                        </div>
                        {if isset($paymentInfo.order_origin)}
                            <div class="form-group">
                                <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_ORDER_ORIGIN' mod='paylane'} == "PAYLANE_BACKEND_ORDER_ORIGIN"}Order originated from{else}{l s='PAYLANE_BACKEND_ORDER_ORIGIN' mod='paylane'}{/if}</label>
                                <label class="control-label col-lg-9">{$paymentInfo.order_origin|escape:'htmlall':'UTF-8'}</label>
                            </div>
                        {/if}
                        {if isset($paymentInfo.order_country)}
                            <div class="form-group">
                                <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_ORDER_COUNTRY' mod='paylane'} == "PAYLANE_BACKEND_ORDER_COUNTRY"}Country (of the card-issuer){else}{l s='PAYLANE_BACKEND_ORDER_COUNTRY' mod='paylane'}{/if}</label>
                                <label class="control-label col-lg-9">{$paymentInfo.order_country|escape:'htmlall':'UTF-8'}</label>
                            </div>
                        {/if}
                        <div class="form-group">
                            <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_ORDER_CURRENCY' mod='paylane'} == "PAYLANE_BACKEND_ORDER_CURRENCY"}Currency{else}{l s='PAYLANE_BACKEND_ORDER_CURRENCY' mod='paylane'}{/if}</label>
                            <label class="control-label col-lg-9">{$paymentInfo.currency|escape:'htmlall':'UTF-8'}</label>
                        </div>
                        <div class="form-group">&nbsp;</div>
                        <div class="form-group">
                            <label class="col-lg-3">{if {l s='BACKEND_TT_TRANSACTION_ID' mod='paylane'} == "BACKEND_TT_TRANSACTION_ID"}Transaction ID{else}{l s='BACKEND_TT_TRANSACTION_ID' mod='paylane'}{/if}</label>
                            <label class="control-label col-lg-9">{$paymentInfo.transaction_id|escape:'htmlall':'UTF-8'}</label>
                        </div>
                        {if isset($paymentInfo.paylane_account)}
                            <div class="form-group">
                                <label class="col-lg-3">{if {l s='PAYLANE_BACKEND_EMAIL_ACCOUNT' mod='paylane'} == "PAYLANE_BACKEND_EMAIL_ACCOUNT"}Email address of paylane account{else}{l s='PAYLANE_BACKEND_EMAIL_ACCOUNT' mod='paylane'}{/if}</label>
                                <label class="control-label col-lg-9">{$paymentInfo.paylane_account|escape:'htmlall':'UTF-8'}</label>
                            </div>
                        {/if}
                    </div>
                </form>
            </div>
            <div style="clear:both"></div>
        </div>
    </div>
</div>
