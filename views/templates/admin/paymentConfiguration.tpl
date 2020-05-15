<form id="module_form" class="defaultForm form-horizontal" action="{$currentIndex|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
    <div class="panel">
		    {foreach from=$payments key=sort item=payment}
			      <div class="form-group">
				        <div class="col-lg-2 logo-wrapper">
					          <img src="{$thisPath|escape:'htmlall':'UTF-8'}views/img/{$payment.type|escape:'htmlall':'UTF-8'}.png" alt="{$payment.type|escape:'htmlall':'UTF-8'}" class="payment-config-logo">
				        </div>
				        <label class="payment-label col-lg-3">
					          {$payment.title|escape:'htmlall':'UTF-8'}
					          {if !empty($payment.tooltips)}
						            <img src="{$thisPath|escape:'htmlall':'UTF-8'}views/img/questionmark.jpg" alt="{$payment.type|escape:'htmlall':'UTF-8'}" data-toggle="tooltip" title="{$payment.tooltips|escape:'htmlall':'UTF-8'}" class="payment-config-tooltip paylane-{$payment.type|escape:'htmlall':'UTF-8'}-tooltip">
					          {/if}
				        </label>
				        <div class="col-lg-3">
					          <div class="col-lg-4 control-label switch-label">{$label.active|escape:'htmlall':'UTF-8'}</div>
					          <div class="col-lg-6 switch prestashop-switch fixed-width-lg">
						            <input type="radio" name="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE" id="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE_on" value="1" {if ($payment.active == 1)}checked="checked"{/if}>
						            <label for="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE_on">{$button.yes|escape:'htmlall':'UTF-8'}</label>
						            <input type="radio" name="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE" id="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE_off" value="0" {if empty($payment.active)}checked="checked"{/if}>
						            <label for="PAYLANE_{$payment.brand|escape:'htmlall':'UTF-8'}_ACTIVE_off">{$button.no|escape:'htmlall':'UTF-8'}</label>
						            <a class="slide-button btn"></a>
					          </div>
				        </div>
				        <div style="clear: both"></div>
			      </div>
			      <div style="clear: both"></div>
		    {/foreach}

	      <div class="panel-footer">
		        <button type="submit" value="1" name="btnSubmitPaymentConfig" class="btn btn-default pull-right">
			          <i class="process-icon-save"></i> {$button.save|escape:'htmlall':'UTF-8'}
		        </button>
	      </div>
    </div>
</form>
