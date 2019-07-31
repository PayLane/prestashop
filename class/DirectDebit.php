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

class DirectDebit extends PaymentMethodAbstract
{
    protected $paymentType = 'directdebit';

    /*
    public function getPaymentOption()
    {
        $active = (boolean)Configuration::get('PAYLANE_DIRECTDEBIT_ACTIVE');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_directdebit_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_directdebit_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/sepa.png');
            }
        }

        return $paymentOption;
    }
    */

    public function getPaymentConfig()
    {
        return array(
            'paylane_directdebit_label' => array(
                'type' => 'text',
                'label' => 'Label',
                'default' => 'Direct Debit (SEPA)'
            ),
            'paylane_directdebit_showImg' => array(
                'type' => 'select',
                'label' => 'Show payment method image',
                'default' => 1
            ),
            'paylane_directdebit_mandate_id' => array(
                'type' => 'text',
                'label' => 'Mandate ID'
            )
        );
    }

    public function handlePayment($paymentParams)
    {
        $data = array();
        $data['sale'] = $this->prepareSaleData();
        $data['customer'] = $this->prepareCustomerData();
        $data['account'] = array(
            'account_holder' => $paymentParams['account_holder'],
            'account_country' => $paymentParams['account_country'],
            'iban' => $paymentParams['iban'],
            'bic' => $paymentParams['bic'],
            'mandate_id' => Configuration::get('paylane_directdebit_mandate_id')
        );

        $apiResult = $this->client->directDebitSale($data);

        return $apiResult;
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/direct_debit.tpl');
    }

    public function getTemplateVars()
    {
        $context = Context::getContext();
        $countries = Country::getCountries((int)Configuration::get('PS_LANG_DEFAULT'));
        $result = array();

        foreach ($countries as $country) {
            $result[$country['iso_code']] = $country['name'];
        }

        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'countries' => $result,
            'paymentMethodLabel' => Configuration::get('paylane_directdebit_label'),
            'withImage' => (bool)Configuration::get('paylane_directdebit_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'paymentMethodLabel' => Configuration::get('paylane_directdebit_label'),
            'withImage' => (bool)Configuration::get('paylane_directdebit_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/direct_debit.tpl');
    }
}
