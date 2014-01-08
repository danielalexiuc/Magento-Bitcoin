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

class Appmerce_Bitcoin_Model_Api_Pull extends Varien_Object
{
    protected $_code = 'bitcoin';

    /**
     * Return order process instance
     *
     * @return Appmerce_Bitcoin_Model_Process
     */
    public function getProcess()
    {
        return Mage::getSingleton('bitcoin/process');
    }

    /**
     * Return Api instance
     *
     * @return Appmerce_Bitcoin_Api
     */
    public function getApi()
    {
        return Mage::getSingleton('bitcoin/api');
    }

    /**
     * Cron transaction status check
     *
     * Check orders created in the last 24 hrs.
     * After that manual check is required.
     */
    public function transactionStatusCheck($shedule = null)
    {
        // Time preparations: from -1w untill now
        $gmtStamp = Mage::getModel('core/date')->gmtTimestamp();
        $from = date('Y-m-d H:i:s', $gmtStamp - 604800);
        $to = date('Y-m-d H:i:s', $gmtStamp);

        // Database preparations
        $db = Mage::getSingleton('core/resource')->getConnection('core_read');
        $orderTable = Mage::getSingleton('core/resource')->getTable('sales_order');
        $orderPaymentTable = Mage::getSingleton('core/resource')->getTable('sales_payment_transaction');

        $result = $db->query('SELECT sfo.entity_id, sfop.transaction_id
            FROM ' . $orderTable . ' sfo 
            INNER JOIN ' . $orderPaymentTable . ' sfop 
            ON sfop.parent_id = sfo.entity_id 
            WHERE (sfo.state = "' . Mage_Sales_Model_Order::STATE_NEW . '" OR sfo.state = "' . Mage_Sales_Model_Order::STATE_PENDING_PAYMENT . '")
            AND sfo.created_at >= "' . $from . '"
            AND sfo.created_at <= "' . $to . '"
            AND sfop.method = "' . $this->_code . '"');

        if (!$result) {
            return $this;
        }

        // Update order statuses
        $order = Mage::getModel('sales/order');
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if (!$row) {
                break;
            }

            $order->reset();
            $order->load($row['entity_id']);

            // Find most recent transactions for address/account
            // In our setup each account represents a single order!
            $address = $order->getPayment()->getAdditionalInformation('address');
            if (!$address) {
                continue;
            }
            $amount = $order->getPayment()->getAdditionalInformation('amount');
            $account = $this->getApi()->getBitcoin()->getAccount($address);
            $transactions = $this->getApi()->getBitcoin()->listTransactions($account);

            // Blockchain handles listtransactions differently
            $host = $this->getApi()->getConfigData('rpc_host');
            if (strpos($host, 'blockchain.info') !== FALSE) {
                $transactions = $transactions['transactions'];
            }

            $minimum_confirmations = (int)$order->getPayment()->getAdditionalInformation('minimum_confirmations');

            // Check transactions / balance
            // There can be more than 1 transaction. So we poll regularly.
            // Total balance is checked with getReceivedByAddress()
            if (isset($transactions[0])) {
                $confirmations = $order->getPayment()->getAdditionalInformation('confirmations');
                $transactionId = $transactions[0]['txid'];

                // Check if full amount was received for this account (=order)
                $balance = $this->getApi()->getBitcoin()->getReceivedByAddress($address, $minimum_confirmations);
                if ($balance >= $amount) {
                    $note = Mage::helper('bitcoin')->__('Confirmed %s BTC total balance (%s/%s).', $balance, $transactions[0]['confirmations'], $minimum_confirmations);
                    $this->getProcess()->success($order, $note, $transactionId);
                }

                // Check most recent transaction
                elseif ($transactions[0]['confirmations'] > $confirmations) {
                    $order->getPayment()->setAdditionalInformation('confirmations', $transactions[0]['confirmations']);
                    $order->getPayment()->save();

                    $note = Mage::helper('bitcoin')->__('Unconfirmed %s BTC transaction (%s/%s).', $transactions[0]['amount'], $transactions[0]['confirmations'], $minimum_confirmations);
                    $this->getProcess()->pending($order, $note, $transactionId);
                }
            }
        }

        return $this;
    }

}
