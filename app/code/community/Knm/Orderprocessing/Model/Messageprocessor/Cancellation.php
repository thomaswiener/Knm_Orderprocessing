<?php

class Knm_Orderprocessing_Model_Messageprocessor_Cancellation
    extends Knm_Orderprocessing_Model_Messageprocessor_Abstract
        implements Knm_Orderprocessing_Model_Messageprocessor_Interface
{
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Messageprocessor_Interface::handleMessage()
     */
    public function handleMessage(Knm_Orderprocessing_Model_Message $message)
    {
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
        //get invoices
        $invoices = $order->getInvoiceCollection();
        //load payment model
        $paymentModel = $this->_getPaymentModel($order);
        $payment = Mage::getModel($paymentModel);
        
        foreach ($invoices as $invoice)
        {
            $array = array();
            $invoiceItems = $invoice->getItems();
            foreach($invoiceItems as $invoiceItem)
            {
                $array[$invoiceItem->getOrderItemId()] = $invoiceItem->getQty();
            }
            $payment->refund($order, $invoice, $array, $message);
        }
    }
}