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
class PayLaneCards extends ObjectModel
{
    public static $definition = array(
        'table' => 'endora_paylane_cards',
        'primary' => 'id_sale',
        'fields' => array(
            'id_sale'            => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt'
            ),
            'customer_id'        => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt'
            ),
            'credit_card_number' => array(
                'type' => self::TYPE_STRING,
                'size' => 255
            )
        ),
    );

    public function getCreditCardsByCustomerId($customerId)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('endora_paylane_cards');
        $sql->where('customer_id = '.$customerId);

        $result = Db::getInstance()->executeS($sql);
        return $result;
    }

    public function checkIfCardAlreadyExist($customerId, $creditCardNumber)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('endora_paylane_cards');
        $sql->where('customer_id = '.$customerId.' AND '.'credit_card_number = \''.$creditCardNumber.'\'');

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function isSaleAlreadyExist($customerId, $idSale, $card)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('endora_paylane_cards');
        $sql->where('customer_id = '.(int)$customerId.' AND '.'credit_card_number = \''.$card.'\' AND '.'id_sale = '.(int)$idSale.'');

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function insertCard($id_sale, $customerId, $creditCardNumber)
    {
        $query = "INSERT INTO `"._DB_PREFIX_."endora_paylane_cards` (`id_sale`, `customer_id`, `credit_card_number`)";
        $query .= " VALUES (".$id_sale.",".$customerId.",'".$creditCardNumber."')";
        Db::getInstance()->execute($query);
    }
}
