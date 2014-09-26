<?php

class Seamless_SEQR_Block_Payment extends Mage_Core_Block_Template {

    protected function getOrderId() {

        return Mage::registry('orderid');
    }

    protected function getSecondsBeforeCancel() {

        return intval(Mage::getStoreConfig('payment/seqr/seconds_before_cancel'));
    }

    protected function getQrCode() {

        $order = Mage::getModel('sales/order')->load(Mage::registry('orderid'));
        return Mage::getSingleton('seqr/invoice')->getInvoice($order)->invoiceQRCode;
    }

    protected function getSeqrUrl() {

        return preg_replace('/^HTTP\:\/\//',
            Mage::getStoreConfig('payment/seqr/debug') ? 'SEQR-DEBUG://' : 'SEQR://',
            $qrcode = $this->getQrCode());
    }
}