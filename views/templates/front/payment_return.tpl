{extends file='page.tpl'}

{block name="content"}
<section id="content-payment-return" class="card definition-list">
    <div class="card-block">
      <div class="row">
        <div class="col-md-12">
            <p>
                {if {l s='FRONTEND_MESSAGE_YOUR_ORDER' mod='paylane'} == "FRONTEND_MESSAGE_YOUR_ORDER"}Your order on{else}{l s='FRONTEND_MESSAGE_YOUR_ORDER' mod='paylane'}{/if} {$shop_name|escape:'htmlall':'UTF-8'} {if {l s='FRONTEND_MESSAGE_INPROCESS' mod='paylane'} == "FRONTEND_MESSAGE_INPROCESS"}is in the process.{else}{l s='FRONTEND_MESSAGE_INPROCESS' mod='paylane'}{/if}<br/>
                {if {l s='FRONTEND_MESSAGE_PLEASE_BACK_AGAIN' mod='paylane'} == "FRONTEND_MESSAGE_PLEASE_BACK_AGAIN"}Please back again after a minutes and check your order histoy{else}{l s='FRONTEND_MESSAGE_PLEASE_BACK_AGAIN' mod='paylane'}{/if}
            </p>
        </div>
      </div>
    </div>
</section>
{/block}
