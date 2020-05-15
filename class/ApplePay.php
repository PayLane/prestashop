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

class ApplePay extends PaymentMethodAbstract
{
    protected $paymentType = 'applepay';
    private $paylane;

    public function __construct(Module $paylane) {
	    $this->paylane = $paylane;
    }
    /*
    public function getPaymentOption()
    {
        $active = (boolean)Configuration::get('PAYLANE_APPLEPAY_ACTIVE');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_applepay_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_applepay_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/applepay.png');
            }
        }

        return $paymentOption;
    }
    */

    public function getPaymentConfig()
    {
        $filename = _PS_ROOT_DIR_ . '/.well-known/apple-developer-merchantid-domain-association';
        $dirname = dirname($filename);
        if (!is_dir($dirname))
        {
            mkdir($dirname, 0755, true);
        }

        $cert = (string)Configuration::get('paylane_applepay_certificate');
        $handle = fopen($filename, 'w+');
        fwrite($handle, $cert);
        fclose($handle);

        return array(
            'paylane_applepay_label' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_APPLE_PAY_LABEL', 'applepay'),
                'default' => $this->paylane->l('PAYLANE_APPLE_PAY_DEFAULT_APPLE_PAY', 'applepay')
            ),
            'paylane_applepay_showImg' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_APPLE_PAY_SHOW_PAYMENT_METHOD_IMAGE', 'applepay'),
                'default' => 1
            ),
            'paylane_applepay_certificate' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_APPLE_PAY_LABEL_APPLE_PAY_CERTIFICATE', 'applepay'),
                'default' => $this->paylane->l('PAYLANE_APPLE_PAY_DEFAULT_APPLE_PAY_CERTIFICATE', 'applepay')
            ),
        );
    }

    public function handlePayment($paymentParams)
    {
        $context = Context::getContext();
        $context->cookie->payment_type = $this->paymentType;
        $result = array();

        $data = array();
        $data['sale'] = $this->prepareSaleData();
        $data['customer'] = $this->prepareCustomerData();
        $data['card'] = array(
            'token' => $paymentParams['token']
        );

        $apiResult = $this->client->applePaySale($data);

        if (!empty($apiResult['success']) && $apiResult['success']) {
            $result = array(
                'order_status' => 'CLEARED',
                'success' => $apiResult['success'],
                'error' => $apiResult['error'],
                'id_sale' => $apiResult['id_sale']
            );
            if ($this->isOldPresta()) {
                $result['order_status'] = Configuration::get('PS_OS_PAYMENT');
            }
        } else {
            $result = array(
                'order_status' => 'ERROR',
                'success' => $apiResult['success'],
                'error' => $apiResult['error']
            );
            if ($this->isOldPresta()) {
                $result['order_status'] = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
            }
        }

        return $result;
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/apple_pay.tpl');
    }

    public function getTemplateVars()
    {
        $context = Context::getContext();

        $countryCode = Tools::strtoupper($context->language->iso_code);
        $currencyCode = $context->currency->iso_code;
        $products = $context->cart->getProducts(true);
        if ($this->isOldPresta()) {
            $paymentDescription = Translate::getModuleTranslation('paylane', 'Order from shop ', 'paylane');
            $paymentDescription .= $context->shop->name;
        } else {
            $paymentDescription = $context->getTranslator()->trans('Order from shop ') . $context->shop->name;
        }
        $totalPrice = 0;

        foreach ($products as $prod) {
            $totalPrice += $prod['total_wt'];
        }

        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'applePayLabel' => Configuration::get('paylane_applepay_label'),
            'countryCode' => $countryCode,
            'currencyCode' => $currencyCode,
            'paymentDescription' => $paymentDescription,
            'amount' => sprintf('%01.2f', round($totalPrice, 2)),
            'apiKey' => (string)Configuration::get('PAYLANE_GENERAL_PUBLIC_KEY_API'),
            'withImage' => (bool)Configuration::get('paylane_applepay_showImg'),
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'applePayLabel' => Configuration::get('paylane_applepay_label'),
            'withImage' => (bool)Configuration::get('paylane_applepay_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/apple_pay.tpl');
    }

    protected function prepareCustomerData()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $cookie = $context->cookie;
        $lang = new Language($cookie->id_lang);
        $result = array();

        $address = new Address($cart->id_address_invoice);
        if ($address->firstname && $address->lastname) {
            $result['name'] = $address->firstname . ' ' . $address->lastname;
        }

        if ($cookie->email) {
            $result['email'] = $cookie->email;
        }

        if ($_SERVER['REMOTE_ADDR']) {
            $result['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if ($address->country) {
            $result['country_code'] = Tools::strtoupper($lang->iso_code);
        }

        return $result;
    }
}
