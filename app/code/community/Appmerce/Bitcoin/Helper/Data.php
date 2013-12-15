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

class Appmerce_Bitcoin_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * We calculate weighted exchange rates every 24h
     * from Coindesk weighted prices
     */
    const REQUEST_TIMEOUT = 30;
    const EXCHANGE_URL = 'https://api.coindesk.com/v1/bpi/currentprice/';

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
     * Request exchange rate
     *
     * @return array
     */
    protected function currencyQuery($currencyCode)
    {
        $response = $this->curlGet(self::EXCHANGE_URL . $currencyCode . '.json');
        $json = json_decode($response, TRUE);
        if (!$json) {
            $errorMessage = Mage::helper('bitcoin')->__('Bitcoin exchange rate could not be fetched.');
            Mage::getSingleton('core/session')->addError($errorMessage);
            return false;
        }
        return $json;
    }

    /**
     * Get URL via Curl
     */
    public function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Get the BTC rate for the current currency
     *
     * @return float
     */
    public function getExchangeRate()
    {
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        $cache = Mage::app()->getCache();
        $cacheTag = 'Appmerce_Bitcoin_BTC_' . $currencyCode;
        $currencyRate = $cache->load($cacheTag);
        if (empty($currencyRate)) {
            if ($currencyCode === 'BTC') {
                $currencyRate = 'BTC';
            }
            else {
                $response = $this->currencyQuery($currencyCode);
                if (!$response || !is_array($response) || !isset($response['bpi']) || !array_key_exists($currencyCode, $response['bpi'])) {
                    $currencyRate = false;
                }
                else {
                    $period = $this->getApi()->getConfigData('period');
                    $currencyRate = $response['bpi'][$currencyCode]['rate'];
                    $cache->save($currencyRate, $cacheTag, array($cacheTag), $period);
                }
            }
        }

        return $currencyRate;
    }

}
