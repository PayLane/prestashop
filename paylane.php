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

require_once(dirname(__FILE__).'/core/core.php');

// prestashop 1.7  // more test
//use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

// inject payment methods classes
require_once(_PS_MODULE_DIR_ . 'paylane/class/SecureForm.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/CreditCard.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/BankTransfer.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/PaylanePayPal.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/DirectDebit.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/Sofort.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/Ideal.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/ApplePay.php');

class Paylane extends PaymentModule
{
    protected $paymentClassMethods = array(
        'SecureForm',
        'CreditCard',
        'BankTransfer',
        'PaylanePayPal',
        'DirectDebit',
        'Sofort',
        'Ideal',
        'ApplePay'
    );
    protected $formBuilder = array();
    protected $html = '';
    public $pendingStatus = '1';
    public $performedStatus = '2';
    public $clearedStatus = '3';
    public $failedStatus = '-2';
    protected $selectedTab = false;
    protected $paylaneSignUpUrl = 'https://paylane.pl/wyprobuj';

    protected $paymentMethodShowTitleLogo = array();

    public function isOldPresta()
    {
        return version_compare(_PS_VERSION_, '1.7', '<');
    }


    public function __construct()
    {
        $this->name = 'paylane';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.3';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->author = 'Paylane';
        $this->module_key = '6f71ca0e0e3465122dfdfeb5d3a43a18';
        $this->paymentMethodShowTitleLogo = array();

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = 'Paylane';
        $this->description = 'Accepts payments by Paylane';
        if ($this->l('BACKEND_TT_DELETE_DETAILS') == "BACKEND_TT_DELETE_DETAILS") {
            $this->confirmUninstall =  "Are you sure you want to delete your details ?";
        } else {
            $this->confirmUninstall = $this->l('BACKEND_TT_DELETE_DETAILS');
        }
        foreach ($this->paymentClassMethods as $method) {
            $paymentMethod = new $method();
            $this->formBuilder[$method] = $paymentMethod->getPaymentConfig();
        }
    }

