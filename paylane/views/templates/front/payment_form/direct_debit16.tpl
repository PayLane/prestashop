{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paylane'}">{l s='Checkout' mod='paylane'}</a><span class="navigation-pipe">{$navigationPipe}</span>{{$paymentMethodLabel}}
{/capture}

<h2>{l s='Order summary' mod='paylane'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{{$paymentMethodLabel}}</h3>

<form action="{$action}" method="POST" class="paylane-form paylane-direct-debit">
    <input type="hidden" name="payment_type" value="DirectDebit">
    <input type="hidden" name="payment[additional_information][type]" value="DirectDebit">
    <div class="form-list">
      <div class="field">
          <label for="payment_params:account_holder" class="required"><em>*</em>{l s='Account holder' mod='paylane'}</label>
          <div class="input-box">
              <input type="text" id="payment_params:account_holder" name="payment[additional_information][account_holder]" size="30" class="input-text required-entry">
          </div>
      </div>
      <div class="field">
          <label for="payment_params:account_country" class="required"><em>*</em>{l s='Bank account country' mod='paylane'}</label>
          <div class="input-box">
              <select id="payment_params:account_country" name="payment[additional_information][account_country]" class="required-entry">
                {foreach $countries as $code => $label}
                  <option value="{$code}">{l s=$label mod='paylane'}</option>
                {/foreach}
              </select>
          </div>
      </div>
      <div class="field">
          <label for="payment_params:iban" class="required"><em>*</em>{l s='IBAN Number' mod='paylane'}</label>
          <div class="input-box">
              <input type="text" id="payment_params:iban" name="payment[additional_information][iban]" size="31" class="input-text required-entry">
          </div>
      </div>
      <div class="field">
          <label for="payment_params:bic" class="required"><em>*</em>{l s='Bank Identifier Code (BIC)' mod='paylane'}</label>
          <div class="input-box">
              <input type="text" id="payment_params:bic" name="payment[additional_information][bic]" size="4" class="input-text required-entry">
          </div>
      </div>
  </div>
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
