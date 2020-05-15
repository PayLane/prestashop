//TODO
{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.applePayLabel}}</h3>

        <div id="paylane_message" style="display: none">
            <div class="alert alert-danger" id="paylane_message_alert" role="alert"></div>
        </div>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-applepay">
            <div id="apple-pay-active">

                <input id="payment_params_token" type="hidden" name="payment[additional_information][token]" value="">
                {l s=' TODO GOOGLEPAY' mod='paylane'}
            </div>

        </form>
        <script>

        </script>

    </div>
    <div class="center-block col-lg-3"></div>

{/block}
