<?php

class Seamless_SEQR_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'seqr';

    public function getOrderPlaceRedirectUrl() {

        return Mage::getUrl('seqr/payment', array('_secure' => true));
    }
}
