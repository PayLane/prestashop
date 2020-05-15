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

class BankTransfer extends PaymentMethodAbstract
{
    protected $paymentType = 'banktransfer';
    private $paylane;

    public function __construct(Module $paylane) {
         $this->paylane = $paylane;
         parent::__construct();
    }
    
    /*
    public function getPaymentOption()
    {
        $active = (boolean)Configuration::get('PAYLANE_BANKTRANSFER_ACTIVE');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_banktransfer_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_banktransfer_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/banktransfer.png');
            }
        }

        return $paymentOption;
    }
    */

    public function getPaymentConfig()
    {
        return array(
            'paylane_banktransfer_label' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_BANK_TRANSFER_LABEL', 'banktransfer'),
                'default' => $this->paylane->l('PAYLANE_BANK_TRANSFER_DEFAULT', 'banktransfer'),
            ),
            'paylane_banktransfer_showImg' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_BANK_TRANSFER_SHOW_PAYMENT_METHOD_IMAGE', 'banktransfer'),
                'default' => 1
            )
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
        $data['payment_type'] = $paymentParams['payment_type'];
        $data['back_url'] = $paymentParams['back_url'];
        if ($this->isOldPresta()) {
            $data['back_url'] = $context->link->getModuleLink('paylane', 'general', array(), true);
        }
        $apiResult = $this->client->bankTransferSale($data);

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

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/bank_transfer.tpl');
    }

    public function getTemplateVars()
    {
        $context = Context::getContext();
        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'paymentTypes' => $this->getBankTransferPaymentTypes(),
            'paymentMethodLabel' => Configuration::get('paylane_banktransfer_label'),
            'withImage' => (bool)Configuration::get('paylane_banktransfer_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'paymentMethodLabel' => Configuration::get('paylane_banktransfer_label'),
            'withImage' => (bool)Configuration::get('paylane_banktransfer_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/bank_transfer.tpl');
    }

    protected function getBankTransferPaymentTypes()
    {
        $result = array(
            'AB' => array(
                'label' => 'Alior Bank'
            ),
            'AS' => array(
                'label' => 'T-Mobile Usługi Bankowe'
            ),
            'MT' => array(
                'label' => 'mTransfer'
            ),
            'IN' => array(
                'label' => 'Inteligo'
            ),
            'IP' => array(
                'label' => 'iPKO'
            ),
            'MI' => array(
                'label' => 'Millenium'
            ),
            'CA' => array(
                'label' => 'Credit Agricole'
            ),
            'PP' => array(
                'label' => 'Poczta Polska'
            ),
            'PCZ' => array(
                'label' => 'Bank Pocztowy'
            ),
            'IB' => array(
                'label' => 'Idea Bank'
            ),
            'PO' => array(
                'label' => 'Pekao S.A.'
            ),
            'GB' => array(
                'label' => 'Getin Bank'
            ),
            'IG' => array(
                'label' => 'ING Bank Śląski'
            ),
            'WB' => array(
                'label' => 'Santander Bank'
            ),
            'PB' => array(
                'label' => 'Bank BGŻ BNP PARIBAS'
            ),
            'CT' => array(
                'label' => 'Citi'
            ),
            'PL' => array(
                'label' => 'Plus Bank'
            ),
            'NP' => array(
                'label' => 'Noble Pay'
            ),
            'BS' => array(
                'label' => 'Bank Spółdzielczy'
            ),
            'NB' => array(
                'label' => 'NestBank'
            ),
            'PBS' => array(
                'label' => 'Podkarpacki Bank Spółdzielczy'
            ),
            'SGB' => array(
                'label' => 'Spółdzielcza Grupa Bankowa'
            ),
            'BP' => array(
                'label' => 'Bank BPH'
            ),
            'BLIK' => array(
                'label' => 'BLIK'
            ),
            'OH' => array(
                'label' => 'Other bank'
            ),
        );
        
        return $result;
    }
}
