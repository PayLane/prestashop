<link rel="stylesheet" type="text/css" href="<?php echo $data['static_files_url'] ?>views/css/style.css"/>
<div class="tpay-insidebg" id="main-payment">
        <div class="tpay-header-belt"></div>
    </div>
    <div class="tpay-panel-inside-content">
        <div class="tpay-channel-form-wrapper tpay-content-wrapper-class">
            <img src="https://secure.tpay.com/_/banks/b64.png"/>
            <form action="<?php echo $data['action_url'] ?>" method="post" id="tpay-blik-form">
                <div class="tpay-row">
                    <div class="tpay-input-blik-code">
                        <div class="tpay-input-wrapper">
                            <div class="tpay-input-label"
                                 title="<?php $lang->l('blik_info') ?>"><?php $lang->Twój kod Blik</div>
                            <input id="blik_code"
                                   name="blik_code"
                                   pattern="\d*"
                                   type="tel"
                                   autocomplete="off"
                                   maxlength="6"
                                   minlength="6"
                                   placeholder="000000"
                                   tabindex="1"
                                   value=""
                                   class="tpay-input-value tpay-blik-input"
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="tpay-buttons-holder">
            <input class="tpay-pay-button" id="tpay-payment-submit" type="submit" value="Zapłać">
        </div>
    </div>
</div>

{*<script type="text/javascript">*}
{*    var regulation_checkbox = document.getElementById('tpay-accept-regulations-checkbox'),*}
{*        submit_form_input = document.getElementById('tpay-payment-submit'),*}
{*        blik_code_input = document.getElementById('blik_code');*}
{*    submit_form_input.onclick = function () {*}
{*        if (regulation_checkbox.value == 0) {*}
{*            alert('<?php $lang->l('acceptance_is_required') ?>');*}
{*            return false;*}
{*        }*}
{*        if (blik_code_input.value.length !== 6 || /^\d+$/.test(blik_code_input.value) === false) {*}
{*            alert('<?php $lang->l('blik_code_error') ?>');*}
{*            return false;*}
{*        }*}
{*        document.getElementById('tpay-blik-form').submit();*}
{*        return true;*}
{*    };*}
{*    regulation_checkbox.onchange = function () {*}
{*        regulation_checkbox.value = (this.checked) ? 1 : 0;*}
{*    };*}
{*</script>*}
