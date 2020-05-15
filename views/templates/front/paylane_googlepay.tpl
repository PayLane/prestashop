{extends file='page.tpl'}

{block name="content"}

    <div class="center-block col-lg-3"></div>
    <div class="center-block col-sm-12 col-lg-6">

        <h3>{{$postParameters.googlePayLabel}}</h3>

        <form action="{$postParameters.action}" method="POST" class="paylane-form paylane-googlepay">
            <div id="google-pay-active">

                <input id="payment_params_token" type="hidden" name="payment[additional_information][token]" value="">
                {l s='  GOOGLEPAY' mod='paylane'}

            </div>
        </form>

        <script>

        </script>

    </div>
    <div class="center-block col-lg-3"></div>

{/block}
