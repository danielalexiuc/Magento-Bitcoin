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

class Appmerce_Bitcoin_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Bitcoin address
     *
     * @var string
     */
    protected $_address;

    /**
     * Bitcoin amount
     *
     * @var string
     */
    protected $_amount;

    /**
     * QR URI
     *
     * @var int
     */
    protected $_qr;

    /**
     * Quote amount
     *
     * @var int
     */
    protected $_quoteAmount;

    /**
     * Bitcoin URI
     *
     * @var int
     */
    protected $_bitcoin;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('appmerce/bitcoin/info.phtml');
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
     * Get Bitcoin amount from order payment
     *
     * @return string
     */
    public function getAmount()
    {
        if (is_null($this->_amount)) {
            $this->_amount = $this->getInfo()->getAdditionalInformation('amount');
        }
        return $this->_amount;
    }

    /**
     * Get Bitcoin quote amount from order payment
     *
     * @return string
     */
    public function getQuoteAmount()
    {
        if (is_null($this->_quoteAmount)) {
            $this->_quoteAmount = $this->getApi()->getAmount($this->getInfo()->getQuote());
        }
        return $this->_quoteAmount;
    }

    /**
     * Get Bitcoin address from order payment
     *
     * @return string
     */
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->getInfo()->getAdditionalInformation('address');
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
            $label = Mage::helper('bitcoin')->__('%s - Order %s', $this->getInfo()->getOrder()->getStore()->getName(), $this->getInfo()->getOrder()->getIncrementId());
            $this->_bitcoin = 'bitcoin:' . $this->getAddress() . '?amount=' . $this->getAmount() . '&label=' . $label;
        }
        return $this->_bitcoin;
    }

}
