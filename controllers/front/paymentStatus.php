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

class PaylanePaymentStatusModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $status = Tools::getValue('status');
        if ($status) {
            $cartId = Tools::getValue('cart_id');
            $orderId = Order::getOrderByCartId($cartId);
            Context::getContext()->cart = new Cart((int)$cartId);

            PrestaShopLogger::addLog('Paylane - use status_url', 1, null, 'Cart', $cartId, true);
            PrestaShopLogger::addLog('Paylane - get payment response from status_url', 1, null, 'Cart', $cartId, true);
            $paymentResponse = $this->getPaymentResponse();
            $messageLog = 'Paylane - payment response from status_url : ' . print_r($paymentResponse, true);
            PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cartId, true);

            $orderId = Order::getOrderByCartId($cartId);

            $isTransactionLogValid = $this->isTransactionLogValid($paymentResponse['transaction_id']);
            $order = $this->module->getOrderByTransactionId($paymentResponse['transaction_id']);
            if (!$isTransactionLogValid) {
                $cart = $this->context->cart;

                $orderTotal = $paymentResponse['amount'];
                $transactionLog = $this->setTransactionLog($orderTotal, $paymentResponse);
                $generatedMd5Sig = $this->module->generateMd5sig($paymentResponse);
                $isPaymentSignatureEqualsGeneratedSignature = $this->module->isPaymentSignatureEqualsGeneratedSignature(
                    $paymentResponse['md5sig'],
                    $generatedMd5Sig
                );
                $generatedAntiFraudHash = $this->module->generateAntiFraudHash(
                    $cartId,
                    $this->getPaymentMethod(),
                    $cart->date_add
                );
                $isFraud = $this->module->isFraud($generatedAntiFraudHash, Tools::getValue('secure_payment'));

                $additionalInformation =
                    $this->getAdditionalInformation(
                        $paymentResponse,
                        $isPaymentSignatureEqualsGeneratedSignature,
                        $isFraud
                    );
                PrestaShopLogger::addLog(
                    'Paylane - save transaction log from status URL',
                    1,
                    null,
                    'Cart',
                    $cartId,
                    true
                );
                $this->saveTransactionLog($transactionLog, 0, $additionalInformation);

                if ($orderId) {
                    $order = $this->module->getOrderByTransactionId($paymentResponse['transaction_id']);

                    $messageLog = 'Paylane - use status_url on existed order';
                    PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $orderId, true);

                    if ($order['order_status'] == $this->module->pendingStatus) {
                        $messageLog = 'Paylane - use status_url on pending status';
                        PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $orderId, true);

                        $this->updateTransactionLog($paymentResponse, $order['id_order']);
                        $this->module->updatePaymentStatus($order['id_order'], $paymentResponse['status']);
                    }
                    die('ok');
                }
                $this->validatePayment($cartId, $paymentResponse);
            }
        } else {
            $messageLog = 'Paylane - no payment response from gateway';
            PrestaShopLogger::addLog($messageLog, 3, null, 'Cart', 0, true);
            die('no response from gateway.');
        }
        die('end');
    }

    public function validatePayment($cartId, $paymentResponse, $status = '')
    {
        Context::getContext()->cart = new Cart((int)$cartId);
        $cart = $this->context->cart;
        Context::getContext()->currency = new Currency((int)$cart->id_currency);
        $customer = new Customer($cart->id_customer);

        $messageLog =
            'Paylane - Module Status : '. $this->module->active .
            ', Customer Id : '. $cart->id_customer .
            ', Delivery Address : '. $cart->id_address_delivery .
            ', Invoice Address : '. $cart->id_address_invoice;

        PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cart->id, true);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0 || !$this->module->active
            || !Validate::isLoadedObject($customer)) {
            PrestaShopLogger::addLog('Paylane - customer datas are not valid', 3, null, 'Cart', $cart->id, true);
            die('Erreur etc.');
        }

        $this->processSuccessPayment($customer, $paymentResponse, $status);
    }

    protected function processSuccessPayment($customer, $paymentResponse, $status)
    {
        $cart = $this->context->cart;
        $cartId = $cart->id;
        $currency = $this->context->currency;

        if ($paymentResponse['status'] == $this->module->clearedStatus
            || $paymentResponse['status'] == $this->module->pendingStatus
            || $paymentResponse['status'] == $this->module->performedStatus
        ) {
            $generatedMd5Sig = $this->module->generateMd5sig($paymentResponse);
            $isPaymentSignatureEqualsGeneratedSignature =
                $this->module->isPaymentSignatureEqualsGeneratedSignature(
                    $paymentResponse['hash'],
                    $generatedMd5Sig
                );

            $generatedAntiFraudHash =
                $this->module->generateAntiFraudHash($cartId, $this->getPaymentMethod(), $cart->date_add);
            $isFraud = false;
            if (!empty(Tools::getValue('payment_key'))) {
                $isFraud = $this->module->isFraud($generatedAntiFraudHash, Tools::getValue('payment_key'));
            }

            if (!$isPaymentSignatureEqualsGeneratedSignature && !empty($paymentResponse['hash'])) {
                $paymentResponse['status'] = $this->module->failedStatus;
                $messageLog = 'Paylane - invalid credential detected';
                PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cartId, true);
            } elseif ($isFraud) {
                $paymentResponse['status'] = $this->module->fraudStatus;
                $messageLog = 'Paylane - fraud detected';
                PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cartId, true);
            }

        } else {
            $errorMessage = 'PAYLANE_ERROR_99_GENERAL';
            if ($paymentResponse['status'] == $this->module->failedStatus
                && isset($paymentResponse['error_code'])) {
                $errorMessage = $this->getErrorMessage($paymentResponse);
            }

            $messageLog = 'Paylane - order has not been successfully created : '. $errorMessage;
            PrestaShopLogger::addLog($messageLog, 3, null, 'Cart', $cartId, true);
            die('payment failed');
        }

        $orderTotal = $paymentResponse['amount'];
        $transactionLog = $this->setTransactionLog($orderTotal, $paymentResponse);
        PrestaShopLogger::addLog('Paylane - get payment status', 1, null, 'Cart', $cartId, true);

        if (!empty($status)) {
            $paymentResponse['status'] = $status;
        }

        $paymentStatus = $this->getPaymentStatus($paymentResponse);
        PrestaShopLogger::addLog('Paylane - payment status : '. $paymentStatus, 1, null, 'Cart', $cartId, true);
        
        if(!($idOrder = Order::getOrderByCartId($cartId)) ) {
            $this->module->validateOrder(
                $cartId,
                $paymentStatus,
                $transactionLog['amount'],
                $transactionLog['payment_name'],
                null,
                array(),
                $currency->id,
                false,
                $customer->secure_key
            );
        }
        // } else {
        //     $order = new Order($idOrder);
        //     $order->setCurrentState($paymentStatus);
        // }

        $orderId = $this->module->currentOrder;
        $this->context->cookie->paylane_paymentName = $transactionLog['payment_name'];

        $serializedResponse = serialize($paymentResponse);
        $this->updateTransactionLog(
            $paymentResponse['transaction_id'],
            $paymentResponse,
            $serializedResponse,
            $orderId
        );

        $messageLog = 'Paylane - order ('. $orderId .') has been successfully created';
        PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $cartId, true);
    }

    protected function getPaymentResponse()
    {
        $paymentResponse = array();
        foreach ($_REQUEST as $parameter => $value) {
            $parameter = Tools::strtolower($parameter);
            $paymentResponse[$parameter] = $value;
        }

        $paymentResponse['paylane_status'] = $paymentResponse['status'];
        $paymentResponse['status'] = PaylanePaymentCore::paymentStatus($paymentResponse['paylane_status']);

        return $paymentResponse;
    }

    public function getPaymentStatus($paymentResponse)
    {
        switch ($paymentResponse['status']) {
            case $this->module->pendingStatus:
                return Configuration::get('PAYLANE_PAYMENT_STATUS_PENDING');
            case $this->module->performedStatus:
                return Configuration::get('PAYLANE_PAYMENT_STATUS_PERFORMED');
            case $this->module->failedStatus:
                return Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
            default:
                return Configuration::get('PS_OS_PAYMENT');
        }
    }

    public function setTransactionLog($orderTotal, $paymentResponse)
    {
        $transactionLog = array();
        $transactionLog['transaction_id'] = $paymentResponse['transaction_id'];

        $transactionLog['payment_type'] = $this->getPaymentType($paymentResponse);

        $transactionLog['payment_method'] = 'PAYLANE_FRONTEND_PM_'.$this->getPaymentMethod();
        $transactionLog['payment_name'] = $this->getPaymentName($transactionLog['payment_type']);

        $transactionLog['status'] = $paymentResponse['status'];
        $transactionLog['currency'] = $paymentResponse['currency'];
        $transactionLog['amount'] = $this->getPaymentAmount($orderTotal, $paymentResponse);
        $transactionLog['payment_response'] = serialize($paymentResponse);

        return $transactionLog;
    }

    protected function getPaymentMethod()
    {
        return (Tools::getValue('payment_method')) ? Tools::getValue('payment_method') : Tools::getValue('payment_type');
    }

    protected function getPaymentType($paymentResponse)
    {
        return $paymentResponse['payment_method'];
    }

    protected function getPaymentName($paymentType)
    {
        $paymentMethod = PaylanePaymentCore::getPaymentMethodByPaymentType($paymentType);
        if ($this->module->l('PAYLANE_FRONTEND_PM_'.$paymentType) == 'PAYLANE_FRONTEND_PM_'.$paymentType) {
            $paymentName = $paymentMethod['name'];
        } else {
            $paymentName = $this->module->l('PAYLANE_FRONTEND_PM_'.$paymentType);
        }

        $isPaylane = strpos($paymentName, 'Paylane');
        if ($isPaylane === false) {
            $paymentName = 'Paylane '.$paymentName;
        }

        return $paymentName;
    }

    protected function getPaymentAmount($orderTotal, $paymentResponse)
    {
        if (!empty($paymentResponse['amount'])) {
            return $paymentResponse['amount'];
        }
        return $orderTotal;
    }

    public function getAdditionalInformation($paymentResponse, $isFraud)
    {
        $additionalInfo = array();
        if (isset($paymentResponse['ip_country'])) {
            $additionalInfo['PAYLANE_BACKEND_ORDER_ORIGIN'] = $paymentResponse['ip_country'];
        }
        if ($isFraud) {
            $additionalInfo['BACKEND_TT_FRAUD'] = $paymentResponse['status'];
        }

        return serialize($additionalInfo);
    }

    public function saveTransactionLog($transactionLog, $orderId, $additionalInformation)
    {
        $sql = "INSERT INTO "._DB_PREFIX_."endora_paylane_order_ref (
            id_order,
            transaction_id,
            payment_method,
            order_status,
            ref_id,
            payment_code,
            currency,
            amount,
            add_information,
            payment_response
        )
        VALUES "."('".
            (int)$orderId."','".
            pSQL($transactionLog['transaction_id'])."','".
            pSQL($transactionLog['payment_method'])."','".
            pSQL($transactionLog['status'])."','".
            pSQL($transactionLog['transaction_id'])."','".
            pSQL($transactionLog['payment_type'])."','".
            pSQL($transactionLog['currency'])."','".
            (float)$transactionLog['amount']."','".
            pSQL($additionalInformation)."','".
            pSQL($transactionLog['payment_response']).
        "')";

        PrestaShopLogger::addLog('Paylane - save transaction log : ' . $sql, 1, null, 'Order', $orderId, true);

        if (!Db::getInstance()->execute($sql)) {
            PrestaShopLogger::addLog('Paylane - failed when saving transaction log', 3, null, 'Order', $orderId, true);
            die('Erreur etc.');
        }
        PrestaShopLogger::addLog('Paylane - transaction log succefully saved', 1, null, 'Order', $orderId, true);
    }

    protected function updateTransactionLog($transactionId, $paymentResponse, $serializedResponse, $orderId)
    {
        $sql = "UPDATE "._DB_PREFIX_."endora_paylane_order_ref SET
            id_order = '".pSQL($orderId)."',
            order_status = '".pSQL($paymentResponse['status'])."',
            payment_response = '".pSQL($serializedResponse)."'
            where transaction_id = '".pSQL($transactionId)."'";

        $messageLog = 'Paylane - update payment response from status_url : ' . $sql;
        PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $orderId, true);

        if (!Db::getInstance()->execute($sql)) {
            $messageLog = 'Paylane - failed when updating payment response from status_url';
            PrestaShopLogger::addLog($messageLog, 3, null, 'Order', $orderId, true);
            die('Erreur etc.');
        }
        PrestaShopLogger::addLog('Paylane - status_url response succefully updated', 1, null, 'Order', $orderId, true);
    }

    public function isTransactionLogValid($transactionId)
    {
        $order = $this->module->getOrderByTransactionId($transactionId);
        // var_dump($order);exit;
        $messageLog = 'Paylane - existing order : ' . print_r($order, true);
            PrestaShopLogger::addLog($messageLog, 1, null, 'Cart', $this->context->cart->id, true);
        
        if (!empty($order)) {
            return true;
        } else {
            return false;
        }
    }
}
