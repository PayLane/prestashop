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
require_once(_PS_MODULE_DIR_ . 'paylane/lib/paylane-rest.php');
abstract class PaymentMethodAbstract
{
    protected $client = null;
    protected $paymentType = null;

    //abstract public function getPaymentOption();
    abstract public function getPaymentConfig();

    public function __construct()
    {
        $apiUser = Configuration::get('PAYLANE_GENERAL_LOGIN_API');
        $apiPass = Configuration::get('PAYLANE_GENERAL_PASSWORD_API');

        if ($apiUser && $apiPass) {
            $this->client = new PayLaneRestClient($apiUser, $apiPass);
        }
    }

    protected function prepareSaleData()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $currency = $context->currency;

        $result = array();
        $result['amount'] = sprintf('%01.2f', $cart->getOrderTotal());
        $result['currency'] = $currency->iso_code;
        $result['description'] = (string)$cart->id;

        if ($this->paymentType === 'creditcard') {
            if ((boolean)Configuration::get('paylane_creditcard_fraud_check_override')) {
                $result['fraud_check_on'] = (boolean)Configuration::get('paylane_creditcard_fraud_check');
            }
            if ((boolean)Configuration::get('paylane_creditcard_avs_override')) {
                $result['avs_check_level'] = (int)Configuration::get('paylane_creditcard_avs');
            }
        }

        return $result;
    }

    protected function prepareCustomerData()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $cookie = $context->cookie;
        $lang = new Language($cookie->id_lang);
        $result = array();

        $address = new Address($cart->id_address_invoice);
        $state = new State($address->id_state);
        if ($address->firstname && $address->lastname) {
            $result['name'] = $address->firstname . ' ' . $address->lastname;
        }

        if ($cookie->email) {
            $result['email'] = $cookie->email;
        }

        if ($_SERVER['REMOTE_ADDR']) {
            $result['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        $result['address'] = array();
        
        if ($address->address1) {
            $result['address']['street_house'] = $address->address1;
        }

        if ($address->address2) {
            $result['address']['street_house'] .= ' ' . $address->address2;
        }

        if ($address->postcode) {
            $result['address']['zip'] = $address->postcode;
        }
        
        if ($address->city) {
            $result['address']['city'] = $address->city;
        }

        if ($state->name) {
            $result['address']['state'] = $state->name;
        }

        if ($address->country) {
            $result['address']['country_code'] = Country::getIsoById((int)$address->id_country);
        }
        
        return $result;
    }

    protected function isOldPresta()
    {
        return version_compare(_PS_VERSION_, '1.7', '<');
    }

    protected function fetchTemplate($path)
    {
        $context = Context::getContext();
        if ($this->isOldPresta()) {
            return $context->smarty->fetch(_PS_MODULE_DIR_ . 'paylane/views/templates/' . $path);
        } else {
            return $context->smarty->fetch('module:paylane/views/templates/' . $path);
        }
    }
}
