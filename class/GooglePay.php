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
//TODO ALL

require_once(_PS_MODULE_DIR_ . 'paylane/class/PaymentMethodAbstract.php');

class GooglePay extends PaymentMethodAbstract
{
    protected $paymentType = 'googlepay';

    public function getPaymentConfig()
    {
        return array(
            'paylane_googlepay_label' => array(
                'type' => 'text',
                'label' => 'Label',
                'default' => 'Google Pay'
            ),
            'paylane_googlepay_showImg' => array(
                'type' => 'select',
                'label' => 'Show payment method image',
                'default' => 1
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
            'googlePayLabel' => Configuration::get('paylane_googlepay_label'),
            'countryCode' => $countryCode,
            'currencyCode' => $currencyCode,
            'paymentDescription' => $paymentDescription,
            'amount' => sprintf('%01.2f', round($totalPrice, 2)),
            'apiKey' => (string)Configuration::get('PAYLANE_GENERAL_PUBLIC_KEY_API'),
            'withImage' => (bool)Configuration::get('paylane_googlepay_showImg'),
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
