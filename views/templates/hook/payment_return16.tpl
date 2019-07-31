{if $status == 'ok'}
  <p style="color: #458a45;">{l s='Payment processed successfully' mod='paylane'}</p>
{else}
  <p style="color: #ff0000;">{l s='Payment not processed successfully' mod='paylane'}</p>
  <p>{l mod='paylane' s="Please contact shop administrator for further information."}</p>
{/if}