    public function install()
    {
        $this->warning = null;
        if (is_null($this->warning)
            && !(parent::install()
              && $this->registerHook('paymentReturn')
              && $this->registerHook('displayAdminOrder')
              && $this->registerHook('header')
              && $this->registerHook('paymentTop')
              && $this->registerHook($this->isOldPresta() ? 'payment' : 'paymentOptions'))) {
            if ($this->l('ERROR_MESSAGE_INSTALL_MODULE') == "ERROR_MESSAGE_INSTALL_MODULE") {
                $this->warning = "There was an Error installing the module.";
            } else {
                $this->warning = $this->l('ERROR_MESSAGE_INSTALL_MODULE');
            }
        }
        if (is_null($this->warning) && !$this->createOrderRefTables()) {
            if ($this->l('ERROR_MESSAGE_CREATE_TABLE') == "ERROR_MESSAGE_CREATE_TABLE") {
                $this->warning = "There was an Error creating a custom table.";
            } else {
                $this->warning = $this->l('ERROR_MESSAGE_CREATE_TABLE');
            }
        }
        if (is_null($this->warning) && !$this->createEndoraCards()) {
            if ($this->l('ERROR_MESSAGE_CREATE_TABLE') == "ERROR_MESSAGE_CREATE_TABLE") {
                $this->warning = "There was an Error creating a custom table.";
            } else {
                $this->warning = $this->l('ERROR_MESSAGE_CREATE_TABLE');
            }
        }
        if (is_null($this->warning) && !$this->addPaylaneOrderStatus()) {
            if ($this->l('ERROR_MESSAGE_CREATE_ORDER_STATUS') == "ERROR_MESSAGE_CREATE_ORDER_STATUS") {
                $this->warning = "There was an Error creating a custom order status.";
            } else {
                $this->warning = $this->l('ERROR_MESSAGE_CREATE_ORDER_STATUS');
            }
        }
        // default paylane setting.
        Configuration::updateValue('PAYLANE_GENERAL_MERCHANTID', '');
        Configuration::updateValue('PAYLANE_GENERAL_HASH', '');
        Configuration::updateValue('PAYLANE_GENERAL_LOGIN_API', '');
        Configuration::updateValue('PAYLANE_GENERAL_PUBLIC_KEY_API', '');
        Configuration::updateValue('PAYLANE_GENERAL_PASSWORD_API', '');

        // notification
        Configuration::updateValue('PAYLANE_NOTIFICATION_URL', $this->context->link->getModuleLink($this->name, 'notification', array(), true));
        Configuration::updateValue('PAYLANE_NOTIFICATION_USER', '');
        Configuration::updateValue('PAYLANE_NOTIFICATION_PASSWORD', '');
        Configuration::updateValue('PAYLANE_NOTIFICATION_TOKEN', '');

        foreach ($this->formBuilder as $formGroup) {
            foreach ($formGroup as $name => $options) {
                Configuration::updateValue($name, isset($options["default"]) ? $options["default"] : null);
            }
        }

        $defaultSort = 1;
        foreach (array_keys(PaylanePaymentCore::getPaymentMethods()) as $paymentType) {
            Configuration::updateValue('PAYLANE_'.$paymentType.'_ACTIVE', '0');
            $defaultSort++;
        }

        return is_null($this->warning);
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('PAYLANE_GENERAL_MERCHANTID')
            || !Configuration::deleteByName('PAYLANE_GENERAL_HASH')

            || !Configuration::deleteByName('PAYLANE_GENERAL_LOGIN_API')
            || !Configuration::deleteByName('PAYLANE_GENERAL_PUBLIC_KEY_API')
            || !Configuration::deleteByName('PAYLANE_GENERAL_PASSWORD_API')

            || !Configuration::deleteByName('PAYLANE_SECUREFORM_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_CREDITCARD_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_BANKTRANSFER_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_PAYPAL_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_DIRECTDEBIT_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_SOFORT_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_IDEAL_ACTIVE')
            || !Configuration::deleteByName('PAYLANE_APPLEPAY_ACTIVE')

            || !Configuration::deleteByName('PAYLANE_NOTIFICATION_URL')
            || !Configuration::deleteByName('PAYLANE_NOTIFICATION_USER')
            || !Configuration::deleteByName('PAYLANE_NOTIFICATION_PASSWORD')
            || !Configuration::deleteByName('PAYLANE_NOTIFICATION_TOKEN')

            || !$this->unregisterHook('paymentReturn')
            || !$this->unregisterHook('displayAdminOrder')
            || !$this->unregisterHook('header')
            || !$this->unregisterHook('paymentTop')
            || !$this->unregisterHook($this->isOldPresta() ? 'payment' : 'paymentOptions')
            || !parent::uninstall()) {

            foreach ($this->formBuilder as $formGroup) {
                foreach ($formGroup as $name => $options) {
                    if (!Configuration::deleteByName($name)) {
                        return false;
                    }
                }
            }

            return false;
        }
        return true;
    }

    public function createOrderRefTables()
    {
        $sql= 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'endora_paylane_order_ref`(
            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(10) NOT NULL,
            `transaction_id` VARCHAR(32) NOT NULL,
            `payment_method` VARCHAR(50) NOT NULL,
            `order_status` VARCHAR(2) NOT NULL,
            `ref_id` VARCHAR(32) NOT NULL,
            `payment_code` VARCHAR(8) NOT NULL,
            `currency` VARCHAR(3) NOT NULL,
            `amount` decimal(17,2) NOT NULL,
            `add_information` LONGTEXT NULL,
            `payment_response` LONGTEXT NULL,
            `refund_response` LONGTEXT NULL)';

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }
        return true;
    }

    public function createEndoraCards()
    {
        $sql= 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'endora_paylane_cards` (
                `id_sale` int(10) unsigned NOT NULL,
                `customer_id` int(10) unsigned NOT NULL,
                `credit_card_number` varchar(255) NOT NULL,
                PRIMARY KEY (`id_sale`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;';

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }
        return true;
    }

    public function addOrderStatus($configKey, $statusName, $stateConfig)
    {
        if (!Configuration::get($configKey)) {
            $orderState = new OrderState();
            $orderState->name = array();
            $orderState->module_name = $this->name;
            $orderState->send_email = true;
            $orderState->color = $stateConfig['color'];
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = true;
            $orderState->invoice = false;
            $orderState->paid = false;
            foreach (Language::getLanguages() as $language) {
                $orderState->template[$language['id_lang']] = 'payment';
                $orderState->name[$language['id_lang']] = $statusName;
            }

            if ($orderState->add()) {
                $paylaneIcon = dirname(__FILE__).'/logo.gif';
                $newStateIcon = dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif';
                copy($paylaneIcon, $newStateIcon);
            }

            Configuration::updateValue($configKey, (int)$orderState->id);
        }
    }

    public function addPaylaneOrderStatus()
    {
        $stateConfig = array();
        try {
            $stateConfig['color'] = 'blue';
            $this->addOrderStatus(
                'PAYLANE_PAYMENT_STATUS_PENDING',
                    'Pending',
                    $stateConfig
            );
            $stateConfig['color'] = 'blue';
            $this->addOrderStatus(
                'PAYLANE_PAYMENT_STATUS_PERFORMED',
                    'Performed',
                    $stateConfig
            );
            $stateConfig['color'] = '#72c279';
            $this->addOrderStatus(
                'PAYLANE_PAYMENT_STATUS_CLEARED',
                    'Cleared',
                    $stateConfig
            );
            $stateConfig['color'] = 'red';
            $this->addOrderStatus(
                'PAYLANE_PAYMENT_STATUS_FAILED',
                    'Error',
                    $stateConfig
            );
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function hookdisplayAdminOrder()
    {
        $orderId = Tools::getValue('id_order');
        $sql = "SELECT * FROM "._DB_PREFIX_."endora_paylane_order_ref WHERE id_order ='".(int)$orderId."'";
        $row = Db::getInstance()->getRow($sql);
        if ($row) {
            $paymentInfo = array();
            $backendPaymentMethod = str_replace('FRONTEND', 'BACKEND', $row['payment_method']);
            $paymentInfo['name'] = $this->getBackendPaymentLocale($backendPaymentMethod);
            $isPaylane = strpos($paymentInfo['name'], 'Paylane');
            if ($isPaylane === false && $backendPaymentMethod != 'PAYLANE_BACKEND_PM_FLEXIBLE') {
                $paymentInfo['name'] = 'Paylane '.$paymentInfo['name'];
            }
            $trnStatus = PaylanePaymentCore::getTrnStatus($row['order_status']);
            $paymentInfo['status'] = $this->getTrnStatusLocale($trnStatus);
            $paymentInfo['method'] = $this->getFrontendPaymentLocale('PAYLANE_FRONTEND_PM_'.$row['payment_code']);
            $paymentInfo['currency'] = $row['currency'];

            $additionalInformation = unserialize($row['add_information']);
            $langId = Context::getContext()->language->id;
            if (isset($additionalInformation['PAYLANE_BACKEND_ORDER_ORIGIN'])) {
                $orderOriginId = $this->getCountryIdByIso($additionalInformation['PAYLANE_BACKEND_ORDER_ORIGIN']);
                $paymentInfo['order_origin'] = Country::getNameById($langId, $orderOriginId);
            }
            if (isset($additionalInformation['PAYLANE_BACKEND_ORDER_COUNTRY'])) {
                $orderCountryId = $this->getCountryIdByIso($additionalInformation['PAYLANE_BACKEND_ORDER_COUNTRY']);
                $paymentInfo['order_country'] = Country::getNameById($langId, $orderCountryId);
            }
            $paymentInfo['transaction_id'] = $row['transaction_id'];

            $this->context->smarty->assign(array(
                'orderId' => (int)$orderId,
                    'paymentInfo' => $paymentInfo
            ));

            return $this->display(__FILE__, 'views/templates/hook/displayAdminOrder.tpl');
        }
        return '';
    }

    public function updatePaymentStatus($orderId, $orderStatus)
    {
        $orderStatusId = false;
        if ($orderStatus == $this->clearedStatus) {
            $orderStatusId = Configuration::get('PS_OS_PAYMENT');
            $status = 'CONFIRMED';
            $template = 'order_confirmed';
        } elseif ($orderStatus == $this->pendingStatus) {
            $orderStatusId = Configuration::get('PAYLANE_PAYMENT_STATUS_PENDING');
        } elseif ($orderStatus == $this->performedStatus) {
            $orderStatusId = Configuration::get('PAYLANE_PAYMENT_STATUS_PERFORMED');
        } elseif ($orderStatus == $this->failedStatus) {
            $orderStatusId = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
        }

        $messageLog = 'Paylane - update payment status : ' . $orderStatusId;
        PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $orderId, true);

        if ($orderStatusId) {
            $order = new Order($orderId);
            $history = new OrderHistory();
            $history->id_order = (int)$orderId;
            $history->id_employee = isset($this->context->employee->id) ? (int)$this->context->employee->id : 0;

            $useExistingsPayment = false;
            if (!$order->hasInvoice()) {
                $useExistingsPayment = true;
            }
            $history->changeIdOrderState((int)($orderStatusId), $order, $useExistingsPayment);
            $history->addWithemail();

            PrestaShopLogger::addLog('Paylane - payment status succefully updates', 1, null, 'Order', $orderId, true);
        }
    }

    private function getCountryIdByIso($countryIso)
    {
        if (Tools::strlen($countryIso) == 3) {
            $countryIso = PaylanePaymentCore::getCountryIso2ByIso3($countryIso);
        }

        $sql = "SELECT `id_country` FROM `"._DB_PREFIX_."country` WHERE `iso_code` = '".pSQL($countryIso)."'";
        $result = Db::getInstance()->getRow($sql);
        return (int)$result['id_country'];
    }

    private function getEnabledPayments()
    {
        $address = new Address((int)$this->context->cart->id_address_delivery);
        $country = new Country($address->id_country);
        $countryCode = $country->iso_code;
        $supportedPayments = PaylanePaymentCore::getSupportedPaymentsByCountryCode($countryCode);

        $paymentSort = 1000;
        $paymentsConfig = array();
        $paymentMethods = PaylanePaymentCore::getPaymentMethods();

        foreach ($supportedPayments as $paymentType) {
            $isActive = Configuration::get('PAYLANE_'.$paymentType.'_ACTIVE');

            if ($isActive) {
                $paymentsConfig[$paymentSort] = array(
                    'name' => Tools::strtolower($paymentType),
                    'label' => Configuration::get(
                        'paylane_'.Tools::strtolower($paymentType).'_label'
                    ),
                    'showimg' => (bool)Configuration::get(
                        'paylane_'.Tools::strtolower($paymentType).'_showImg'
                    ),
                );
                if (isset($paymentMethods[$paymentType]['logos'])) {
                    $paymentsConfig[$paymentSort]['logos'] = $paymentMethods[$paymentType]['logos'];
                }
            }
            $paymentSort++;
        }
        ksort($paymentsConfig);

        return $paymentsConfig;
    }

    public function checkCurrency($cart)
    {
        $currencyOrder = new Currency($cart->id_currency);
        $currencyModules = $this->getCurrency($cart->id_currency);

        if (is_array($currencyModules)) {
            foreach ($currencyModules as $currencyModule) {
                if ($currencyOrder->id == $currencyModule['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookHeader($parameters)
    {
        if ($this->isOldPresta()) {
            $this->context->controller->addCSS(($this->_path).'views/css/payment_options.css', 'all');
            $this->context->controller->addCSS(($this->_path).'views/css/forms.css', 'all');
            $this->context->controller->addJs('https://js.paylane.com/v1/', 'all');
        } else {
            $this->context->controller->addCSS(($this->_path).'views/css/payment_options.css', 'all');
            $this->context->controller->addCSS(($this->_path).'views/css/forms.css', 'all');
            $this->context->controller->registerJavascript('remote-paylane-js', 'https://js.paylane.com/v1/', array(
                'server' => 'remote', 'position' => 'head', 'priority' => 20
            ));
        }
    }

    // Presta 1.6
    public function hookPayment($parameters)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($parameters['cart'])) {
            return;
        }

        $paymentTemplate = '';
        $enabledPayments = $this->getEnabledPayments();

        foreach ($enabledPayments as $value) {
            foreach ($this->paymentClassMethods as $method) {
                $name = $value['name'];
                if ($value['name'] === 'paypal') {
                    $name = 'paylanepaypal';
                }
                if ($name === strtolower($method)) {
                    $paymentMethod = new $method();
                    if ($method === 'SecureForm') {
                        $paymentTemplate .= $paymentMethod->generatePaymentLinkTemplate($parameters);
                    } else {
                        $paymentTemplate .= $paymentMethod->generatePaymentLinkTemplate();
                    }
                }
            }
        }

        return $paymentTemplate;
    }

    public function hookPaymentTop($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->isOldPresta()) {
            return $this->fetch('module:paylane/views/templates/hook/payment_handler.tpl');
        } else {
            return;
        }
    }

    public function hookPaymentOptions($parameters)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($parameters['cart'])) {
            return;
        }

        $paymentOptions = array();
        $enabledPayments = $this->getEnabledPayments();

        foreach ($enabledPayments as $value) {
            //$newOption = new PaymentOption(); // 1.7
            $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption(); // 1.6
            $paymentController = $this->context->link->getModuleLink(
                $this->name,
                    'payment'.Tools::ucfirst($value['name']),
                    array(),
                    true
            );
            $logo = Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/'.$value['name'].'.jpg');

            $logoHtml = '';
            $paymentName = '';

            $newOption  ->setCallToActionText($value['label'])
                ->setAction($paymentController)
                ->setAdditionalInformation($logoHtml);
            if ($value['showimg']) {
                $newOption->setLogo($logo);
            }

            $paymentOptions[] = $newOption;
        }

        return $paymentOptions;
    }

    public function hookPaymentReturn($parameters)
    {
        if (!$this->active) {
            return;
        }

        if ($this->isOldPresta()) {
            $order= $parameters['objOrder'];
        } else {
            $order= $parameters['order'];
        }

        $state = $order->getCurrentState();
        PrestaShopLogger::addLog(
            'Paylane - State payment return: '.$state,
                1,
                null,
                'cart',
                $this->context->cart->id,
                true
        );
        $template = '';

        if ($state == Configuration::get('PS_OS_PAYMENT')
            || $state == Configuration::get('PAYLANE_PAYMENT_STATUS_PENDING')
            || $state == Configuration::get('PAYLANE_PAYMENT_STATUS_PERFORMED')
            || $state == Configuration::get('PAYLANE_PAYMENT_STATUS_CLEARED')
            || $state == 0) {
            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                    'status' => 'ok'
            ));
            if (isset($order->reference) && !empty($order->reference)) {
                $this->smarty->assign('reference', $order->reference);
            }
            $status='SUCCESFUL';
            $template='order_successful';

        }

        unset($this->context->cookie->paylane_paymentName);

        if ($this->isOldPresta()) {
            return $this->display(__FILE__, 'payment_return16.tpl');
        } else {
            return $this->display(__FILE__, 'payment_return.tpl');
        }

    }

    public function setNumberFormat($number)
    {
        $number = (float) str_replace(',', '.', $number);
        return number_format($number, 2, '.', '');
    }

    public function isPaymentSignatureEqualsGeneratedSignature($paymentSignature, $generatedSignature)
    {
        return ($paymentSignature == $generatedSignature);
    }

    public function isFraud($generatedAntiFraudHash, $antiFraudHash)
    {
        return !($generatedAntiFraudHash == $antiFraudHash);
    }

    public function generateAntiFraudHash($cartId, $paymentMethod, $cartDate)
    {
        return md5($cartId . $paymentMethod . $cartDate);
    }

    public function isAuthorized()
    {
        $isAuthorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'paylane') {
                $isAuthorized = true;
                break;
            }
        }

        return $isAuthorized;
    }

    public function generateMd5sig($paymentResponse)
    {
        return SHA1(
            Configuration::get('PAYLANE_GENERAL_HASH') . "|" .
                $paymentResponse['status'] . "|" .
                $paymentResponse['description'] . "|" .
                $paymentResponse['amount'] . "|" .
                $paymentResponse['currency'] . "|" .
                $paymentResponse['transaction_id']
        );
    }

    public function getOrderByTransactionId($transactionId)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."endora_paylane_order_ref WHERE transaction_id ='".pSQL($transactionId)."'";
        $order = Db::getInstance()->getRow($sql);

        return $order;
    }

    private function getFrontendPaymentLocale($paymentMethod)
    {
        switch ($paymentMethod) {
        case 'PAYLANE_FRONTEND_PM_SECUREFORM':
            if ($this->l('PAYLANE_FRONTEND_PM_SECUREFORM') == "PAYLANE_FRONTEND_PM_SECUREFORM") {
                $paymentLocale = "Paylane SecureForm";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_SECUREFORM');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_CREDITCARD':
            if ($this->l('PAYLANE_FRONTEND_PM_CREDITCARD') == "PAYLANE_FRONTEND_PM_CREDITCARD") {
                $paymentLocale = "Paylane CreditCard";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_CREDITCARD');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_BANKTRANSFER':
            if ($this->l('PAYLANE_FRONTEND_PM_BANKTRANSFER') == "PAYLANE_FRONTEND_PM_BANKTRANSFER") {
                $paymentLocale = "Paylane BankTransfer";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_BANKTRANSFER');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_PAYPAL':
            if ($this->l('PAYLANE_FRONTEND_PM_PAYPAL') == "PAYLANE_FRONTEND_PM_PAYPAL") {
                $paymentLocale = "Paylane PayPal";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_PAYPAL');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_DIRECTDEBIT':
            if ($this->l('PAYLANE_FRONTEND_PM_DIRECTDEBIT') == "PAYLANE_FRONTEND_PM_DIRECTDEBIT") {
                $paymentLocale = "Paylane DirectDebit";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_DIRECTDEBIT');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_SOFORT':
            if ($this->l('PAYLANE_FRONTEND_PM_SOFORT') == "PAYLANE_FRONTEND_PM_SOFORT") {
                $paymentLocale = "Paylane Sofort";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_SOFORT');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_IDEAL':
            if ($this->l('PAYLANE_FRONTEND_PM_IDEAL') == "PAYLANE_FRONTEND_PM_IDEAL") {
                $paymentLocale = "Paylane Ideal";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_IDEAL');
            }
            break;
        case 'PAYLANE_FRONTEND_PM_APPLEPAY':
            if ($this->l('PAYLANE_FRONTEND_PM_APPLEPAY') == "PAYLANE_FRONTEND_PM_APPLEPAY") {
                $paymentLocale = "Paylane ApplePay";
            } else {
                $paymentLocale = $this->l('PAYLANE_FRONTEND_PM_APPLEPAY');
            }
            break;
        default:
            $paymentLocale = "UNDEFINED";

            break;
        }

        return $paymentLocale;
    }

    private function getBackendPaymentLocale($paymentMethod)
    {
        switch ($paymentMethod) {
        case 'PAYLANE_BACKEND_PM_SECUREFORM':
            if ($this->l('PAYLANE_BACKEND_PM_SECUREFORM') == "PAYLANE_BACKEND_PM_SECUREFORM") {
                $paymentLocale = "Paylane SecureForm";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_SECUREFORM');
            }
            break;
        case 'PAYLANE_BACKEND_PM_CREDITCARD':
            if ($this->l('PAYLANE_BACKEND_PM_CREDITCARD') == "PAYLANE_BACKEND_PM_CREDITCARD") {
                $paymentLocale = "Paylane CreditCard";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_CREDITCARD');
            }
            break;
        case 'PAYLANE_BACKEND_PM_BANKTRANSFER':
            if ($this->l('PAYLANE_BACKEND_PM_BANKTRANSFER') == "PAYLANE_BACKEND_PM_BANKTRANSFER") {
                $paymentLocale = "Paylane BankTransfer";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_BANKTRANSFER');
            }
            break;
        case 'PAYLANE_BACKEND_PM_PAYPAL':
            if ($this->l('PAYLANE_BACKEND_PM_PAYPAL') == "PAYLANE_BACKEND_PM_PAYPAL") {
                $paymentLocale = "Paylane PayPal";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_PAYPAL');
            }
            break;
        case 'PAYLANE_BACKEND_PM_DIRECTDEBIT':
            if ($this->l('PAYLANE_BACKEND_PM_DIRECTDEBIT') == "PAYLANE_BACKEND_PM_DIRECTDEBIT") {
                $paymentLocale = "Paylane DirectDebit";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_DIRECTDEBIT');
            }
            break;
        case 'PAYLANE_BACKEND_PM_SOFORT':
            if ($this->l('PAYLANE_BACKEND_PM_SOFORT') == "PAYLANE_BACKEND_PM_SOFORT") {
                $paymentLocale = "Paylane Sofort";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_SOFORT');
            }
            break;
        case 'PAYLANE_BACKEND_PM_IDEAL':
            if ($this->l('PAYLANE_BACKEND_PM_IDEAL') == "PAYLANE_BACKEND_PM_IDEAL") {
                $paymentLocale = "Paylane Ideal";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_IDEAL');
            }
            break;
        case 'PAYLANE_BACKEND_PM_APPLEPAY':
            if ($this->l('PAYLANE_BACKEND_PM_APPLEPAY') == "PAYLANE_BACKEND_PM_APPLEPAY") {
                $paymentLocale = "Paylane ApplePay";
            } else {
                $paymentLocale = $this->l('PAYLANE_BACKEND_PM_APPLEPAY');
            }
            break;
        default:
            $paymentLocale = "UNDEFINED";

            break;
        }

        return $paymentLocale;
    }

    private function getTrnStatusLocale($status)
    {
        switch ($status) {
            case 'BACKEND_TT_PENDING':
                if ($this->l('BACKEND_TT_PENDING') == "BACKEND_TT_PENDING") {
                    $trnStatus = "Pending";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_PENDING');
                }
                break;
            case 'BACKEND_TT_PERFORMED':
                if ($this->l('BACKEND_TT_PERFORMED') == "BACKEND_TT_PERFORMED") {
                    $trnStatus = "Performed";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_PERFORMED');
                }
                break;
            case 'BACKEND_TT_CLEARED':
                if ($this->l('BACKEND_TT_CLEARED') == "BACKEND_TT_CLEARED") {
                    $trnStatus = "Cleared";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_CLEARED');
                }
                break;
            case 'BACKEND_TT_CANCELLED':
                if ($this->l('BACKEND_TT_CANCELLED') == "BACKEND_TT_CANCELLED") {
                    $trnStatus = "Cancelled";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_CANCELLED');
                }
                break;
            case 'BACKEND_TT_FAILED':
                if ($this->l('BACKEND_TT_FAILED') == "BACKEND_TT_FAILED") {
                    $trnStatus = "Failed";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_FAILED');
                }
                break;
            case 'BACKEND_TT_CHARGEBACK':
                if ($this->l('BACKEND_TT_CHARGEBACK') == "BACKEND_TT_CHARGEBACK") {
                    $trnStatus = "Chargeback";
                } else {
                    $trnStatus = $this->l('BACKEND_TT_CHARGEBACK');
                }
                break;
            default:
                if ($this->l('ERROR_GENERAL_ABANDONED_BYUSER') == "ERROR_GENERAL_ABANDONED_BYUSER") {
                    $trnStatus = "Abandoned by user";
                } else {
                    $trnStatus = $this->l('ERROR_GENERAL_ABANDONED_BYUSER');
                }
                break;
        }

        return $trnStatus;
    }

    public function getLocaleErrorMapping($errorIdentifier)
    {
        switch ($errorIdentifier) {
            case 'ERROR_GENERAL_NORESPONSE':
                if ($this->l('ERROR_GENERAL_NORESPONSE') == "ERROR_GENERAL_NORESPONSE") {
                    $returnMessage = "Unfortunately, the confirmation of your payment failed.
                    Please contact your merchant for clarification.";
                } else {
                    $returnMessage = $this->l('ERROR_GENERAL_NORESPONSE');
                }
                break;
            case 'ERROR_GENERAL_FRAUD_DETECTION':
                if ($this->l('ERROR_GENERAL_FRAUD_DETECTION') == "ERROR_GENERAL_FRAUD_DETECTION") {
                    $returnMessage = "Unfortunately, there was an error while processing your order.
                    In case a payment has been made, it will be automatically refunded.";
                } else {
                    $returnMessage = $this->l('ERROR_GENERAL_FRAUD_DETECTION');
                }
                break;
            case 'PAYLANE_ERROR_99_GENERAL':
                if ($this->l('PAYLANE_ERROR_99_GENERAL') == "PAYLANE_ERROR_99_GENERAL") {
                    $returnMessage = "Failure reason not specified";
                } else {
                    $returnMessage =  $this->l('PAYLANE_ERROR_99_GENERAL');
                }
                break;
            default:
                if ($this->l('ERROR_GENERAL_REDIRECT') == "ERROR_GENERAL_REDIRECT") {
                    $returnMessage = "Error before redirect";
                } else {
                    $returnMessage =  $this->l('ERROR_GENERAL_REDIRECT');
                }
                break;
        }

        return $returnMessage;
    }

    protected function getPresentationLocale()
    {
        $locale = array();
        if ($this->l('PAYLANE_BACKEND_PRES_ABOUTTITLE') == "PAYLANE_BACKEND_PRES_ABOUTTITLE") {
            $locale['about']['title'] = "With our offer you will sail out into wide waters!";
        } else {
            $locale['about']['title'] = $this->l('PAYLANE_BACKEND_PRES_ABOUTTITLE');
        }

        if ($this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT1') == "PAYLANE_BACKEND_PRES_ABOUTTEXT1") {
            $locale['about']['text1'] = "Our goal is to create solutions perfectly matched to your business model.";
        } else {
            $locale['about']['text1'] = $this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT1');
        }
        if ($this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT2') == "PAYLANE_BACKEND_PRES_ABOUTTEXT2") {
            $locale['about']['text2'] = "For years, we have been helping companies boldly navigate the ocean of possibilities created by modern solutions in online payments.";
        } else {
            $locale['about']['text2'] = $this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT2');
        }
        if ($this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT3') == "PAYLANE_BACKEND_PRES_ABOUTTEXT3") {
            $locale['about']['text3'] = "Check how many solutions can support your business.";
        } else {
            $locale['about']['text3'] = $this->l('PAYLANE_BACKEND_PRES_ABOUTTEXT3');
        }
        if ($this->l('PAYLANE_BACKEND_PRES_SIGNUP') == "PAYLANE_BACKEND_PRES_SIGNUP") {
            $locale['signup']['title'] = "sign up now";
        } else {
            $locale['signup']['title'] = $this->l('PAYLANE_BACKEND_PRES_SIGNUP');
        }

        return $locale;
    }

    public function getContent()
    {
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);
        $backOfficeJsUrl = $shopDomainSsl.__PS_BASE_URI__.'modules/'.$this->name.'/views/js/paylanebackoffice.js';
        $backOfficeCssUrl = $shopDomainSsl.__PS_BASE_URI__.'modules/'.$this->name.'/views/css/paylanebackoffice.css';

        $tplVars = array(
            'tabs' => $this->getConfigurationTabs(),
            'selectedTab' => $this->getSelectedTab(),
            'backOfficeJsUrl' => $backOfficeJsUrl,
            'backOfficeCssUrl' => $backOfficeCssUrl
        );

        if (isset($this->context->cookie->paylaneConfigMessage)) {
            $tplVars['message']['success'] = $this->context->cookie->paylaneMessageSuccess;
            $tplVars['message']['text'] = $this->context->cookie->paylaneConfigMessage;
            unset($this->context->cookie->paylaneConfigMessage);
        } else {
            $tplVars['message'] = false;
        }

        $this->context->smarty->assign($tplVars);

        return $this->display(__FILE__, 'views/templates/admin/tabs.tpl');
    }

    protected function getAdminModuleLink()
    {
        $adminLink = $this->context->link->getAdminLink('AdminModules', false);
        $module = '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $adminToken = Tools::getAdminTokenLite('AdminModules');

        return $adminLink.$module.'&token='.$adminToken;
    }

    protected function getConfigurationTabs()
    {
        $tabsLocale = $this->getTabsLocale();
        $tabs = array();

        $tabs[] = array(
            'id' => 'presentation',
                'title' => $tabsLocale['presentation'],
                'content' => $this->getPresentationTemplate()
        );

        $tabs[] = array(
            'id' => 'general_setting',
                'title' => $tabsLocale['paylaneSetting'],
                'content' => $this->getGeneralSettingTemplate()
        );

        $tabs[] = array(
            'id' => 'payment_configuration',
                'title' => $tabsLocale['paymentsConfig'],
                'content' => $this->getPaymentConfigurationTemplate()
        );

        return $tabs;
    }

    protected function getSelectedTab()
    {
        if ($this->selectedTab) {
            return $this->selectedTab;
        }

        if (Tools::getValue('selected_tab')) {
            return Tools::getValue('selected_tab');
        }

        return 'presentation';
    }

    protected function getSignUpUrl()
    {
        return $this->paylaneSignUpUrl;
    }

    protected function getPresentationTemplate()
    {
        $tplVars = array(
            'presentation' => $this->getPresentationLocale(),
            'signUpUrl' => $this->getSignUpUrl(),
            'thisPath' => $this->_path
        );
        $this->context->smarty->assign($tplVars);
        return $this->display(__FILE__, 'views/templates/admin/presentation.tpl');
    }

    protected function getGeneralSettingTemplate()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->validateGeneralSetting();
            $this->selectedTab = 'general_setting';
        }

        $this->html .= $this->renderGeneralSettingForm();

        return $this->html;
    }

    protected function getPaymentConfigurationTemplate()
    {
        if (Tools::isSubmit('btnSubmitPaymentConfig')) {
            $this->selectedTab = 'payment_configuration';
            $this->updatePaymentConfig();
        }

        $locale = $this->getPaymentConfigurationLocale();
        $i = 0;
        $payments = array();
        $paymentMethods = PaylanePaymentCore::getPaymentMethods();
        foreach (array_keys($paymentMethods) as $paymentType) {
            $paymentTypeLowerCase = Tools::strtolower($paymentType);
            $activeConfigName = Configuration::get('PAYLANE_'.$paymentType.'_ACTIVE');

            $payments[$i]['title'] = $locale[$paymentTypeLowerCase]['title'];
            $payments[$i]['type'] = $paymentTypeLowerCase;
            $payments[$i]['active'] = Tools::getValue('PAYLANE_'.$paymentType.'_ACTIVE', $activeConfigName);
            $payments[$i]['brand'] = $paymentType;
            if (isset($locale[$paymentTypeLowerCase]['tooltips'])) {
                $payments[$i]['tooltips'] = $locale[$paymentTypeLowerCase]['tooltips'];
            } else {
                $payments[$i]['tooltips'] = "";
            }
            $i++;
        }

        $tplVars = array(
            'panelTitle' => $locale['paymentsConfig'],
            'payments' => $payments,
            'thisPath' => Tools::getShopDomain(true, true).__PS_BASE_URI__.'modules/paylane/',
            'fieldsValue' => $this->getPaymentConfiguration(),
            'currentIndex' => $this->getAdminModuleLink(),
            'label' => $locale['label'],
            'button' => $locale['button']
        );
        $this->context->smarty->assign($tplVars);

        return $this->display(__FILE__, 'views/templates/admin/paymentConfiguration.tpl');
    }

    protected function renderGeneralSettingForm()
    {
        $locale = $this->getGeneralSettingLocale();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        if (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')) {
            $helper->allow_employee_form_lang =  Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        } else {
            $helper->allow_employee_form_lang =  0;
        }
        $this->fields_form = array();
        $this->fields_form = $this->getGeneralSettingForm($locale);
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->getAdminModuleLink();
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getGeneralSetting(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($this->fields_form);
    }

    protected function getGeneralSettingForm($locale)
    {
        $generalForm = array();
        $generalForm[0] = array(
            'form' => array(
                'legend' => array('title' => $this->l('Basic')),
                'input' => array(
                    $this->getTextForm('GENERAL_MERCHANTID', $locale['mid'], true),
                    $this->getTextForm('GENERAL_HASH', $locale['hash'], true),
                    $this->getTextForm('GENERAL_LOGIN_API', $locale['loginApi'], true),
                    $this->getTextForm('GENERAL_PUBLIC_KEY_API', $locale['publicKeyApi'], true),
                    $this->getTextForm('GENERAL_PASSWORD_API', $locale['passwordApi'], true)
                ),
                'submit' => array(
                    'title' => $locale['save']
                )
            ),
        );
        $generalForm[1] = array(
            'form' => array(
                'legend' => array('title' => $this->l('Notification')),
                'input' => array(
                    $this->getTextForm('NOTIFICATION_URL', $locale['notificationUrl'], false, true),
                    $this->getTextForm('NOTIFICATION_USER', $locale['notificationUser'], false),
                    $this->getTextForm('NOTIFICATION_PASSWORD', $locale['notificationPassword'], false),
                    $this->getTextForm('NOTIFICATION_TOKEN', $locale['notificationToken'], false),
                ),
                'submit' => array(
                    'title' => $locale['save']
                )
            ),
        );

        $options = array(
            array(
                'value' => 1,
                'label' => $this->l('Yes')
            ),
            array(
                'value' => 0,
                'label' => $this->l('No')
            )
        );

        $i=2;
        foreach ($this->formBuilder as $formGroupName => $formFields) {
            $generalForm[$i] = array();

            $fieldsList = array();
            foreach ($formFields as $formName => $formOptions) {
                $formElem = array(
                    'type' => $formOptions['type'],
                    'label' => $this->l($formOptions['label']),
                    'name' => $formName,
                    'required' => isset($formOptions['required']) ? $formOptions['required'] : true
                );

                if ($formOptions['type'] == 'select') {
                    $formElem['options'] = array(
                        'query' => isset($formOptions['options']) ? $formOptions['options'] : $options,
                        'id' => 'value',
                        'name' => 'label'
                    );
                }

                if ($formOptions['type'] == 'text') {
                    $formElem['size'] = 255;
                }

                $fieldsList[] = $formElem;
            }

            $generalForm[$i]['form'] = array(
                'legend' => array(
                    'title' => $this->l($formGroupName),
                ),
                'input' => $fieldsList,
                'submit' => array(
                    'title' => $locale['save']
                )
            );

            $i++;
        }

        return $generalForm;
    }

    protected function getGeneralSetting()
    {
        $configMerchantID = Configuration::get('PAYLANE_GENERAL_MERCHANTID');
        $configHash = Configuration::get('PAYLANE_GENERAL_HASH');

        $configLoginApi = Configuration::get('PAYLANE_GENERAL_LOGIN_API');
        $configPublicKeyApi = Configuration::get('PAYLANE_GENERAL_PUBLIC_KEY_API');
        $configPasswordApi = Configuration::get('PAYLANE_GENERAL_PASSWORD_API');

        $configNotificationUrl = Configuration::get('PAYLANE_NOTIFICATION_URL');
        $configNotificationUser = Configuration::get('PAYLANE_NOTIFICATION_USER');
        $configNotificationPassword = Configuration::get('PAYLANE_NOTIFICATION_PASSWORD');
        $configNotificationToken = Configuration::get('PAYLANE_NOTIFICATION_TOKEN');

        $generalSetting = array();
        $generalSetting['PAYLANE_GENERAL_MERCHANTID'] = Tools::getValue('PAYLANE_GENERAL_MERCHANTID', $configMerchantID);
        $generalSetting['PAYLANE_GENERAL_HASH'] = Tools::getValue('PAYLANE_GENERAL_HASH', $configHash);
        $generalSetting['PAYLANE_GENERAL_LOGIN_API'] = Tools::getValue('PAYLANE_GENERAL_LOGIN_API', $configLoginApi);
        $generalSetting['PAYLANE_GENERAL_PUBLIC_KEY_API'] = Tools::getValue('PAYLANE_GENERAL_PUBLIC_KEY_API', $configPublicKeyApi);
        $generalSetting['PAYLANE_GENERAL_PASSWORD_API'] = Tools::getValue('PAYLANE_GENERAL_PASSWORD_API', $configPasswordApi);

        $generalSetting['PAYLANE_NOTIFICATION_URL'] = Tools::getValue('PAYLANE_NOTIFICATION_URL', $configNotificationUrl);
        $generalSetting['PAYLANE_NOTIFICATION_USER'] = Tools::getValue('PAYLANE_NOTIFICATION_USER', $configNotificationUser);
        $generalSetting['PAYLANE_NOTIFICATION_PASSWORD'] = Tools::getValue('PAYLANE_NOTIFICATION_PASSWORD', $configNotificationPassword);
        $generalSetting['PAYLANE_NOTIFICATION_TOKEN'] = Tools::getValue('PAYLANE_NOTIFICATION_TOKEN', $configNotificationToken);

        foreach ($this->formBuilder as $formGroup) {
            foreach ($formGroup as $name => $options) {
                $generalSetting[$name] = Tools::getValue($name, Configuration::get($name));
            }
        }

        return $generalSetting;
    }

    protected function validateGeneralSetting()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $locale = $this->getGeneralSettingLocale();
            $isRequired = false;
            $fieldsRequired = array();

            if (trim(Tools::getValue('PAYLANE_GENERAL_MERCHANTID')) == '') {
                $fieldsRequired[] = $locale['mid']['label'];
                $isRequired = true;
            }
            if (trim(Tools::getValue('PAYLANE_GENERAL_HASH')) == '') {
                $fieldsRequired[] = $locale['hash']['label'];
                $isRequired = true;
            }

            if (trim(Tools::getValue('PAYLANE_GENERAL_LOGIN_API')) == '') {
                $fieldsRequired[] = $locale['loginApi']['label'];
                $isRequired = true;
            }

            if (trim(Tools::getValue('PAYLANE_GENERAL_PUBLIC_KEY_API')) == '') {
                $fieldsRequired[] = $locale['publicKeyApi']['label'];
                $isRequired = true;
            }

            if (trim(Tools::getValue('PAYLANE_GENERAL_PASSWORD_API')) == '') {
                $fieldsRequired[] = $locale['passwordApi']['label'];
                $isRequired = true;
            }

            if ($isRequired) {
                $warning = implode(', ', $fieldsRequired) . ' ';
                if ($this->l('ERROR_MANDATORY') == "ERROR_MANDATORY") {
                    $warning .= "is required. please fill out this field";
                } else {
                    $warning .= $this->l('ERROR_MANDATORY');
                }
                $this->context->cookie->paylaneMessageSuccess = false;
                $this->context->cookie->paylaneConfigMessage = $warning;
            } else {
                $this->updateGeneralSetting();
            }
        }
    }

    protected function updateGeneralSetting()
    {
        if (Tools::isSubmit('btnSubmit')) {

            Configuration::updateValue('PAYLANE_GENERAL_MERCHANTID', Tools::getValue('PAYLANE_GENERAL_MERCHANTID'));
            Configuration::updateValue('PAYLANE_GENERAL_HASH', Tools::getValue('PAYLANE_GENERAL_HASH'));
            Configuration::updateValue('PAYLANE_GENERAL_LOGIN_API', Tools::getValue('PAYLANE_GENERAL_LOGIN_API'));
            Configuration::updateValue('PAYLANE_GENERAL_PUBLIC_KEY_API', Tools::getValue('PAYLANE_GENERAL_PUBLIC_KEY_API'));
            Configuration::updateValue('PAYLANE_GENERAL_PASSWORD_API', Tools::getValue('PAYLANE_GENERAL_PASSWORD_API'));

            Configuration::updateValue('PAYLANE_NOTIFICATION_USER', Tools::getValue('PAYLANE_NOTIFICATION_USER'));
            Configuration::updateValue('PAYLANE_NOTIFICATION_PASSWORD', Tools::getValue('PAYLANE_NOTIFICATION_PASSWORD'));
            Configuration::updateValue('PAYLANE_NOTIFICATION_TOKEN', Tools::getValue('PAYLANE_NOTIFICATION_TOKEN'));

            foreach ($this->formBuilder as $formGroup) {
                foreach ($formGroup as $name => $options) {
                    Configuration::updateValue($name, Tools::getValue($name));
                }
            }

            if ($this->l('PAYLANE_SUCCESS_GENERAL_SETTING') == "PAYLANE_SUCCESS_GENERAL_SETTING") {
                $successMessage = "Your paylane setting were successfully updated.";
            } else {
                $successMessage = $this->l('PAYLANE_SUCCESS_GENERAL_SETTING');
            }

            $this->context->cookie->paylaneMessageSuccess = true;
            $this->context->cookie->paylaneConfigMessage = $successMessage;
        }
    }

    protected function getPaymentConfiguration()
    {
        $saveConfig = array();
        foreach (array_keys(PaylanePaymentCore::getPaymentMethods()) as $paymentType) {
            $getActive = Configuration::get('PAYLANE_'.$paymentType.'_ACTIVE');
            $saveConfig['PAYLANE_'.$paymentType.'_ACTIVE'] =
                Tools::getValue('PAYLANE_'.$paymentType.'_ACTIVE', $getActive);
        }

        return $saveConfig;
    }

    protected function updatePaymentConfig()
    {
        if (Tools::isSubmit('btnSubmitPaymentConfig')) {
            foreach (array_keys(PaylanePaymentCore::getPaymentMethods()) as $paymentType) {
                $active = Tools::getValue('PAYLANE_'.$paymentType.'_ACTIVE');
                Configuration::updateValue('PAYLANE_'.$paymentType.'_ACTIVE', $active);
            }

            if ($this->l('SUCCESS_GENERAL_PAYMENTCONFIG') == "SUCCESS_GENERAL_PAYMENTCONFIG") {
                $successMessage = "Congratulations, your payments configuration were successfully updated.";
            } else {
                $successMessage = $this->l('SUCCESS_GENERAL_PAYMENTCONFIG');
            }

            $this->context->cookie->paylaneMessageSuccess = true;
            $this->context->cookie->paylaneConfigMessage = $successMessage;
        }
    }

    protected function getTabsLocale()
    {
        $locale = array();
        if ($this->l('BACKEND_GENERAL_PRESENTATION') == "BACKEND_GENERAL_PRESENTATION") {
            $locale['presentation'] = "Presentation";
        } else {
            $locale['presentation'] = $this->l('BACKEND_GENERAL_PRESENTATION');
        }
        if ($this->l('BACKEND_CH_GENERAL') == "BACKEND_CH_GENERAL") {
            $locale['generalSetting'] = "General Setting";
        } else {
            $locale['generalSetting'] = $this->l('BACKEND_CH_GENERAL');
        }
        if ($this->l('BACKEND_GENERAL_PAYMENT_CONFIG') == "BACKEND_GENERAL_PAYMENT_CONFIG") {
            $locale['paymentsConfig'] = "Payment Configuration";
        } else {
            $locale['paymentsConfig'] = $this->l('BACKEND_GENERAL_PAYMENT_CONFIG');
        }
        if ($this->l('PAYLANE_BACKEND_PM_SETTINGS') == "PAYLANE_BACKEND_PM_SETTINGS") {
            $locale['paylaneSetting'] = "Paylane Settings";
        } else {
            $locale['paylaneSetting'] = $this->l('PAYLANE_BACKEND_PM_SETTINGS');
        }

        return $locale;
    }

    protected function getGeneralSettingLocale()
    {
        $locale = array();
        if ($this->l('PAYLANE_BACKEND_PM_SETTINGS') == "PAYLANE_BACKEND_PM_SETTINGS") {
            $locale['setting']['label'] = "Paylane Settings";
        } else {
            $locale['setting']['label'] = $this->l('PAYLANE_BACKEND_PM_SETTINGS');
        }
        if ($this->l('PAYLANE_BACKEND_MID') == "PAYLANE_BACKEND_MID") {
            $locale['mid']['label'] = "Merchant ID";
        } else {
            $locale['mid']['label'] = $this->l('PAYLANE_BACKEND_MID');
        }
        if ($this->l('PAYLANE_BACKEND_HASH') == "PAYLANE_BACKEND_HASH") {
            $locale['hash']['label'] = "Hash salt";
        } else {
            $locale['hash']['label'] = $this->l('PAYLANE_BACKEND_HASH');
        }

        if ($this->l('PAYLANE_BACKEND_LOGIN_API') == "PAYLANE_BACKEND_LOGIN_API") {
            $locale['loginApi']['label'] = "Login API";
        } else {
            $locale['loginApi']['label'] = $this->l('PAYLANE_BACKEND_LOGIN_API');
        }

        if ($this->l('PAYLANE_BACKEND_PUBLIC_KEY_API') == "PAYLANE_BACKEND_PUBLIC_KEY_API") {
            $locale['publicKeyApi']['label'] = "Public Key Api ";
        } else {
            $locale['publicKeyApi']['label'] = $this->l('PAYLANE_BACKEND_PUBLIC_KEY_API');
        }

        if ($this->l('PAYLANE_BACKEND_PASSWORD_API') == "PAYLANE_BACKEND_PASSWORD_API") {
            $locale['passwordApi']['label'] = "Password API";
        } else {
            $locale['passwordApi']['label'] = $this->l('PAYLANE_BACKEND_PASSWORD_API');
        }

        if ($this->l('PAYLANE_BACKEND_NOTIFICATION_URL') == "PAYLANE_BACKEND_NOTIFICATION_URL") {
            $locale['notificationUrl']['label'] = "Notification Url";
        } else {
            $locale['notificationUrl']['label'] = $this->l('PAYLANE_BACKEND_NOTIFICATION_URL');
        }
        if ($this->l('PAYLANE_BACKEND_NOTIFICATION_USER') == "PAYLANE_BACKEND_NOTIFICATION_USER") {
            $locale['notificationUser']['label'] = "Notification User";
        } else {
            $locale['notificationUser']['label'] = $this->l('PAYLANE_BACKEND_NOTIFICATION_USER');
        }
        if ($this->l('PAYLANE_BACKEND_NOTIFICATION_PASSWORD') == "PAYLANE_BACKEND_NOTIFICATION_PASSWORD") {
            $locale['notificationPassword']['label'] = "Notification Password";
        } else {
            $locale['notificationPassword']['label'] = $this->l('PAYLANE_BACKEND_NOTIFICATION_PASSWORD');
        }
        if ($this->l('PAYLANE_BACKEND_NOTIFICATION_TOKEN') == "PAYLANE_BACKEND_NOTIFICATION_TOKEN") {
            $locale['notificationToken']['label'] = "Notification Token";
        } else {
            $locale['notificationToken']['label'] = $this->l('PAYLANE_BACKEND_NOTIFICATION_TOKEN');
        }

        if ($this->l('PAYLANE_BACKEND_TT_MID') == "PAYLANE_BACKEND_TT_MID") {
            $locale['mid']['desc'] = "Your Paylane customer ID";
        } else {
            $locale['mid']['desc'] = $this->l('PAYLANE_BACKEND_TT_MID');
        }
        if ($this->l('PAYLANE_BACKEND_TT_HASH') == "PAYLANE_BACKEND_TT_HASH") {
            $locale['hash']['desc'] = "Your Paylane hash salt";
        } else {
            $locale['hash']['desc'] = $this->l('PAYLANE_BACKEND_TT_HASH');
        }
        if ($this->l('PAYLANE_BACKEND_TT_LOGIN_API') == "PAYLANE_BACKEND_TT_LOGIN_API") {
            $locale['loginApi']['desc'] = "Your Paylane login API";
        } else {
            $locale['loginapi']['desc'] = $this->l('PAYLANE_BACKEND_TT_LOGIN_API');
        }
        if ($this->l('PAYLANE_BACKEND_TT_PUBLIC_KEY_API') == "PAYLANE_BACKEND_TT_PUBLIC_KEY_API") {
            $locale['publicKeyApi']['desc'] = "Your Paylane public key API";
        } else {
            $locale['publicKeyApi']['desc'] = $this->l('PAYLANE_BACKEND_TT_PUBLIC_KEY_API');
        }
        if ($this->l('PAYLANE_BACKEND_TT_PASSWORD_API') == "PAYLANE_BACKEND_TT_PASSWORD_API") {
            $locale['passwordApi']['desc'] = "Your Paylane password API";
        } else {
            $locale['passwordApi']['desc'] = $this->l('PAYLANE_BACKEND_TT_PASSWORD_API');
        }
        if ($this->l('PAYLANE_BACKEND_TT_NOTIFICATION_URL') == "PAYLANE_BACKEND_TT_NOTIFICATION_URL") {
            $locale['notificationUrl']['desc'] = "Notification Url";
        } else {
            $locale['notificationUrl']['desc'] = $this->l('PAYLANE_BACKEND_TT_NOTIFICATION_URL');
        }
        if ($this->l('PAYLANE_BACKEND_TT_NOTIFICATION_USER') == "PAYLANE_BACKEND_TT_NOTIFICATION_USER") {
            $locale['notificationUser']['desc'] = "Notification User";
        } else {
            $locale['notificationUser']['desc'] = $this->l('PAYLANE_BACKEND_TT_NOTIFICATION_USER');
        }
        if ($this->l('PAYLANE_BACKEND_TT_NOTIFICATION_PASSWORD') == "PAYLANE_BACKEND_TT_NOTIFICATION_PASSWORD") {
            $locale['notificationPassword']['desc'] = "Notification Password";
        } else {
            $locale['notificationPassword']['desc'] = $this->l('PAYLANE_BACKEND_TT_NOTIFICATION_PASSWORD');
        }
        if ($this->l('PAYLANE_BACKEND_TT_NOTIFICATION_TOKEN') == "PAYLANE_BACKEND_TT_NOTIFICATION_TOKEN") {
            $locale['notificationToken']['desc'] = "Static string sent only if configured for merchant account";
        } else {
            $locale['notificationToken']['desc'] = $this->l('PAYLANE_BACKEND_TT_NOTIFICATION_TOKEN');
        }

        $locale['save'] = $this->l('BACKEND_CH_SAVE') == "BACKEND_CH_SAVE" ? "Save" : $this->l('BACKEND_CH_SAVE');

        return $locale;
    }

    protected function getPaymentConfigurationLocale()
    {
        $locale = array();

        foreach (array_keys(PaylanePaymentCore::getPaymentMethods()) as $paymentType) {
            $paymentTypeLower = Tools::strtolower($paymentType);
            $locale[$paymentTypeLower]['title'] = $this->getBackendPaymentLocale('PAYLANE_BACKEND_PM_'.$paymentType);
        }

        if ($this->l('BACKEND_CH_ACTIVE') == "BACKEND_CH_ACTIVE") {
            $locale['label']['active'] = "Enabled";
        } else {
            $locale['label']['active'] = $this->l('BACKEND_CH_ACTIVE');
        }
        if ($this->l('BACKEND_GENERAL_PAYMENT_CONFIG') == "BACKEND_GENERAL_PAYMENT_CONFIG") {
            $locale['paymentsConfig'] = "Payment Configuration";
        } else {
            $locale['paymentsConfig'] = $this->l('BACKEND_GENERAL_PAYMENT_CONFIG');
        }

        if ($this->l('PAYLANE_BACKEND_TT_ALL_COUNTRIES') == "PAYLANE_BACKEND_TT_ALL_COUNTRIES") {
            $locale['secureform']['tooltips'] = "All Countries";
        } else {
            $locale['secureform']['tooltips'] = $this->l('PAYLANE_BACKEND_TT_ALL_COUNTRIES');
        }

        $locale['button']['save'] =
            $this->l('BACKEND_CH_SAVE') == "BACKEND_CH_SAVE" ? "Save" : $this->l('BACKEND_CH_SAVE');
        $locale['button']['yes'] = $this->l('BACKEND_BT_YES') == "BACKEND_BT_YES" ? "Yes" : $this->l('BACKEND_BT_YES');
        $locale['button']['no'] = $this->l('BACKEND_BT_NO') == "BACKEND_BT_NO" ? "No" : $this->l('BACKEND_BT_NO');

        return $locale;
    }

    private function getTextForm($pm, $locale, $requirement = false, $readonly = false)
    {
        $textForm =
                  array(
                      'type' => 'text',
                      'label' => @$locale['label'],
                      'name' => 'PAYLANE_'.$pm,
                      'required' => $requirement,
                      'readonly' => $readonly,
                      'desc' => @$locale['desc']
                  );

        return $textForm;
    }

    private function getPasswordForm($pm, $locale, $requirement = false)
    {
        $passwordForm =
                      array(
                          'type' => 'password',
                          'label' => $locale['label'],
                          'name' => 'PAYLANE_'.$pm,
                          'required' => $requirement,
                          'desc' => $locale['desc']
                      );

        return $passwordForm;
    }

    private function getSelectForm($pm, $locale, $selectList)
    {
        $selectForm = array(
            'type'      => 'select',
            'label'     => @$locale['label'],
            'name'      => 'PAYLANE_'.$pm,
            'desc'      => @$locale['desc'],
            'options'   => array(
                'query' => $selectList,
                'id' => 'id',
                'name'   => 'name'
            )
        );
        return $selectForm;
    }

}
