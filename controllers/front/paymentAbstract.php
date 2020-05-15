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
require_once(dirname(__FILE__).'/../../core/core.php');

class PaylanePaymentAbstractModuleFrontController extends ModuleFrontController
{
    protected $paymentMethod = '';
    protected $paymentClass = '';
    protected $templateName = 'module:paylane/views/templates/front/paylane_PAYMENT_METHOD.tpl';
    //protected $templateName16 = _PS_MODULE_DIR_ . 'paylane/views/templates/front/payment_form/payment16_PAYMENT_METHOD.tpl';

    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $messageLog =
            'Paylane - start payment process, method : '. $this->paymentMethod .
                ' by customer id : ' . $cart->id_customer;
        PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cart->id, true);

        PrestaShopLogger::addLog('Paylane - get post parameters', 1, null, 'Cart', $cart->id, true);
        $postParameters = $this->getPostParameters();
        $messageLog = 'Paylane - post parameters : ' . print_r($postParameters, true);
        PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cart->id, true);

        PrestaShopLogger::addLog('Paylane - get widget url', 1, null, 'Cart', $cart->id, true);
        $redirectUrl = $this->getRedirectUrl();
        PrestaShopLogger::addLog('Paylane - widget url : ' . $redirectUrl, 1, null, 'Cart', $cart->id, true);

        $this->context->smarty->assign(array(
            'fullname' => $this->context->customer->firstname ." ". $this->context->customer->lastname,
                'lang'    => $this->getLang(),
                'redirectUrl' => $redirectUrl, //paylane response
                'postParameters' => $postParameters,
                'paymentMethod' => $this->paymentMethod,
                'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
        ));

        /*
           if ($this->isOldPresta()) {
           $this->context->smarty->assign($this->getTemplateVars());
           $templateName = str_replace(
           'PAYMENT_METHOD', strtolower($this->paymentMethod), $this->templateName16
           );
           $this->context->smarty->fetch($templateName);
           } else {
           $templateName = str_replace(
           'PAYMENT_METHOD', strtolower($this->paymentMethod), $this->templateName
           );
           $this->setTemplate($templateName);
           }
        */

        $templateName = str_replace(
            'PAYMENT_METHOD', strtolower($this->paymentMethod), $this->templateName
        );

        $this->setTemplate($templateName);
    }

    protected function isOldPresta()
    {
        return version_compare(_PS_VERSION_, '1.7', '<');
    }

    protected function getRedirectUrl() {
        return PaylanePaymentCore::getPaylaneRedirectUrl();
    }

    private function redirectError($returnMessage)
    {
        $this->errors[] = $this->module->getLocaleErrorMapping($returnMessage);
        $this->redirectWithNotifications($this->context->link->getPageLink('order', true, null, array(
            'step' => '3')));
    }

    private function getPostParameters()
    {
        $cart = $this->context->cart;
        $contextLink = $this->context->link;
        $customer = new Customer($cart->id_customer);
        $address = new Address((int)$cart->id_address_delivery);
        $country = new Country($address->id_country);
        $currency = new Currency((int)$cart->id_currency);
        $cartDetails = $cart->getSummaryDetails();

        $dateTime = PaylanePaymentCore::getDateTime();
        $paylaneSettings = $this->getPaylaneSettings();

        if (empty($paylaneSettings['merchant_id'])
            || empty($paylaneSettings['hash'])
        ) {
            $messageLog = 'Paylane - general setting is not completed. either of the parameter is not filled';
            PrestaShopLogger::addLog($messageLog, 3, null, 'Cart', $cart->id, true);
            $this->redirectError('ERROR_GENERAL_REDIRECT');
        }

        $postParameters = array();
        $postParameters['merchant_id'] = $paylaneSettings['merchant_id'];
        $postParameters['public_key_api'] = $paylaneSettings['public_key_api'];
        $postParameters['transaction_id'] = str_pad((int)($cart->id), 4, "0", STR_PAD_LEFT); 
        $postParameters['return_url'] = $contextLink->getModuleLink(
            'paylane',
                'validation',
                array('cart_id' => $cart->id, 'secure_key' => $customer->secure_key, 'payment_method' => $this->paymentMethod),
                true
        );
        $postParameters['3dsreturn_url'] = $contextLink->getModuleLink(
            'paylane',
                '3dsvalidation',
                array('cart_id' => $cart->id, 'secure_key' => $customer->secure_key, 'payment_method' => $this->paymentMethod),
                true
        );
        $postParameters['status_url'] = $this->getStatusUrl();
        $postParameters['cancel_url'] = $contextLink->getPageLink('order', true, null, array('step' => '3'));
        $postParameters['language'] = strtolower($this->getLang());
        $postParameters['customer_email'] = $this->context->customer->email;
        $postParameters['customer_firstname'] = $this->context->customer->firstname;
        $postParameters['customer_lastname'] = $this->context->customer->lastname;
        $postParameters['customer_address'] = $address->address1;
        $postParameters['customer_zip'] = $address->postcode;
        $postParameters['customer_city'] = $address->city;
        $postParameters['customer_country'] = $country->iso_code;
        $postParameters['amount'] = $cart->getOrderTotal(true, Cart::BOTH);

        $postParameters['hash'] = PaylanePaymentCore::generateHash(
            str_pad((int)($cart->id), 4, "0", STR_PAD_LEFT),
                (float)($cart->getOrderTotal(true)),
                $currency->iso_code,
                'S'
        );

        $postParameters['transaction_type'] = 'S';
        $postParameters['currency'] = $currency->iso_code;
        $postParameters['transaction_description'] = $this->getProductsName($cart->getProducts());

        $messageLog = 'Paylane - get post parameters : ' . print_r($postParameters, true);

        return array_merge($postParameters, $this->getTemplateVars());
    }

    protected function getTemplateVars() {
        return array();
    }

    private function getProductsName($products)
    {
        $description = array();
        foreach($products as $product) {
            $description[] = $product['name'];
        }

        return implode(',', $description);
    }

    private function getStatusUrl()
    {
        $cart = $this->context->cart;

        $cartId = $this->context->cart->id;
        $paymentMethod = $this->paymentMethod;
        $cartDate = $cart->date_add;

        $statusUrl = $this->context->link->getModuleLink(
            'paylane',
                'paymentStatus',
                array(
                    'payment_method' => $this->paymentMethod,
                        'cart_id' => $cartId,
                        'payment_key' => $this->module->generateAntiFraudHash($cartId, $paymentMethod, $cartDate)
                ),
                true
        );
        return $statusUrl;
    }

    private function getLang()
    {
        $cart = $this->context->cart;
        $language = new Language((int)$cart->id_lang);
        $languageCode = $language->iso_code;

        return Tools::strtoupper($languageCode);
    }

    private function getPaylaneSettings()
    {
        $paylaneSettings = array();
        $paylaneSettings['merchant_id'] = Configuration::get('PAYLANE_GENERAL_MERCHANTID');
        $paylaneSettings['hash'] = Configuration::get('PAYLANE_GENERAL_HASH');
        $paylaneSettings['login_api'] = Configuration::get('PAYLANE_GENERAL_LOGIN_API');
        $paylaneSettings['public_key_api'] = Configuration::get('PAYLANE_GENERAL_PUBLIC_KEY_API');
        $paylaneSettings['password_api'] = Configuration::get('PAYLANE_GENERAL_PASSWORD_API');

        return $paylaneSettings;
    }
}
