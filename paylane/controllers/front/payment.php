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
class PaylanePaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function __construct()
    {
        parent::__construct();
        $this->display_column_left = false;
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        if (method_exists('Tools', 'getAllValues')) {
            $params = Tools::getAllValues();
        } else {
            $params = $_POST + $_GET;
        }

        if (!isset($params) || !isset($params['payment_type'])) {
            Tools::redirect('index.php?controller=order');
        } else {
            require_once(_PS_MODULE_DIR_ . 'paylane/class/' . $params['payment_type'] . '.php');
            $handler = new $params['payment_type']();

            $templateVars = $handler->getTemplateVars();

            $pathSsl = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/';

            $this->context->smarty->assign(array_merge(array(
                'nbProducts' => $cart->nbProducts(),
                'this_path' => $this->module->getPathUri(),
                'this_path_bw' => $this->module->getPathUri(),
                'this_path_ssl' => $pathSsl
            ), $templateVars));

            if ($params['payment_type'] === 'PaylanePayPal') {
                $params['payment_type'] = 'Paypal';
            }

            $this->setTemplate('payment_form/' . $this->toSnakeCase($params['payment_type']) . '16.tpl');
        }
    }

    protected function toSnakeCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == Tools::strtoupper($match) ? Tools::strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}
