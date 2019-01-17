<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SpecificPriceRule extends SpecificPriceRuleCore
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'specific_price_rule',
        'primary' => 'id_specific_price_rule',
        'fields' => array(
            'name' =>            array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'id_shop' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_country' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_currency' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_group' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'from_quantity' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'price' =>        array('type' => self::TYPE_FLOAT, 'validate' => 'isNegativePrice', 'required' => true),
            'reduction' =>        array('type' => self::TYPE_FLOAT, 'validate' => 'isNegativePrice', 'required' => true),
            'reduction_tax' =>            array('type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true),
            'reduction_type' => array('type' => self::TYPE_STRING, 'validate' => 'isReductionType', 'required' => true),
            'from' =>            array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'to' =>            array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
        ),
    );
}
