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

class Appmerce_Bitcoin_Model_Api extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'bitcoin';
    protected $_formBlockType = 'bitcoin/form';
    protected $_infoBlockType = 'bitcoin/info';

    // Magento features
    protected $_isGateway = false;
    protected $_canOrder = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false;

    // Restrictions
    protected $_allowCurrencyCode = array();

    // Local variables
    const SUPPORTED_CURRENCIES = 'http://api.coindesk.com/v1/bpi/supported-currencies.json';
    const CACHE_LIFETIME = '7d';

    /**
     * Return Bitcoin config instance
     *
     * @return Appmerce_Bitcoin_Model_Config
     */
    public function __construct()
    {
        $this->_config = Mage::getSingleton('bitcoin/config');

        // Set $_allowCurrencyCode, cache once per week
        $cache = Mage::app()->getCache();
        $cacheTag = 'Appmerce_Bitcoin_allowCurrencyCode';
        $this->_allowCurrencyCode = unserialize($cache->load($cacheTag));
        if (empty($this->_allowCurrencyCode)) {
            $response = Mage::helper('bitcoin')->curlGet(self::SUPPORTED_CURRENCIES);
            $json = json_decode($response, TRUE);
            if (!$json || !is_array($json)) {
                $errorMessage = Mage::helper('bitcoin')->__('Supported currencies could not be fetched.');
                Mage::getSingleton('core/session')->addError($errorMessage);
                return false;
            }
            else {
                foreach ($json as $key => $values) {
                    $this->_allowCurrencyCode[] = $values['currency'];
                }
                $cache->save(serialize($this->_allowCurrencyCode), $cacheTag, array($cacheTag), self::CACHE_LIFETIME);
            }
        }

        return $this;
    }

    /**
     * Return bitcoin configuration instance
     *
     * @return Appmerce_Bitcoin_Model_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Validate if payment is possible
     *  - check allowed currency codes
     *
     * @return bool
     */
    public function validate()
    {
        parent::validate();
        $currency_code = $this->getCurrencyCode();
        if (!empty($this->_allowCurrencyCode) && !in_array($currency_code, $this->_allowCurrencyCode)) {
            $errorMessage = Mage::helper('bitcoin')->__('Selected currency (%s) is not compatible with this payment method.', $currency_code);
            Mage::throwException($errorMessage);
        }
        return $this;
    }

    /**
     * Decide currency code type
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Bitcoin redirect URL for payment page
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getConfig()->getApiUrl('payment');
    }

    /**
     * Return order process instance
     *
     * @return Appmerce_Bitcoin_Model_Api_Bitcoin
     */
    public function getBitcoin()
    {
        return Mage::getSingleton('bitcoin/api_bitcoin');
    }

    /**
     * Get formatted BTC amount
     *
     * @return string
     */
    public function getAmount($order)
    {
        $price = $order->getGrandTotal();
        $rate = Mage::helper('bitcoin')->getExchangeRate();
        return number_format($price / $rate, 8);
    }

    /**
     * Get order statuses
     */
    public function getOrderStatus()
    {
        $status = $this->getConfigData('order_status');
        if (empty($status)) {
            $status = Appmerce_Bitcoin_Model_Config::DEFAULT_STATUS_PENDING;
        }
        return $status;
    }

    public function getPendingStatus()
    {
        $status = $this->getConfigData('pending_status');
        if (empty($status)) {
            $status = Appmerce_Bitcoin_Model_Config::DEFAULT_STATUS_PENDING_PAYMENT;
        }
        return $status;
    }

    public function getProcessingStatus()
    {
        $status = $this->getConfigData('processing_status');
        if (empty($status)) {
            $status = Appmerce_Bitcoin_Model_Config::DEFAULT_STATUS_PROCESSING;
        }
        return $status;
    }

}
