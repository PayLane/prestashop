{if $status == 'ok'}
	  <p>
		    {if {l s='FRONTEND_MESSAGE_YOUR_ORDER' mod='paylane'} == "FRONTEND_MESSAGE_YOUR_ORDER"}Your order on{else}{l s='FRONTEND_MESSAGE_YOUR_ORDER' mod='paylane'}{/if} {$shop_name|escape:'htmlall':'UTF-8'} {if {l s='FRONTEND_MESSAGE_COMPLETE' mod='paylane'} == "FRONTEND_MESSAGE_COMPLETE"}is complete.{else}{l s='FRONTEND_MESSAGE_COMPLETE' mod='paylane'}{/if}<br/>
		    {if {l s='FRONTEND_MESSAGE_THANK_YOU' mod='paylane'} == "FRONTEND_MESSAGE_THANK_YOU"}Thank you for your purchase!{else}{l s='FRONTEND_MESSAGE_THANK_YOU' mod='paylane'}{/if}
	  </p>
{/if}
