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
require_once(_PS_MODULE_DIR_ . 'paylane/class/PayLaneCards.php');

class CreditCard extends PaymentMethodAbstract
{
    protected $paymentType = 'creditcard';
    
    private $paylane;

    public function __construct(Module $paylane) {
         $this->paylane = $paylane;
         parent::__construct();
    }

    /*
    public function getPaymentOption()
    {
        $active = (boolean)Configuration::get('PAYLANE_CREDITCARD_ACTIVE');
        $paymentOption = null;

        if ($active) {
            $label = Configuration::get('paylane_creditcard_label');

            $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption->setCallToActionText($label)
                ->setForm($this->generatePaymentForm());

            if ((bool)Configuration::get('paylane_creditcard_showImg')) {
                $paymentOption->setLogo(_MODULE_DIR_ . 'paylane/views/img/payment_methods/creditcard.png');
            }
        }

        return $paymentOption;
    }
    */

    protected function prepareSale($token) {
        $tokenExplode = explode('|', $token);
        $idSale = $tokenExplode[0];
        $card = $tokenExplode[1];

        return array('id_sale' => $idSale, 'card' => $card);
    }

    protected function isValidate($token) {
        $context = Context::getContext();

        $prepare = $this->prepareSale($token);
        $customerId = $context->customer->id;

        $payLaneCards = new PayLaneCards();
        return $payLaneCards->isSaleAlreadyExist($customerId, $prepare['id_sale'], $prepare['card']);
    }

    public function handlePayment($paymentParams)
    {
        $context = Context::getContext();

        if (!empty($paymentParams['creditCardString'])) {
            $customerId = $context->customer->id;
            $context->cookie->{'paylane_cards_'.$customerId} = $paymentParams['creditCardString'];
        }

        //using single-click way
        if ((!empty($paymentParams['authorization_id']) || !empty($paymentParams['id_sale'])) && ($paymentParams['id_sale'] != "addNewCard")) { 

            if (!$this->isValidate($paymentParams['id_sale'])) {
                $result = array(
                    'order_status' => 'ERROR',
                    'success' => false,
                    'error' => array('error_description' => 'Wrong token card'),
                );
                if ($this->isOldPresta()) {
                    $result['order_status'] = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
                }
                return $result;
            }

            $prepareSale = $this->prepareSale($paymentParams['id_sale']);
            $paymentParams['id_sale'] = $prepareSale['id_sale'];

            $result = $this->handleSingleClickPayment($paymentParams);
        } else { // 3DS transaction type
            $data = array();
            $data['sale'] = $this->prepareSaleData();
            $data['customer'] = $this->prepareCustomerData();
            $data['card'] = array(
                'token' => $paymentParams['token']
            );
            
            if ((boolean)Configuration::get('paylane_creditcard_3ds')) { // 3DS check enabled
                $data['back_url'] = $paymentParams['back_url']; 
                
                if ($this->isOldPresta()) {
                    $data['back_url'] = $context->link->getModuleLink(
                        'paylane',
                            '3dsvalidation',
                            array('cart_id' => $cart->id, 'secure_key' => $customer->secure_key, 'payment_method' => $this->paymentMethod),
                            true);
                }
                $result = $this->client->checkCard3DSecureByToken($data);

                if (isset($result['is_card_enrolled']) && $result['is_card_enrolled']) {
                    // if card enrolled
                    Tools::redirect($result['redirect_url']);

                    //single click save credit card
                    if ((!empty($result['id_sale'])) && ($context->customer->isLogged())) {
                        $payLaneCards = new PayLaneCards();
                        $customerId = $context->customer->id;
                        $creditCardString = $context->cookie->{'paylane_cards_'.$customerId};
                        unset($context->cookie->{'paylane_cards_'.$customerId});
                        if (!$payLaneCards->checkIfCardAlreadyExist($customerId, $creditCardString)) {
                            $payLaneCards->insertCard($result['id_sale'], $customerId, $creditCardString);
                        }
                    }
                    die;
                    
                }else{
                    $result = $this->client->cardSaleByToken($data);

                    //single click save credit card
                    if ((!empty($result['id_sale'])) && ($context->customer->isLogged())) {
                        $payLaneCards = new PayLaneCards();
                        $customerId = $context->customer->id;
                        $creditCardString = $context->cookie->{'paylane_cards_'.$customerId};
                        unset($context->cookie->{'paylane_cards_'.$customerId});
                        if (!$payLaneCards->checkIfCardAlreadyExist($customerId, $creditCardString)) {
                            $payLaneCards->insertCard($result['id_sale'], $customerId, $creditCardString);
                        }
                    }
                }
                
            } else { // 3DS check disabled
                $result = $this->client->cardSaleByToken($data); 

                //single click save credit card
                if ((!empty($result['id_sale'])) && ($context->customer->isLogged())) {
                    $payLaneCards = new PayLaneCards();
                    $customerId = $context->customer->id;
                    $creditCardString = $context->cookie->{'paylane_cards_'.$customerId};
                    unset($context->cookie->{'paylane_cards_'.$customerId});
                    if (!$payLaneCards->checkIfCardAlreadyExist($customerId, $creditCardString)) {
                        $payLaneCards->insertCard($result['id_sale'], $customerId, $creditCardString);
                    }
                }
            }
        }

        return $result;
    }

