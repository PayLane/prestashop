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
require_once(dirname(__FILE__).'/paymentStatus.php');

class PaylaneValidationModuleFrontController extends ModuleFrontController
{
    protected $orderConfirmationUrl = 'index.php?controller=order-confirmation';

    public function isOldPresta()
    {
        return version_compare(_PS_VERSION_, '1.7', '<');
    }

    public function postProcess()
    {
        if ($this->isOldPresta()) {
            $this->postProcess16();
            return;
        }

        $cartId = (int)Tools::getValue('cart_id');
        PrestaShopLogger::addLog('process return url', 1, null, 'Cart', $cartId, true);
        $orderId = Order::getOrderByCartId($cartId);

        $payment = Tools::getValue('payment');
        $paymentParams = null;
        if (isset($payment) && isset($payment['additional_information'])) {
            $paymentParams = $payment['additional_information'];
        }
        if (isset($paymentParams['type'])) {
            require_once(_PS_MODULE_DIR_ . 'paylane/class/' . $paymentParams['type'] . '.php');
            $handler = new $paymentParams['type']();
            try {
                $responseStatus = $this->getResponseStatus();

                $result = $handler->handlePayment($paymentParams);

                if ($result['success']) {
                    $responseStatus['transaction_id'] = $result['id_sale'];
                    if (isset($result['order_status'])) {
                        $orderStatus = $result['order_status'];
                    } else {
                        $orderStatus = 'CLEARED';
                    }
                    $responseStatus['paylane_status'] = $orderStatus;
                    $responseStatus['status'] = PaylanePaymentCore::paymentStatus($responseStatus['paylane_status']);
                } else {
                    $errorStatus = PaylanePaymentCore::getErrorMessage(
                        array('error_text' => $result['error']['error_description'])
                    );
                    $this->redirectError($errorStatus);
                }

            } catch (Exception $e) {
                $errorStatus = PaylanePaymentCore::getErrorMessage(array('error_text' => $e->getMessage()));
                $this->redirectError($errorStatus);
            }
        } else {
            $responseStatus = $this->getResponseStatus();
        }
        PrestaShopLogger::addLog('Paylane - return url order ID:'. $orderId, 1, null, 'Cart', $cartId, true);


        $this->checkPaymentStatus($cartId, $responseStatus);
        if ($orderId) {
            PrestaShopLogger::addLog('validate order', 1, null, 'Cart', $cartId, true);
            $this->validateOrder($cartId, $responseStatus['transaction_id']);
        } else {
            PrestaShopLogger::addLog('prestashop order not found', 1, null, 'Cart', $cartId, true);
            // $this->checkPaymentStatus($cartId, $responseStatus);
        }
    }

    protected function getResponseStatus() {
        $responseStatus = array();
        $responseStatus['paylane_status'] = Tools::getValue('status');
        $responseStatus['status'] = PaylanePaymentCore::paymentStatus($responseStatus['paylane_status']);
        $responseStatus['amount'] = Tools::getValue('amount');
        $responseStatus['currency'] = Tools::getValue('currency');
        $responseStatus['description'] = Tools::getValue('description');
        $responseStatus['hash'] = Tools::getValue('hash');
        $responseStatus['transaction_id'] = Tools::getValue('id_sale');
        $responseStatus['payment_method'] = Tools::getValue('payment_method');
        $responseStatus['error_code'] = Tools::getValue('error_code');
        $responseStatus['error_text'] = Tools::getValue('error_text');

        return $responseStatus;
    }

    protected function validateOrder($cartId, $transactionId)
    {
        ;
        $order = $this->module->getOrderByTransactionId($transactionId);

        PrestaShopLogger::addLog('transaction log order : '.print_r($order, true), 1, null, 'Cart', $cartId, true);
        if (empty($order) || empty($order['order_status'])) {
            PrestaShopLogger::addLog('Paylane - status url late', 1, null, 'Cart', $cartId, true);
            $this->checkPaymentStatus($cartId, $transactionId);
        } elseif ($order['order_status'] == $this->module->failedStatus) {
            $paymentResponse = unserialize($order['payment_response']);
            $errorStatus = PaylanePaymentCore::getErrorMessage($paymentResponse);
            $this->redirectError($errorStatus);
        } else {
            if ($this->context->cart->OrderExists() == false) {
                $responseStatus = $this->getResponseStatus();
                PrestaShopLogger::addLog('Paylane - check order from return url', 1, null, 'Cart', $cartId, true);

                $this->checkPaymentStatus($cartId, $responseStatus);
            } else {
                PrestaShopLogger::addLog(
                    'Paylane - redirect success validate return url',
                    1,
                    null,
                    'Cart',
                    $cartId,
                    true
                );
                $this->redirectSuccess($cartId);
            }
        }
    }

