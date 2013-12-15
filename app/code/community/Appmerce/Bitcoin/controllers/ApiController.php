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

class Appmerce_Bitcoin_ApiController extends Appmerce_Bitcoin_Controller_Common
{
    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Render placement form and set New Order Status
     *
     * @see bitcoin/api/payment
     */
    public function paymentAction()
    {
        $this->saveCheckoutSession();
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());

        // Debug
        if ($this->getApi()->getConfigData('debug_flag')) {
            if ($order->getId()) {
                $url = $this->getRequest()->getPathInfo();
                $info = $this->getApi()->getBitcoin()->getInfo();
                $data = print_r($info, true);
                Mage::getModel('bitcoin/api_debug')->setDir('out')->setUrl($url)->setData('data', $data)->save();
            }
        }

        // Get BTC amount and (new) Bitcoin address
        $amount = $this->getApi()->getAmount($order);
        $address = $order->getPayment()->getAdditionalInformation('address');
        if (!$address) {
            $address = $this->getApi()->getBitcoin()->getNewAddress($order);
        }

        // Save re-usable information
        $order->getPayment()->setAdditionalInformation('address', $address);
        $order->getPayment()->setAdditionalInformation('amount', $amount);
        $order->getPayment()->setAdditionalInformation('confirmations', -1);
        $order->getPayment()->setAdditionalInformation('minimum_confirmations', $this->getApi()->getConfigData('confirmations'));
        // $order->getPayment()->save();

        // Send (optional) order email incl. payment instructions
        if (!$order->getEmailSent() && $this->getApi()->getConfigData('order_email')) {
            $order->sendNewOrderEmail()->setEmailSent(true);
        }
        $order->save();

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Confirm action
     *
     * @see bitcoin/api/success
     */
    public function confirmAction()
    {
        $this->getProcess()->done();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
    
    /**
     * Decline action
     *
     * @see bitcoin/api/decline
     */
    public function declineAction()
    {
        $this->getProcess()->repeat();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

}
