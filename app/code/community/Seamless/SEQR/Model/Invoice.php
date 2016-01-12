<?php

/**
 * Main SEQR actions.
 */
class Seamless_SEQR_Model_Invoice {

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed|null
     */
    public function getInvoice(Mage_Sales_Model_Order $order)
    {
        $paymentData = $this->getAdditionalData($order);
        if ($paymentData) return $paymentData;

        return $this->sendInvoice($order);
    }

    public function getPaymentStatus(Mage_Sales_Model_Order $order)
    {
        $paymentData = $this->getAdditionalData($order);
        if (! $paymentData) return null;
        if ($paymentData->status->status === 'PAID' || $paymentData->status->status === 'CANCELED') return $paymentData->status;

        $result = Mage::getSingleton('seqr/api')->getPaymentStatus($order, $paymentData->invoiceReference, $paymentData->version);

        $paymentData->status = $result;
        $paymentData->version = $result->version;

        $this->setAdditionalData($order, $paymentData);

        if ($result->status === 'PAID')
        {
            try
            {
                $order->setStatus(Mage::getStoreConfig('payment/seqr/paid_order_status'));
                $order->setState('complete_payment');
                $order->save();

                if ($order->getCanSendNewEmailFlag()) $order->sendNewOrderEmail();

                $payment = $order->getPayment();
                $payment->setTransactionId($paymentData->invoiceReference);
                $payment->setIsTransactionClosed(1);

                $transaction = Mage::getModel('core/resource_transaction');
                $transaction->addObject($order);

                if(! $order->canInvoice()) Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));

                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transaction->addObject($invoice);
                $transaction->save();
            }
            catch(Exception $e)
            {
                Mage::logException($e);
            }
        }
        else if ($result->status === 'CANCELED')
        {
            $order->setStatus(Mage::getStoreConfig('payment/seqr/canceled_order_status'))->save();
        }

        return $result;
    }

    public function refund(Mage_Sales_Model_Order $order, $creditMemo)
    {
        $creditMemos = Mage::getResourceModel('sales/order_creditmemo_collection');
        $creditMemos->addFieldToFilter('order_id', $order->getId());
        $creditMemos->setOrder('created_at','DESC');
        $creditMemos->load();

        return Mage::getSingleton('seqr/api')->refundPayment($order, $creditMemo, $this->getAdditionalData($order)->status->ersReference);
    }

    public function cancel(Mage_Sales_Model_Order $order)
    {
        $order->cancel();
        $order->setStatus('canceled');
        $order->save();

        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        $quote->setIsActive(1);
        $quote->save();
    }

    public function cancelInvoice(Mage_Sales_Model_Order $order)
    {
        $paymentData = $order->getPayment()->getAdditionalData();
        if (! $paymentData) return null;
        if ($paymentData->status->status === 'PAID') return false;

        $result = Mage::getSingleton('seqr/api')->cancelInvoice($order, json_decode($paymentData)->invoiceReference);

        $paymentData->status->status = 'CANCELED';

        $this->setAdditionalData($order, $paymentData);
        $order->setStatus(Mage::getStoreConfig('payment/seqr/canceled_order_status'))->save();

        return $result && $result->resultCode === 0;
    }

    private function sendInvoice(Mage_Sales_Model_Order $order)
    {
        $data = Mage::getSingleton('seqr/api')->sendInvoice($order);
        if (! $data) return null;

        $this->setAdditionalData($order, $data);

        return $data;
    }

    public function getAdditionalData(Mage_Sales_Model_Order $order)
    {
        return json_decode($order->getPayment()->getAdditionalData());
    }

    public function setAdditionalData(Mage_Sales_Model_Order $order, $data)
    {
        if (! $data || ! $order) return null;
        $order->getPayment()->setAdditionalData(json_encode($data))->save();

        return $data;
    }
}
