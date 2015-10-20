<?php

/**
 * SEQR payment controller.
 *
 * Provide actions and pages for SEQR payment proceed
 */
class Seamless_SEQR_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Displaying payment form (QR or Mobile Application link).
     *
     * Show page or no route if order not exist. (For displaying form execute sendInvoice on SEQR API)
     */
    public function indexAction()
    {
        $order = $this->loadOrder();
        if (! $order
            || $order->getStatus() != Mage::getStoreConfig('payment/seqr/order_status')
            || ! $this->isSEQRPayment($order))
        {
            $this->_forward('noRoute');
            return;
        }

        Mage::getSingleton('seqr/invoice')->getInvoice($order);

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Display information that order was canceled.
     */
    public function cancelAction()
    {
        $order = $this->loadOrder();
        if (! $order || ! $this->isSEQRPayment($order))
        {
            $this->_forward('noRoute');
            return;
        }

        $this->restoreShoppingCart($order);
        $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl(), array('_secure' => true));
    }

    /**
     * Action for submit final status (failed, paid, canceled).
     *
     * Redirects to page which according to the status or no route if order
     * For more details about rotes please check Seamless_SEQR_PaymentController::selectRoute method.
     */
    public function submitAction()
    {
        $order = $this->loadOrder();
        if (! $order || ! $this->isSEQRPayment($order))
        {
            $this->_forward('noRoute');
            return;
        }

        $report = Mage::getSingleton('seqr/invoice')->getPaymentStatus($order);
        $route = $this->selectRoute($report);

        if (! $route)
        {
            $this->_forward('noRoute');
            return;
        }

        if ($report->status === 'CANCELED')
        {
            $this->restoreShoppingCart($order);
            $this->_redirectUrl($route, array('_secure' => true ));
            return;
        }

        $this->_redirect($route, array('order_id' => $order->getId(), '_secure' => true ));
    }

    /**
     * Cancel old orders in SEQR.
     */
    public function cancelOldOrdersAction()
    {
        Mage::getModel('seqr/observer')->cancelOldOrders();
    }

    /**
     * Check current status of invoice in SEQR.
     */
    public function checkAction()
    {
        $order = $this->loadOrder();
        if (! $order || ! $this->isSEQRPayment($order))
        {
            $this->getResponse()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(Zend_Json::encode(array( 'error' => Mage::helper('core')->__('Could not find order'))));
            return;
        }

        $status = Mage::getSingleton('seqr/invoice')->getPaymentStatus($order);

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($status));
    }

    /**
     * Check if current order payment method is SEQR.
     *
     * @param Mage_Sales_Model_Order $order Order for check
     * @return bool True if payment method of order is SEQR
     */
    private function isSEQRPayment(Mage_Sales_Model_Order $order)
    {
        return $order->getPayment() || $order->getPayment()->getMethodInstance()->getCode() != 'seqr';
    }

    /**
     * Load current order from Magento.
     *
     * Sources:
     *  1  Magento register
     *  2. Request param
     *  3. Last order id of session
     *
     * @return Mage_Core_Model_Abstract|null Order object or null if order doesn't created.
     */
    private function loadOrder()
    {
        $orderid = Mage::registry('orderid');

        if (! $orderid) $orderid = $this->getRequest()->getParam('id');
        if (! $orderid) $orderid = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (! $orderid) return null;

        Mage::register('orderid', $orderid);
        return Mage::getModel('sales/order')->load($orderid);
    }

    private function restoreShoppingCart(Mage_Sales_Model_Order $order)
    {
        if (! $order->canCancel()) return false;

        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        $quote->setIsActive(true)->save();
        Mage::getModel('checkout/cart')->setQuote($quote)->save();
        Mage::getSingleton('checkout/session')->setFirstTimeChk('0');

        return true;
    }

    /**
     * Select path according status of SEQR invoice
     *
     * @param object $report Status report from getPaymentStatus request.
     * @return null|string Null if status not correct or url
     */
    private function selectRoute($report)
    {
        if (! $report || ! $report->status) return null;

        switch ($report->status)
        {
            case 'PAID': return 'checkout/onepage/success';
            case 'ISSUED': return 'seqr/payment';
            case 'CANCELED': return Mage::helper('checkout/url')->getCheckoutUrl();
            case 'FAILED': return 'checkout/onepage/failure';
        }

        return null;
    }
}