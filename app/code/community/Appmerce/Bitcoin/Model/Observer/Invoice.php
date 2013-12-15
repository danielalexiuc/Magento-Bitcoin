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

class Appmerce_Bitcoin_Model_Observer_Invoice
{
    /*
     * Inherit transaction_id for refunds
     */
    public function sales_order_invoice_save_before(Varien_Event_Observer $observer)
    {
        $method = $observer->getEvent()->getInvoice()->getOrder()->getPayment()->getMethod();
        if (strpos($method, 'bitcoin_') !== false) {
            $transactionId = $observer->getEvent()->getInvoice()->getOrder()->getPayment()->getLastTransId();
            $observer->getEvent()->getInvoice()->setTransactionId($transactionId);
        }
    }

    /*
     * Inherit transaction_id for refunds
     */
    public function sales_order_creditmemo_save_before(Varien_Event_Observer $observer)
    {
        $method = $observer->getEvent()->getCreditmemo()->getOrder()->getPayment()->getMethod();
        if (strpos($method, 'bitcoin_') !== false) {
            if ($observer->getEvent()->getCreditmemo()->getInvoice()) {
                $transactionId = $observer->getEvent()->getCreditmemo()->getInvoice()->getTransactionId();
                $observer->getEvent()->getCreditmemo()->setTransactionId($transactionId);
            }
        }
    }

}
