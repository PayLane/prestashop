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
//TODO

require_once(_PS_MODULE_DIR_ . 'paylane/class/PaymentMethodAbstract.php');

class Blik extends PaymentMethodAbstract
{
    protected $paymentType = 'blik';

    public function getPaymentConfig()
    {
        return array(
            'paylane_blik_label' => array(
                'type' => 'text',
                'label' => 'Label',
                'default' => 'Blik'
            ),
            'paylane_blik_showImg' => array(
                'type' => 'select',
                'label' => 'Show payment method image',
                'default' => 1
            ),
        );
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/blik.tpl');
    }

    public function getTemplateVars()
    {
        $context = Context::getContext();
        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'paymentTypes' => $this->getBlikPaymentType(),
            'paymentMethodLabel' => Configuration::get('paylane_blik_label'),
            'withImage' => (bool)Configuration::get('paylane_blik_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'blikLabel' => Configuration::get('paylane_blik_label'),
            'withImage' => (bool)Configuration::get('paylane_blik_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/blik.tpl');
    }

    public function getBlikPaymentType()
    {
        $result = array(
            'BL' => array(
                'label' => 'Blik'
            ),
        );

        return $result;
    }
}
