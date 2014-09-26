<?php

/**
 * Main SEQR actions.
 */
class Seamless_SEQR_Model_Invoice {

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed|null
     */
    public function getInvoice(Mage_Sales_Model_Order $order) {

        $paymentData = $this->getAdditionalData($order);
        if ($paymentData) return $paymentData;

        return $this->sendInvoice($order);
    }

    public function getPaymentStatus(Mage_Sales_Model_Order $order) {

        $paymentData = $this->getAdditionalData($order);
        if (! $paymentData) return null;
        if ($paymentData->status->status === 'PAID'
            || $paymentData->status->status === 'CANCELED') return $paymentData->status;

        $result = Mage::getSingleton('seqr/api')
            ->getPaymentStatus($paymentData->invoiceReference, $paymentData->version);

        $paymentData->status = $result;
        $paymentData->version = $result->version;

        $this->setAdditionalData($order, $paymentData);

        if ($result->status->status === 'PAID') {
            $order->setStatus(Mage::getStoreConfig('payment/seqr/paid_order_status'))->save();

            if ($order->getCanSendNewEmailFlag()) {
                try {
                    $order->sendNewOrderEmail();
                } catch(Exception $e) {
                    Mage::logException($e);
                }
            }
        } else $order->setStatus(Mage::getStoreConfig('payment/seqr/canceled_order_status'))->save();

        return $result;
    }

    public function cancelInvoice(Mage_Sales_Model_Order $order) {

        $paymentData = $order->getPayment()->getAdditionalData();
        if (! $paymentData) return null;
        if ($paymentData->status->status === 'PAID') return false;

        $result = Mage::getSingleton('seqr/api')->cancelInvoice(json_decode($paymentData)->invoiceReference);

        $paymentData->status->status = 'CANCELED';

        $this->setAdditionalData($order, $paymentData);

        if ($result && $result->resultCode === 0) return false;
        $order->setStatus(Mage::getStoreConfig('payment/seqr/canceled_order_status'))->save();
        return true;
    }

    private function sendInvoice(Mage_Sales_Model_Order $order) {

        $data = Mage::getSingleton('seqr/api')->sendInvoice($order);
        if (! $data) return null;

        $this->setAdditionalData($order, $data);

        return $data;
    }

    private function getAdditionalData(Mage_Sales_Model_Order $order) {

        return json_decode($order->getPayment()->getAdditionalData());
    }

    private function setAdditionalData(Mage_Sales_Model_Order $order, $data) {

        if (! $data || ! $order) return null;
        $order->getPayment()->setAdditionalData(json_encode($data))->save();

        return $data;
    }
}