    public function handle3DSPayment($paymentParams)
    {
        $result = array(
            'success' => false,
            'error' => null
        );

        if ((boolean)Configuration::get('paylane_creditcard_3ds') && $paymentParams['id_3dsecure_auth']) {
            $ds3Status = $this->client->saleBy3DSecureAuthorization(array(
                'id_3dsecure_auth' => $paymentParams['id_3dsecure_auth']
            ));
            if (!empty($ds3Status['success']) && $ds3Status['success']) {
                $result = array(
                    'order_status' => 'CLEARED',
                    'success' => $ds3Status['success'],
                    'id_sale' => $ds3Status['id_sale']
                );
                if ($this->isOldPresta()) {
                    $result['order_status'] = Configuration::get('PS_OS_PAYMENT');
                }
            } else {
                $result = array(
                    'order_status' => 'ERROR',
                    'success' => $ds3Status['success'],
                    'error' => $ds3Status['error']
                );
                if ($this->isOldPresta()) {
                    $result['order_status'] = Configuration::get('PAYLANE_PAYMENT_STATUS_FAILED');
                }
            }
        }

        $context = Context::getContext();

        if ((!empty($result['id_sale'])) && ($context->customer->isLogged())) {
            $payLaneCards = new PayLaneCards();
            $customerId = $context->customer->id;
            $creditCardString = $context->cookie->{'paylane_cards_'.$customerId};
            unset($context->cookie->{'paylane_cards_'.$customerId});
            if (!$payLaneCards->checkIfCardAlreadyExist($customerId, $creditCardString)) {
                $payLaneCards->insertCard($result['id_sale'], $customerId, $creditCardString);
            }
        }

        return $result;
    }

