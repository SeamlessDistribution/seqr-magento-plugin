<?php

class Seamless_SEQR_Model_Observer {

    public function cancelOldOrders() {
        $secondsBeforeCancel = intval(Mage::getStoreConfig('payment/seqr/seconds_before_cancel'));

        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->join(
                array('p' => 'sales/order_payment'),
                'p.parent_id = main_table.entity_id',
                array('payment_method' => 'p.method')
            )
            ->addFieldToFilter('status', Mage::getStoreConfig('payment/seqr/order_status'))
            ->addFieldToFilter('p.method', 'seqr')
            ->addFieldToFilter('created_at',
                array('lt' => new Zend_Db_Expr("DATE_ADD('".now()."', INTERVAL -$secondsBeforeCancel SECOND)")));

        foreach ($orders as $order) Mage::getSingleton('seqr/invoice')->cancelInvoice($order);
    }
}