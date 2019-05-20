{extends file='page.tpl'}

{block name="content"}
    <section>
            <h3>PayLane SecureForm</h3>
            {l s='You will be redirected to PayLane Secure Form to pay for the order' mod='paylane'}

            <form action="{$redirectUrl}" method="post" id="paylane_form" class="paylane-form paylane-secureform">
                <input type="hidden" name="merchant_id" value="{$postParameters['merchant_id']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="amount" value="{$postParameters['amount']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="currency" value="{$postParameters['currency']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="transaction_type" value="S" />
                <input type="hidden" name="back_url" value="{$postParameters['return_url']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="description" value="{$postParameters['transaction_id']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="transaction_description" value="{$postParameters['transaction_description']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="language" value="{$postParameters['language']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="hash" value="{$postParameters['hash']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_name" value="{$postParameters['customer_firstname']|escape:'htmlall':'UTF-8'} {$postParameters['customer_lastname']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_email" value="{$postParameters['customer_email']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_address" value="{$postParameters['customer_address']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_zip" value="{$postParameters['customer_zip']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_city" value="{$postParameters['customer_city']|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="customer_country" value="{$postParameters['customer_country']|escape:'htmlall':'UTF-8'}" />
            </form>
            <script type="text/javascript">
             {literal}
             document.forms["paylane_form"].submit();
             {/literal}
            </script>
    </section>
{/block}
