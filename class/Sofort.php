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

class Sofort extends PaymentMethodAbstract
{
    protected $paymentType = 'sofort';
    private $paylane;

    public function __construct(Module $paylane) {
        $this->paylane = $paylane;
    }
    /*
    public function getPaymentOption()
    {
        $active = (boolean)Configuration::get('PAYLANE_SOFORT_ACTIVE');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_sofort_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_sofort_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/sofort.png');
            }
        }

        return $paymentOption;
    }
    */

    public function handlePayment($paymentParams)
    {
        $context = Context::getContext();
        $context->cookie->payment_type = $this->paymentType;
        $result = array();

        $data = array();
        $data['sale'] = $this->prepareSaleData();
        $data['customer'] = $this->prepareCustomerData();
        $data['payment_type'] = $paymentParams['type'];
        $data['back_url'] = $paymentParams['back_url'];
        if ($this->isOldPresta()) {
            $data['back_url'] = $context->link->getModuleLink('paylane', 'general', array(), true);
        }

        $apiResult = $this->client->sofortSale($data);

        if (!empty($apiResult['success']) && $apiResult['success']) {
            Tools::redirect($apiResult['redirect_url']);
            die;
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

    public function getPaymentConfig()
    {
        return array(
            'paylane_sofort_label' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_SOFORT_LABEL', 'sofort'),
                'default' => $this->paylane->l('PAYLANE_SOFORT_DEFAULT', 'sofort'),
            ),
            'paylane_sofort_showImg' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_SOFORT_SHOW_PAYMENT_METHOD_IMAGE', 'sofort'),
                'default' => 1
            ),
        );
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/sofort.tpl');
    }

    public function getTemplateVars()
    {
        $context = Context::getContext();
        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'paymentMethodLabel' => Configuration::get('paylane_sofort_label'),
            'withImage' => (bool)Configuration::get('paylane_sofort_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'paymentMethodLabel' => Configuration::get('paylane_sofort_label'),
            'withImage' => (bool)Configuration::get('paylane_sofort_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/sofort.tpl');
    }
}
