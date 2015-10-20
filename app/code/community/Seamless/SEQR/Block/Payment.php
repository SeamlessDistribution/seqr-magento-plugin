<?php

class Seamless_SEQR_Block_Payment extends Mage_Core_Block_Template
{
    private $data = null;

    protected function getOrderId()
    {
        return Mage::registry('orderid');
    }

    protected function getSecondsBeforeCancel()
    {
        return intval(Mage::getStoreConfig('payment/seqr/seconds_before_cancel'));
    }

    protected function getQrCode()
    {
        if (! $this->data) {
            $order = Mage::getModel('sales/order')->load(Mage::registry('orderid'));
            $this->data = Mage::getSingleton('seqr/invoice')->getAdditionalData($order);
        }

        if (!$this->data || !$this->data->invoiceQRCode) return null;
        return $this->data->invoiceQRCode;
    }

    protected function getSeqrUrl()
    {
        return preg_replace('/^HTTP\:\/\//',
            Mage::getStoreConfig('payment/seqr/debug') ? 'SEQR-DEBUG://' : 'SEQR://',
            $qrcode = $this->getQrCode());
    }

    protected function getWebPluginUrl()
    {
        return 'https://cdn.seqr.com/webshop-plugin/js/seqrShop.js'.
        '#!'.(Mage::getStoreConfig('payment/seqr/debug') ? '' : 'mode=demo').'&injectCSS=true&statusCallback=seqrStatusUpdated&'.
        'invoiceQRCode='.$this->getQrCode().'&'.
        'statusURL='.Mage::getBaseUrl().'/seqr/payment/check';
    }

    protected function getCancelUrl()
    {
        return Mage::getBaseUrl().'seqr/payment/cancel';
    }
}