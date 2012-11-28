<?php

class Knm_Orderprocessing_Model_Messageprocessor_Adjustment
    extends Knm_Orderprocessing_Model_Messageprocessor_Abstract 
        implements Knm_Orderprocessing_Model_Messageprocessor_Interface
{
    public function handleMessage(Knm_Orderprocessing_Model_Message $message)
    {
        //get items from message
        $items = $this->_getItemsByMessage($message);
        //has message items
        if (sizeof($items) == 0)
            throw new Exception($message, 1020);
    
        if (true !== $this->_checkIfAllItemsExist($items))
            throw new Exception($message, 1020);
    
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
    
        $adjustmentItemArrays = $this->_getAdjustmentItemArrays($order, $items);
        
        $reasonInvoiceItems        = $adjustmentItemArrays['reasonInvoiceItems'];
        $invoiceArray              = $adjustmentItemArrays['invoiceArray'];
        $notInvoicedItems          = $adjustmentItemArrays['notInvoicedItems'];
        $orderItemIdToAdjustmentId = $adjustmentItemArrays['orderItemIdToAdjustmentId'];
    
        //load payment model
        $paymentModel = $this->_getPaymentModel($order);
        $payment = Mage::getModel($paymentModel);
        
        // refund of already invoiced items
        foreach ($reasonInvoiceItems as $reason => $invoices) {
            foreach ($invoices as $key => $invoiceItems) {
                $invoice = $invoiceArray[$key];
                //refund
                $payment->refund($order, $invoice, $invoiceItems, $message);
                
            }
        }
        //refund  items not invoiced, optional! mandatory for ratepay
        //ratepay needs to be informed => Refactor please
        if ((array_key_exists('NoInventory', $notInvoicedItems)) 
            && ($notInvoicedItems['NoInventory'] != array())
        ) {
            $payment->cancel($order, $notInvoicedItems, $message);
        }
        
        $this->_cancelIfNeeded($order, $message);
        
    }
    
    private function _getAdjustmentItemArrays(Mage_Sales_Model_Order $order, $items = array())
    {
        $reasonInvoiceItems        = array();
        $invoiceArray              = array();
        $notInvoicedItems          = array();
        $orderItemIdToAdjustmentId = array();
        
        foreach ($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->_getOrderItemByMerchantOrderItemId($item);
            $orderItemIdToAdjustmentId[$orderItem->getId()] = (string) $item->getMerchantAdjustmentItemId();
            if($item->getAdjustmentReason() == 'CustomerReturn') {
                // Artikel wurden bereits versendet? Nicht versendet = Fehler
                if($orderItem->getQtyShipped() == 0) //was QtyKmoShipped
                {
                    // Throw Error F060
                    throw new Exception($message, 1060);
                }
            } elseif($item->getAdjustmentReason() == 'CouldNotShip' || $item->getAdjustmentReason() == 'NoInventory') {
                // Artikel wurden bereits versendet? Versendet = Fehler
                if($orderItem->getQtyKmoShipped() == $orderItem->getQtyOrdered())
                {
                    // Throw Error F020
                    throw new Exception($message, 1020);
                }
            }
        
            //calculate amount
            $qty = (double)$item->getItemPriceAdjustments() / (double) $orderItem->getBasePriceInclTax();
            //get invoice if exsists
            $invoice = $this->_getInvoice($order, $orderItem);
            //if invoice does not exsist
            if($invoice !== false) {
                if(isset($reasonInvoiceItems[(string) $item->getAdjustmentReason()]))
                {
                    if (is_array($reasonInvoiceItems[(string) $item->getAdjustmentReason()]) === false) 
                    {
                        $reasonInvoiceItems[(string) $item->getAdjustmentReason()] = array();
                    }
                }
                if(isset($reasonInvoiceItems[(string) $item->getAdjustmentReason()]))
                {
                    if(is_array($reasonInvoiceItems[(string) $item->getAdjustmentReason()][$invoice->getId()]) === false) {
                        $reasonInvoiceItems[(string) $item->getAdjustmentReason()][$invoice->getId()] = array();
                    }
                }
                $reasonInvoiceItems[(string) $item->getAdjustmentReason()][$invoice->getId()][$orderItem->getId()] = $qty;
                $invoiceArray[$invoice->getId()] = $invoice;
            }
            else
            {
                $notInvoicedItems[(string) $item->getAdjustmentReason()][$orderItem->getId()] = $qty;
            }
        }
        
        return array(
            'reasonInvoiceItems'        => $reasonInvoiceItems,
            'invoiceArray'              => $invoiceArray,
            'notInvoicedItems'          => $notInvoicedItems,
            'orderItemIdToAdjustmentId' => $orderItemIdToAdjustmentId,
        );
    }
}