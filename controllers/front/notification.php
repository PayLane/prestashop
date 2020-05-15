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

class Order16 extends Order {
    public static function getOrderByCartId_Paylane($id_cart)
    {
        $sql = 'SELECT *
				FROM `'._DB_PREFIX_.'orders`
				WHERE `id_cart` = '.(int)$id_cart
             .Shop::addSqlRestriction();
        $result = Db::getInstance()->getRow($sql);

        return isset($result['id_order']) ? new self((int) $result['id_order']) : null;
    }
}


class PaylaneNotificationModuleFrontController extends ModuleFrontController
{
    const NOTIFICATION_TYPE_SALE = 'S';
    const NOTIFICATION_TYPE_REFUND = 'R';

    public $ssl = true;

    public function initContent()
    {
        if ($this->isOldPresta()) {
            parent::initContent();
        }
        
        if (method_exists('Tools', 'getAllValues')) {
            $params = Tools::getAllValues();
        } else {
            $params = $_POST + $_GET;
        }

        if (!empty(Configuration::get('PAYLANE_NOTIFICATION_USER')) && !empty(Configuration::get('PAYLANE_NOTIFICATION_PASSWORD'))) {
            if (!isset($_SERVER['PHP_AUTH_USER'])
                || !isset($_SERVER['PHP_AUTH_PW'])
                || Configuration::get('PAYLANE_NOTIFICATION_USER') != $_SERVER['PHP_AUTH_USER']
                || Configuration::get('PAYLANE_NOTIFICATION_PASSWORD') != $_SERVER['PHP_AUTH_PW']) {
                $this->failAuthorization();
            }
        }

        if (empty($params['communication_id'])) {
            die('Empty communication id');
        }

        if (!empty($params['token']) && Configuration::get('PAYLANE_NOTIFICATION_TOKEN') !== $params['token']) {
            die('Wrong token');
        }

        $this->handleAutoMessages($params['content']);

        die($params['communication_id']);
    }

    protected function failAuthorization()
    {
        // authentication failed
        header("WWW-Authenticate: Basic realm=\"Secure Area\"");
        header("HTTP/1.0 401 Unauthorized");
        exit();
    }

    public function isOldPresta()
    {
        return version_compare(_PS_VERSION_, '1.7', '<');
    }

    protected function handleAutoMessages($messages)
    {
        foreach ($messages as $message) {
            if (empty($message['text'])) {
                // Message without Prestashop cart ID - skip
                continue;
            }

            $idSale = $message['id_sale'];
            $cartId = $message['text'];
            $amount = $message['amount'];
            $currCode = $message['currency_code'];
            $transType = $message['type'];

            // Get order by id_cart
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            if ($this->isOldPresta()) {
                $order = Order16::getOrderByCartId_Paylane($cartId);
            } else {
                $order = Order::getByCartId($cartId);
            }

            if (empty($order))
            {
                $cart = new Cart($cartId);
                $customer = new Customer($cart->id_customer);
                $currency = new Currency($cart->id_currency);
                $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

                $this->module->validateOrder(
                    $cart->id,
                    Configuration::get('PS_OS_PAYMENT'),
                    $total,
                    $this->module->displayName,
                    null,
                    array(),
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );

                $orderId = Order::getByCartId($cartId);

                $db->update('order_payment', array(
                    'transaction_id' => $idSale,
                    'payment_method' => $orderId->payment
                ), 'order_reference = "'.$orderId->reference.'"');
            }

            if (!empty($order)) {
                if ((float) $order->total_paid !== (float) $amount) {
                    die('Wrong amount');
                }

                switch ($message['type']) {
                case self::NOTIFICATION_TYPE_SALE:
                    $orderStatus = Configuration::get('PAYLANE_PAYMENT_STATUS_CLEARED');
                
                    $order->setCurrentState($orderStatus);
                    $db->update('order_payment', array(
                        'transaction_id' => $idSale,
                        'payment_method' => $order->payment
                    ), 'order_reference = "'.$order->reference.'"');
                    break;
                case self::NOTIFICATION_TYPE_REFUND:
                    break;

                default:
                    die('Unrecognized message type (' . $message['type'] . ')');
                }
            }
        }
    }

}