<?php

/**
 * Requests to SEQR API
 */
class Seamless_SEQR_Model_Api
{
    /**
     * Sends an invoice to SEQR server
     *
     * @param Mage_Sales_Model_Order $order
     * @return null
     * @throws
     */
    public function sendInvoice(Mage_Sales_Model_Order $order)
    {
        $SOAP = $this->SOAP();
        $result = $SOAP->sendInvoice(array(
            'context' => $this->getRequestContext($order->getIncrementId()),
            'invoice' => $this->getInvoiceRequest($order)
        ))->return;

        if ($result->resultCode != 0) throw new Exception($result->resultCode . ' : ' . $result->resultDescription);

        return $result;
    }

    /**
     * Obtains status of a previously submitted invoice
     *
     * @param string $reference Reference to SEQR invoice
     * @param integer $version Version of invoice (nullable)
     * @return null
     */
    public function getPaymentStatus(Mage_Sales_Model_Order $order, $reference, $version)
    {
        try
        {
            $SOAP = $this->SOAP();
            $result = $SOAP->getPaymentStatus(array(
                "context" => $this->getRequestContext($order->getIncrementId()),
                "invoiceReference" => $reference,
                "invoiceVersion" => $version ? $version : 0
            ))->return;

            if ($result->resultCode != 0) throw new Exception($result->resultCode . ' : ' . $result->resultDescription);

            return $result;
        }
        catch(Exception $e)
        {
            Mage::log('Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancels an unpaid invoice. Can be triggered after defined timeout
     *
     * @param Mage_Sales_Model_Order order
     * @param string $reference SEQR invoice reference
     * @return null
     */
    public function cancelInvoice(Mage_Sales_Model_Order $order, $reference)
    {
        if (! $reference) return null;

        try
        {
            $SOAP = $this->SOAP();
            $result = $SOAP->cancelInvoice(array(
                "context" => $this->getRequestContext($order->getIncrementId()),
                "invoiceReference" => $reference
            ))->return;

            if ($result->resultCode != 0) throw new Exception($result->resultCode . ' : ' . $result->resultDescription);

            return $result;
        }
        catch(Exception $e)
        {
            Mage::log('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function refundPayment(Mage_Sales_Model_Order $order, $creditMemo, $ersReference)
    {
        if (! $ersReference) return null;

        $helper = Mage::helper('seqr/data');

        $unitType = Mage::getStoreConfig('payment/seqr/unit_type');
        $currencyCode = $creditMemo->getOrderCurrencyCode();

        $invoiceRows = array();

        foreach ($creditMemo->getItemsCollection() as $item) {
            if (! $item->getQty()) continue;

            $invoiceRows[] = array(
                'itemDescription' => $item->getName(),
                'itemSKU' => $item->getSku(),
                'itemTaxRate' => $helper->toFloat(0),
                'itemUnit' => $unitType,
                'itemQuantity' => $helper->toFloat($item->getQty()),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($item->getPriceInclTax())
                ),
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($item->getRowTotalInclTax())
                )
            );
        }

        if ($creditMemo->getShippingAmount()) {
            $invoiceRows[] = array(
                'itemDescription' => $helper->__('Shipping & Handling'),
                'itemTaxRate' => $helper->toFloat(0),
                'itemUnit' => $unitType,
                'itemQuantity' => $helper->toFloat(1),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($creditMemo->getShippingAmount())
                ),
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($creditMemo->getShippingAmount())
                )
            );
        }

        if ($creditMemo->getAdjustmentNegative()) {
            $invoiceRows[] = array(
                'itemDescription' => $helper->__('Adjustment Fee'),
                'itemTaxRate' => $helper->toFloat(0),
                'itemUnit' => $unitType,
                'itemQuantity' => $helper->toFloat(1),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat(-$creditMemo->getAdjustmentNegative())
                ),
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat(-$creditMemo->getAdjustmentNegative())
                )
            );
        }

        if ($creditMemo->getAdjustmentPositive()) {
            $invoiceRows[] = array(
                'itemDescription' => $helper->__('Adjustment Refund'),
                'itemTaxRate' => $helper->toFloat(0),
                'itemUnit' => $unitType,
                'itemQuantity' => $helper->toFloat(1),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($creditMemo->getAdjustmentPositive())
                ),
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($creditMemo->getAdjustmentPositive())
                )
            );
        }

        $SOAP = $this->SOAP();
        $result = $SOAP->refundPayment(array(
            "context" => $this->getRequestContext($order->getIncrementId()),
            "ersReference" => $ersReference,
            "invoice" => array(
                "paymentMode" => "IMMEDIATE_DEBIT",
                "acknowledgmentMode" => "NO_ACKNOWLEDGMENT",
                "cashierId" => "MainWeb",
                "issueDate" => date('Y-m-d\Th:i:s'),
                "title" => Mage::getStoreConfig('payment/seqr/title') . " - REFUND",
                "clientInvoiceId" => $creditMemo->getInvoiceId(),
                "invoiceRows" => $invoiceRows,
                "totalAmount" => array(
                    "currency" => $currencyCode,
                    "value" => $helper->toFloat($creditMemo->getGrandTotal())
                )
            )
        ))->return;

        if ($result->resultCode != 0) throw new Exception($result->resultCode . ' : ' . $result->resultDescription);
        return $result;
    }

    /**
     * Get SOAP API client
     *
     * @return SoapClient SOAP client of SEQR API or false if WSDL is unavailable.
     */
    private function SOAP()
    {
        return new SoapClient(Mage::getStoreConfig('payment/seqr/soap_url'), array( 'trace' => 1, 'connection_timeout' => 3000 ));
    }

    /**
     * Get common request part containing context object required for all request.
     *
     * @return array Context request based on Terminal ID
     */
    private function getRequestContext($orderId)
    {
        return array(
            'channel' => 'MagentoWS',

            'initiatorPrincipalId' => array(
                'id' => Mage::getStoreConfig('payment/seqr/terminal_id'),
                'type' => 'TERMINALID',
                'userId' => Mage::getStoreConfig('payment/seqr/user_id')
            ),

            'clientId' => Mage::getConfig()->getNode()->modules->Seamless_SEQR->version,
            'clientReference' => $orderId,
            'clientComment' => Mage::getStoreConfig('payment/seqr/title'),

            'password' => Mage::getStoreConfig('payment/seqr/terminal_password'),
            'clientRequestTimeout' => '0'
        );
    }

    /**
     * Get request representation of Magento Order.
     *
     * @param Mage_Sales_Model_Order $order Order object
     * @return array Order representation used by SEQR
     */
    private function getInvoiceRequest(Mage_Sales_Model_Order $order)
    {
        $helper = Mage::helper('seqr/data');

        $currencyCode = $order->getOrderCurrencyCode();
        $unitType = Mage::getStoreConfig('payment/seqr/unit_type');

        // Prepare main part of request data (ex Shipping and Discounts)
        $invoice = array(
            'paymentMode' => 'IMMEDIATE_DEBIT',
            'acknowledgmentMode' => 'NO_ACKNOWLEDGMENT',

            'issueDate' => date('Y-m-d\Th:i:s'),
            'title' => Mage::getStoreConfig('payment/seqr/title'),
            'clientInvoiceId' => $order->getIncrementId(),

            'invoiceRows' => array_map(function($item) use ($unitType, $currencyCode, $helper) {
                return array(
                    'itemDescription' => $item->getName(),
                    'itemSKU' => $item->getSku(),
                    'itemTaxRate' => $item->getTaxPercent(),
                    'itemUnit' => $unitType,
                    'itemQuantity' => $item->getQtyOrdered(),
                    'itemUnitPrice' => array(
                        'currency' => $currencyCode,
                        'value' => $helper->toFloat($item->getPriceInclTax())
                    ),
                    'itemTotalAmount' => array(
                        'currency' => $currencyCode,
                        'value' => $helper->toFloat($item->getRowTotalInclTax())
                    )
                );
            }, $order->getAllVisibleItems()),

            'totalAmount' => array(
                'currency' => $currencyCode,
                'value' => $helper->toFloat($order->getGrandTotal())
            ),

            'backURL' => Mage::getUrl("seqr/payment/submit", array( 'id' => $order->getId() )),
            'notificationUrl' => Mage::getUrl("seqr/payment/check", array( 'id' => $order->getId(), '_secure'=>true ))
        );

        // Shipping & Handling
        if ($order->getShippingInclTax() && intval($order->getShippingInclTax()))
        {
            $invoice['invoiceRows'][] = array(
                'itemDescription' => $helper->__('Shipping & Handling'),
                'itemQuantity' => 1,
                'itemTaxRate' => ($order->getShippingTaxAmount() - 1) * 100,
                'itemUnit' => '',
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getShippingInclTax())
                ),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getShippingInclTax())
                )
            );
        }

        // Discount
        if ($order->getDiscountAmount() && intval($order->getDiscountAmount()))
        {
            $invoice['invoiceRows'][] = array(
                'itemDescription' => $helper->__('Discount'),
                'itemQuantity' => 1,
                'itemTaxRate' => ($order->getShippingTaxAmount() - 1) * 100,
                'itemUnit' => '',
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getDiscountAmount())
                ),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getDiscountAmount())
                )
            );
        }

        // Shipping discount
        if ($order->getShippingDiscountAmount() && intval($order->getShippingDiscountAmount()))
        {
            $invoice['invoiceRows'][] = array(
                'itemDescription' => $helper->__('Shipping Discount'),
                'itemQuantity' => 1,
                'itemTaxRate' => ($order->getShippingTaxAmount() - 1) * 100,
                'itemUnit' => '',
                'itemTotalAmount' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getShippingDiscountAmount())
                ),
                'itemUnitPrice' => array(
                    'currency' => $currencyCode,
                    'value' => $helper->toFloat($order->getShippingDiscountAmount())
                )
            );
        }

        return $invoice;
    }
}
