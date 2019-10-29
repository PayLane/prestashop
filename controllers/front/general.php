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

/**
 * Only Prestashop 1.6
 */

class PaylaneGeneralModuleFrontController extends ModuleFrontController
{
    const STATUS_ERROR = 'ERROR';
    const STATUS_CLEARED = 'CLEARED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PERFORMED = 'PERFORMED';

    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cookie = Context::getContext()->cookie;
        $paymentType = $cookie->payment_type;

        if (!$paymentType) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if (method_exists('Tools', 'getAllValues')) {
            $params = Tools::getAllValues();
        } else {
            $params = $_POST + $_GET;
        }

        $status = $params['status'];
        $amount = $params['amount'];
        $currency = $params['currency'];
        $cartId = $params['description'];
        $hash = $params['hash'];
        $idSale = isset($params['id_sale']) ? $params['id_sale'] : null;
        $hashSalt = Configuration::get('PAYLANE_GENERAL_HASH');

        $hashCode = $hashSalt . '|' . $status . '|' . $cartId . '|';
        $hashCode .= $amount . '|' . $currency . '|' . $idSale;
        $hashComputed = sha1($hashCode);
        $orderStatus = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');

        if ($hash === $hashComputed) {
            if ($status === self::STATUS_PENDING) {
                $orderStatus = Configuration::get('PAYLANE_PAYMENT_STATUS_PENDING');
            }

            //if ($status === self::STATUS_PERFORMED) {
            //    $orderStatus = Configuration::get('paylane_order_performed_status');
            //}

            if ($status === self::STATUS_CLEARED || $status === self::STATUS_PERFORMED) {
                $orderStatus = Configuration::get('PS_OS_PAYMENT');
            }

            if ($status === self::STATUS_ERROR) {
                $orderStatus = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
            }
        } else {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        $currency = $this->context->currency;

        $extraVars = null;

        if (!is_null($idSale)) {
            $extraVars = array(
                'transaction_id' => $idSale
            );
        }

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $displayName = $this->module->displayName . ' | ' . Configuration::get('paylane_'.$paymentType.'_label');

        unset($cookie->payment_type);

        if( !($idOrder = Order::getOrderByCartId($cartId)) ) {
            $this->module->validateOrder(
                (int)$cartId,
                $orderStatus,
                $amount,
                $displayName,
                null,
                $extraVars,
                (int)$currency->id,
                false,
                $customer->secure_key
            );
        } else {
            $order = new Order($idOrder);
            $order->setCurrentState($orderStatus);
        }

        $redirectUrl = 'index.php?controller=order-confirmation&id_cart=';
        $redirectUrl .= (int)$cart->id.'&id_module='.(int)$this->module->id;
        $redirectUrl .= '&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
var_dump($redirectUrl); die;
        Tools::redirect($redirectUrl);
    }
}
