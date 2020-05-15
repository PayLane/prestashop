<?php
/*
 * 2005-2016 PayLane sp. z.o.o.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@Paylane.pl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayLane to newer
 * versions in the future. If you wish to customize PayLane for your
 * needs please refer to http://www.Paylane.pl for more information.
 *
 *  @author PayLane <info@paylane.pl>
 *  @copyright  2005-2019 PayLane sp. z.o.o.
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayLane sp. z.o.o.
 */
require_once(_PS_MODULE_DIR_ . 'paylane/class/PaymentMethodAbstract.php');

class SecureForm extends PaymentMethodAbstract
{
    protected $paymentType = 'secureform';
    private $paylane;

    public function __construct(Module $paylane) {
        $this->paylane = $paylane;
        parent::__construct();
    }

    protected $availableLangs = array(
        'pl', 'en', 'de', 'es', 'fr', 'nl', 'it'
    );

    /*
    public function getPaymentOption()
    {
        $active = (Configuration::get('paylane_payment_mode') === 'SECURE_FORM');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_secureform_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_secureform_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/secureform.png');
            }
        }

        return $paymentOption;
    }
    */
    public function getPaymentConfig()
    {
        return array(
            'paylane_secureform_label' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_SECUREFORM_LABEL', 'secureform'),
                'default' => $this->paylane->l('PAYLANE_SECUREFORM_DEFAULT', 'secureform'),
            ),
            'paylane_secureform_showImg' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_SECUREFORM_SHOW_PAYMENT_METHOD_IMAGE', 'secureform'),
                'default' => 1
            ),
        );
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/secureform.tpl');
    }

    public function getTemplateVars()
    {
        return array(
            'action' => 'https://secure.paylane.com/order/cart.html',
            'paymentMethodLabel' => Configuration::get('paylane_secureform_label'),
            'data' => $this->getFormData(),
            'withImage' => (bool)Configuration::get('paylane_secureform_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'paymentMethodLabel' => Configuration::get('paylane_secureform_label'),
            'withImage' => (bool)Configuration::get('paylane_secureform_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/secure_form.tpl');
    }

    protected function getFormData()
    {

        $result = array();

        $context = Context::getContext();
        $cookie = $context->cookie;
        $currency = new Currency($cookie->id_currency);
        $lang = new Language($cookie->id_lang);
        $cart = $context->cart;
        $hashSalt = Configuration::get('PAYLANE_GENERAL_HASH');
        $cookie->payment_type = $this->paymentType;

        $result['amount'] = $cart->getOrderTotal();
        $result['currency'] = $currency->iso_code;
        $result['merchant_id'] = Configuration::get('PAYLANE_GENERAL_MERCHANTID');
        $result['description'] = $cart->id;
        $result['transaction_description'] = $this->generateTransactionDescription();
        $result['transaction_type'] = 'S';
        $result['back_url'] = $context->link->getModuleLink('paylane', 'general', array(), true);
        $result['language'] = in_array($lang->iso_code, $this->availableLangs) ? $lang->iso_code : 'en';

        $hashCode = $hashSalt . '|' . $result['description'] . '|' . $result['amount'] . '|';
        $hashCode .= $result['currency'] . '|' . $result['transaction_type'];

        $result['hash'] = sha1($hashCode);

        if ((bool)Configuration::get('paylane_secureform_send_customer_data')) {
            $customerData = $this->getCustomerData();

            if (count($customerData) > 0) {
                $result = array_merge($result, $customerData);
            }
        }

        return $result;
    }

    protected function getCustomerData()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $cookie = $context->cookie;
        $lang = new Language($cookie->id_lang);
        $result = array();

        $address = new Address($cart->id_address_invoice);
        $state = new State($address->id_state);
        if ($address->firstname && $address->lastname) {
            $result['customer_name'] = $address->firstname . ' ' . $address->lastname;
        }

        if ($cookie->email) {
            $result['customer_email'] = $cookie->email;
        }
        
        if ($address->address1) {
            $result['customer_address'] = $address->address1;
        }

        if ($address->address2) {
            $result['customer_address'] .= ' ' . $address->address2;
        }

        if ($address->postcode) {
            $result['customer_zip'] = $address->postcode;
        }
        
        if ($address->city) {
            $result['customer_city'] = $address->city;
        }

        if ($state->name) {
            $result['customer_state'] = $state->name;
        }

        if ($address->country) {
            if (in_array($lang->iso_code, $this->availableLangs)) {
                $result['customer_country'] = Tools::strtoupper($lang->iso_code);
            } else {
                $result['customer_country'] = 'EN';
            }
        }

        return $result;
    }

    protected function generateTransactionDescription()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $details = $cart->getSummaryDetails();

        $txt = 'Cart #' . $cart->id . '<br> ';

        foreach ($details['products'] as $product) {
            $txt .= $product['name'] . ' x ' . $product['quantity'] . '<br>';
        }

        return $txt;
    }
}
