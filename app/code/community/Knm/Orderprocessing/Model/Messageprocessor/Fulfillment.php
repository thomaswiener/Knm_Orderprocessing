<?php

class Knm_Orderprocessing_Model_Messageprocessor_Fulfillment
    extends Knm_Orderprocessing_Model_Messageprocessor_Abstract
        implements Knm_Orderprocessing_Model_Messageprocessor_Interface
{
    public function handleMessage(Knm_Orderprocessing_Model_Message $message)
    {
        //get items from message
        $items = $this->_getItemsByMessage($message);
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
    
        $fulfillmentItems = $this->_getFulfillmentItems($order, $items, $message);
        
        $shipmentItems  = $fulfillmentItems['shipmentItems'];
        $invoiceItems   = $fulfillmentItems['invoiceItems'];
        $noInvoiceFound = $fulfillmentItems['noInvoiceFound'];
    
        //check if items have valid quantity
        if ($this->_hasValidItemQuantity($invoiceItems) === false)
        {
            //quantity of items is null, log information and return
            $message->setExceptionLog(Knm_Orderprocessing_Model_Abstract::WARNING_LOG_PREFIX . ': Given invoice item array has a sum of 0 (zero). NO shipment and NO invoice was created. Aborting.');
            $message->save();
            return;
        }
        
        //create invoice
        
        #if($noInvoiceFound === true) {
        if ($this->_hasOrderInvoice($order) === false) 
        {
            //find payment model
            $paymentModel = $this->_getPaymentModel($order);
            if ($paymentModel === false)
            {
                //model not found
                throw new Exception('payment model not found'); #$oMessage, 1040);
            }
            //load model
            $payment = Mage::getModel($paymentModel);
            //inform payment provider about delivery
            $payment->deliver($order, $invoiceItems = array());
            //send invoice email to customer
            #$this->_sendInvoiceEmail($invoice, $order);
        }
        //reload order object, might have changed by now already
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
        
        //after invoice creation create shipment
        //create shipment
        $shipment = $this->_createShipment($message, $order, $shipmentItems);
        //update shipped quantities
        $this->_updateShippedQuantities($items);
    
        $this->_cancelIfNeeded($order, $message);
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $items
     * @param Knm_Orderprocessing_Model_Message $message
     * @throws Exception
     * @return multitype:boolean multitype:number
     */
    private function _getFulfillmentItems(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $shipmentItems  = array();
        $invoiceItems   = array();
        $noInvoiceFound = false;
        
        //loop through items
        foreach($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->_getOrderItemByMerchantOrderItemId($item);
            //check if creditmemo exsists
            $this->_hasOrderItemCreditmemo($order, $orderItem);
        
        
            $remainingQty = (int) ($orderItem->getQtyOrdered() - $orderItem->getQtyShipped());
            if((int) $item->getQuantity() <= $remainingQty )
            {
                $shipmentItems[$orderItem->getId()] = (int) $item->getQuantity();
                $invoice     = $this->_getInvoiceByOrderItemId($order, $orderItem->getId());
                $invoiceItem = $this->_getInvoiceItemByOrderItemId($order, $orderItem->getId());
        
                //error when invoice for single item was created, part shipment, all other invoices will fail
                #if($invoice === false) {
                $noInvoiceFound = true;
                $invoiceItems[$orderItem->getId()] = (int) $item->getQuantity();
                #}
            } else {
                echo "No items for shipping left\n";
                // Throw Error F040
                #echo $this->oColor->prstr("No items for shipping left.", 'ERROR') . "\n";
                throw new Exception($message);
            }
        }
        
        return array(
            'shipmentItems'  => $shipmentItems,
            'invoiceItems'   => $invoiceItems,
            'noInvoiceFound' => $noInvoiceFound,
        );
    }
    
    private function _updateShippedQuantities($items = array())
    {
        //loop through items, save shipped quantity
        foreach($items as $item) //TODO really all items?
        {
            $orderItem = $this->_getOrderItemByMerchantOrderItemId($item);
            $orderItem->addData(array(
                    'qty_kmo_shipped' => $orderItem->getQtyKmoShipped() + (int) $item->Quantity,
            ));
            $orderItem->save();
        }
    }
    
    private function _hasOrderInvoice(Mage_Sales_Model_Order $order)
    {
        $invoices = $order->getInvoiceCollection();
        if (sizeof($invoices) > 0)
            return true;
        
        return false;
    }
}