    public function getPaymentConfig()
    {
        return array(
            'paylane_creditcard_label' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_LABEL', 'creditcard'),
                'default' => $this->paylane->l('PAYLANE_CREDITCARD_DEFAULT', 'creditcard'),
            ),
            'paylane_creditcard_showImg' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_SHOW_PAYMENT_METHOD_IMAGE', 'creditcard'),
                'default' => 1
            ),
            'paylane_creditcard_fraud_check_override' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_FRAUD_CHECK_OVERRIDE', 'creditcard'),
            ),
            'paylane_creditcard_fraud_check' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_FRAUD_CHECK', 'creditcard'),
            ),
            'paylane_creditcard_avs_override' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_AVS_OVERRIDE', 'creditcard'),
            ),
            'paylane_creditcard_avs' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_AVS_LEVEL', 'creditcard'),
                'options' => array(
                    array(
                        'value' => 0,
                        'label' => '0'
                    ),
                    array(
                        'value' => 1,
                        'label' => '1'
                    ),
                    array(
                        'value' => 2,
                        'label' => '2'
                    ),
                    array(
                        'value' => 3,
                        'label' => '3'
                    ),
                    array(
                        'value' => 4,
                        'label' => '4'
                    ),
                )
            ),
            'paylane_creditcard_3ds' => array(
                'type' => 'select',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_3DS_CHECK', 'creditcard'),
                'default' => 1
            ),
            'paylane_creditcard_blocked_amount' => array(
                'type' => 'text',
                'label' => $this->paylane->l('PAYLANE_CREDITCARD_BLOCKED_AMOUNT', 'creditcard'),
                'required' => false,
                'default' => 1
            ),
            // @TODO: To implement that function
            // 'paylane_creditcard_single_click' => array(
            //     'type' => 'select',
            //     'label' => 'Single click payments'
            // )
        );
    }

    public function generatePaymentForm()
    {
        $context = Context::getContext();
        $context->smarty->assign($this->getTemplateVars());
        return $this->fetchTemplate('front/payment_form/credit_card.tpl');
    } 

    public function getTemplateVars()
    {
        $context = Context::getContext();

        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02d", $i);
        }

        $years = array();
        for ($i = 0; $i <= 10; $i++) {
            $years[] = date('Y', strtotime('+'.$i.' years'));
        }

        $authorizeId = $this->getAuthorizeId();
        $isFirstOrder = $this->isCustomerFirstOrder();

        $payLaneCards = new PayLaneCards();
        $creditCardsArray = $payLaneCards->getCreditCardsByCustomerId($context->customer->id);

        return array(
            'action' => $context->link->getModuleLink('paylane', 'validation', array(), true),
            'months' => $months,
            'years' => $years,
            'creditCardsArray' => $creditCardsArray,
            'isSingleClickActive' => (boolean)Configuration::get('paylane_creditcard_single_click'),
            'authorizeId' => $authorizeId,
            'isFirstOrder' => $isFirstOrder,
            'lastSaleId' => 0, // @TODO: Handle in future
            'apiKey' => (string)Configuration::get('PAYLANE_GENERAL_PUBLIC_KEY_API'),
            'paymentMethodLabel' => (string)Configuration::get('paylane_creditcard_label'),
            'withImage' => (bool)Configuration::get('paylane_creditcard_showImg')
        );
    }

    public function generatePaymentLinkTemplate()
    {
        $context = Context::getContext();

        $context->smarty->assign(array(
            'paymentMethodLabel' => Configuration::get('paylane_creditcard_label'),
            'withImage' => (bool)Configuration::get('paylane_creditcard_showImg')
        ));

        return $this->fetchTemplate('front/payment_link/credit_card.tpl');
    }

    protected function handleSingleClickPayment($paymentParams)
    {
        $data = array();

        if (!empty($paymentParams['id_sale'])) {
            $data['id_sale'] = $paymentParams['id_sale'];
        }
        if (!empty($paymentParams['authorization_id'])) {
            $data['id_authorization'] = $paymentParams['authorization_id'];
        }

        $context = Context::getContext();
        $cart = $context->cart;
        $currency = $context->currency;

        $data['amount'] = sprintf('%01.2f', $cart->getOrderTotal());
        $data['currency'] = $currency->iso_code;
        $data['description'] = $cart->id;

        if (!empty($paymentParams['id_sale'])) {
            $result = $this->client->resaleBySale($data);
        }
        if (!empty($paymentParams['authorization_id'])) {
            $result = $this->client->resaleByAuthorization($data);
        }

        return $result;
    }

    protected function isCustomerFirstOrder()
    {
        $context = Context::getContext();
        $customer = $context->customer;

        $orderCount = Order::getCustomerNbOrders($customer->id);

        return (boolean)($orderCount < 1);
    }

    protected function getAuthorizeId()
    {
        $context = Context::getContext();
        $customer = $context->customer;

        $authorizeId = (int)Configuration::get('paylane_card_auth_id_' . $customer->id);

        return $authorizeId;
    }

    protected function getCustomerLastOrderPaylaneSaleId()
    {
        $context = Context::getContext();
        $customer = $context->customer;

        $orderSql = 'SELECT `reference`
                FROM `'._DB_PREFIX_.'orders`
                WHERE `id_customer` = '.(int)$customer->id
                    . ' AND `payment` = "' . (string)Configuration::get('paylane_creditcard_label') . '"'
                    . ' ORDER BY `date_upd` DESC'
                    .Shop::addSqlRestriction();

        $result = Db::getInstance()->getRow($orderSql);

        $sql = 'SELECT `transaction_id`
                FROM `'._DB_PREFIX_.'order_payment`
                WHERE `order_reference` = "'.(string)$result['reference'] . '"'
                    .Shop::addSqlRestriction();
        $result = Db::getInstance()->getRow($sql);

        return isset($result['transaction_id']) ? $result['transaction_id'] : null;
    }
}