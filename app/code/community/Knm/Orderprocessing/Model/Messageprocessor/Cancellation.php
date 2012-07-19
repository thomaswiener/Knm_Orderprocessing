<?php

class Knm_Orderprocessing_Model_Messageprocessor_Cancellation
    extends Knm_Orderprocessing_Model_Payment_Abstract
        implements Knm_Orderprocessing_Model_Messageprocessor_Interface
{
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Messageprocessor_Interface::handleMessage()
     */
    public function handleMessage(Knm_Orderprocessing_Model_Message $message)
    {
        //get items from message
        $items = $this->_getItemsByMessage($message);
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
        //get all order items
        $orderItems       = $order->getAllItems();
        $invoiceFound     = false;
        $refundedInvoices = array();
    
        foreach ($orderItems as $orderItem) {
            
            $invoice = $this->_getInvoiceByOrderItemId($order, $orderItem->getId());
            
            if($invoice !== false && array_search($invoice->getId(), $refundedInvoices) === false) 
            {
                $invoiceFound = true;
        
                // have items been refunded already
                $creditmemo = $this->_getCreditmemo($order, $orderItem);
                if($creditmemo !== false) {
                    // Throw Error F020
                    throw new Exception($oMessage->asXML(), 1020);
                }
                
                //create creditmemo
                $creditmemo = $this->_refundInvoice($order, $invoice, array(), $message);
        
                // was creditmemo creation successful
                if(!$creditmemo || !$creditmemo->getId()) {
                    // Throw Error F030
                    throw new Exception($oMessage->asXML(), 1030);
                }
        
                $refundedInvoices[] = $invoice->getId();
        
                // E-Mail an Kunde wird verschickt
                $creditmemo->sendEmail();
            
                $orderItem->addData(array(
                    'qty_kmo_canceled' => $orderItem->getData('qty_ordered'),
                    'qty_canceled'     => $orderItem->getData('qty_ordered'),
                ));
                $orderItem->save();
            }
        
            if($invoiceFound === false) {
                $this->_cancelIfNeeded($order, $message);
            }
        }
    }
}