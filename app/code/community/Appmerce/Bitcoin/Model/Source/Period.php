<?php
/**
 * Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 *
 * @extension   Bitcoin
 * @type        Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Magento Commerce
 * @package     Appmerce_Bitcoin
 * @copyright   Copyright (c) 2011-2013 Appmerce (http://www.appmerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Appmerce_Bitcoin_Model_Source_Period
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 3600,
                'label' => Mage::helper('bitcoin')->__('1 hour'),
            ),
            array(
                'value' => 10800,
                'label' => Mage::helper('bitcoin')->__('3 hours'),
            ),
            array(
                'value' => 21600,
                'label' => Mage::helper('bitcoin')->__('6 hours (default)'),
            ),
            array(
                'value' => 86400,
                'label' => Mage::helper('bitcoin')->__('24 hours'),
            ),
            array(
                'value' => 604800,
                'label' => Mage::helper('bitcoin')->__('7 days'),
            ),
        );
    }

}
