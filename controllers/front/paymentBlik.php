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
require_once(dirname(__FILE__).'/paymentAbstract.php');
require_once(_PS_MODULE_DIR_ . 'paylane/class/BLIK.php');
require_once(_PS_MODULE_DIR_ . 'paylane/paylane.php');

class PaylanePaymentBlikModuleFrontController extends PaylanePaymentAbstractModuleFrontController
{
    protected $paymentMethod = 'BLIK';

    public function getTemplateVars()
    {
        $paylane = new Paylane();
        $payment = new Blik($paylane);
        return $payment->getTemplateVars();
    }

}
