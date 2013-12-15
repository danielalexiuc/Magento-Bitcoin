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

class Appmerce_Bitcoin_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * BTC amount
     *
     * @var float
     */
    protected $_amount;

    /**
     * Formatted price
     *
     * @var int
     */
    protected $_price;

    /**
     * QR URI
     *
     * @var int
     */
    protected $_qr;

    /**
     * Bitcoin URI
     *
     * @var int
     */
    protected $_bitcoin;

    /**
     * Payable Bitcoin address
     *
     * @var string
     */
    protected $_address;

    public function __construct()
    {
    }

    /**
     * Return checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return payment API model
     *
     * @return Appmerce_Bitcoin_Model_Api
     */
    protected function getApi()
    {
        return Mage::getSingleton('bitcoin/api');
    }

    /**
     * Return order instance by lastRealOrderId
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            $order = $this->getOrder();
        }
        elseif ($this->getCheckout()->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        }

        return $order;
    }

    /**
     * Get formatted BTC amount
     *
     * @return string
     */
    public function getAmount()
    {
        if (is_null($this->_amount)) {
            $this->_amount = $this->getApi()->getAmount($this->_getOrder());
        }
        return $this->_amount;
    }

    /**
     * Get formatted price
     *
     * @return string
     */
    public function getPrice()
    {
        if (is_null($this->_price)) {
            $this->_price = Mage::helper('core')->currency($this->_getOrder()->getGrandTotal(), true, false);
        }
        return $this->_price;
    }

    /**
     * Get payable Bitcoin address
     *
     * @return string
     */
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->_getOrder()->getPayment()->getAdditionalInformation('address');
        }
        return $this->_address;
    }

    /**
     * Get QR Code Google API URI
     *
     * @return string
     */
    public function getQr($size = 75)
    {
        if (is_null($this->_qr)) {
            $uri = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&chld=L|0&cht=qr&chl=';
            $this->_qr = $uri . urlencode($this->getBitcoin());
        }
        return $this->_qr;
    }

    /**
     * Get Bitcoin URI
     * https://en.bitcoin.it/wiki/URI_Scheme
     *
     * @return string
     */
    public function getBitcoin()
    {
        if (is_null($this->_bitcoin)) {
            $label = Mage::helper('bitcoin')->__('%s - Order %s', Mage::app()->getStore()->getName(), $this->_getOrder()->getIncrementId());
            $this->_bitcoin = 'bitcoin:' . $this->getAddress() . '?amount=' . $this->getAmount() . '&label=' . $label;
        }
        return $this->_bitcoin;
    }

    /**
     * Return gateway path from admin settings
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getApi()->getConfig()->getApiUrl('confirm');
    }

}