    protected function checkPaymentStatus($cartId, $responseStatus)
    {
        $cart = $this->context->cart;
        $fieldParams = array();
        PrestaShopLogger::addLog('Paylane - check Payment Status', 1, null, 'Cart', $cartId, true);

        PrestaShopLogger::addLog(
            'Paylane - check payment status:'. print_r($responseStatus, true),
            1,
            null,
            'Cart',
            $cartId,
            true
        );

        if (isset($responseStatus) && $responseStatus['status'] !== '-2') {

            $PaymentStatus = new PaylanePaymentStatusModuleFrontController();

            $isTransactionLogValid = $PaymentStatus->isTransactionLogValid($responseStatus['transaction_id']);

            if (!$isTransactionLogValid) {
                $orderTotal = $responseStatus['amount'];
                $transactionLog = $PaymentStatus->setTransactionLog($orderTotal, $responseStatus);

                $generatedMd5Sig = $this->module->generateMd5sig($responseStatus);

                $isPaymentSignatureEqualsGeneratedSignature =
                   $this->module->isPaymentSignatureEqualsGeneratedSignature(
                        $responseStatus['hash'],
                        $generatedMd5Sig
                    );

                $generatedAntiFraudHash = $this->module->generateAntiFraudHash(
                    $cartId,
                    $responseStatus['payment_method'],
                    $cart->date_add
                );

                $isFraud = $this->module->isFraud($generatedAntiFraudHash, Tools::getValue('secure_method'));

                $additionalInformation =
                    $PaymentStatus->getAdditionalInformation(
                        $responseStatus,
                        $isPaymentSignatureEqualsGeneratedSignature,
                        $isFraud
                    );
                PrestaShopLogger::addLog(
                    'Paylane - save transaction log from return URL',
                    1,
                    null,
                    'Cart',
                    $cartId,
                    true
                );

                $PaymentStatus->saveTransactionLog($transactionLog, 0, $additionalInformation);

                $PaymentStatus->validatePayment($cartId, $responseStatus, $responseStatus['status']);
            }

            $this->redirectSuccess($cartId);

        } elseif (isset($responseStatus) && $responseStatus['status'] == '-2') {
            //LK
            /*
            $PaymentStatus = new PaylanePaymentStatusModuleFrontController();
            $currency = $this->context->currency;
            $customer = new Customer($cart->id_customer);

            $this->module->validateOrder(
                (int)$cart->id,
                $PaymentStatus->getPaymentStatus($responseStatus),
                $amount = sprintf('%01.2f', $cart->getOrderTotal()),
                $this->getPaymentName($responseStatus['payment_method']),
                null,
                array(),
                (int)$currency->id,
                false,
                $customer->secure_key
            );*/
            $order = new Order(Order::getOrderByCartId($cartId));
            $order->setCurrentState(Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED'));

            $errorStatus = PaylanePaymentCore::getErrorMessage($responseStatus);
            $this->redirectError($errorStatus);
        } else {
            $this->redirectPaymentReturn();
        }
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

    protected function redirectError($returnMessage)
    {
        $this->errors[] = $returnMessage;
        $this->redirectWithNotifications($this->context->link->getPageLink('order', true, null, array(
            'step' => '3')));
    }

    protected function redirectPaymentReturn()
    {
        die('sadgfddcgdfgfd');
        $url = $this->context->link->getModuleLink('paylane', 'paymentReturn', array(
            'secure_key' => $this->context->customer->secure_key), true);
        PrestaShopLogger::addLog('rediret to payment return : '.$url, 1, null, 'Cart', $this->context->cart->id, true);
        Tools::redirect($url);
        exit;
    }

    protected function redirectSuccess($cartId)
    {
        Tools::redirect(
            $this->orderConfirmationUrl.
            '&id_cart='.$cartId.
            '&id_module='.(int)$this->module->id.
            '&key='.$this->context->customer->secure_key
        );
    }

    public function postProcess16()
    {
        if (method_exists('Tools', 'getAllValues')) {
            $params = Tools::getAllValues();
        } else {
            $params = $_POST + $_GET;
        }

        if (isset($params['payment']) && isset($params['payment']['additional_information'])) {
            $paymentParams = $params['payment']['additional_information'];
        } else {
            $paymentParams = null;
        }
        $idSale = null;
        $orderStatus = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');

        $displayName = $this->module->displayName;

        if (isset($params['payment_type'])) {
            require_once(_PS_MODULE_DIR_ . 'paylane/class/' . $params['payment_type'] . '.php');
            $handler = new $params['payment_type']();
            $result = $handler->handlePayment($paymentParams);

            if ($result['success']) {
                $idSale = $result['id_sale'];

                if (isset($result['order_status'])) {
                    $orderStatus = $result['order_status'];
                } else {
                    $orderStatus = Configuration::get('PS_OS_PAYMENT');
                }
            }

            $paymentLabelPath = 'paylane_' . Tools::strtolower($params['payment_type']) . '_label';
            $displayName .=  ' | ' . Configuration::get($paymentLabelPath);
        }

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        $currency = $this->context->currency;
        $amount = sprintf('%01.2f', $cart->getOrderTotal());

        $extraVars = null;

        if (!is_null($idSale)) {
            $extraVars = array(
                'transaction_id' => $idSale
            );
        }

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->module->validateOrder(
            (int)$cart->id,
            $orderStatus,
            $amount,
            $displayName,
            null,
            $extraVars,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        $redirectUrl = 'index.php?controller=order-confirmation&id_cart=';
        $redirectUrl .= (int)$cart->id.'&id_module='.(int)$this->module->id;
        $redirectUrl .= '&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        Tools::redirect($redirectUrl);
    }
}
