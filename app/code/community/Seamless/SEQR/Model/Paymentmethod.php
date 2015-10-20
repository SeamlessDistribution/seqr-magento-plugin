<?php

class Seamless_SEQR_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway                   = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = false;

    protected $_code = 'seqr';

    public function authorize(Varien_Object $payment, $amount)
    {
        $invoice = Mage::getSingleton('seqr/invoice')->getInvoice($payment->getOrder());
        if (! $invoice || ! $invoice->invoiceQRCode) Mage::throwException(Mage::helper('core')->__('Cannot send invoice to SEQR.'));

        return parent::authorize($payment, $amount);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        Mage::getSingleton('seqr/invoice')->refund($payment->getOrder(), $payment->getCreditmemo());
        return parent::refund($payment, $amount);
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('seqr/payment', array('_secure' => true));
    }
}
