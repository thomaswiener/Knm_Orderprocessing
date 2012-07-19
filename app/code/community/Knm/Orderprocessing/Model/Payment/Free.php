<?php

class Knm_Orderprocessing_Model_Payment_Free 
    extends Knm_Orderprocessing_Model_Payment_Abstract 
        implements Knm_Orderprocessing_Model_Payment_Interface
{
    
    protected $paymentName = 'free';
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::deliver()
     */
    public function deliver(Mage_Sales_Model_Order $order, $items = array())
    {
        $this->_createInvoice($order, $items);
    }
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::refund()
     */
    public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $creditmemo = $this->_refund($order, $invoice, $items, $message);
        
        $this->_updateQuantitiesAndAddHistory($order, $message);
    }
    
    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $order->cancel();
    }
    
    /*protected function _updateQuantitiesAndAddHistory(Mage_Sales_Model_Order $order, Knm_Orderprocessing_Model_Message $message)
    {
        $emailComment = null;
    
        $items = $this->_getItemsByMessage($message);
    
        foreach ($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->_getOrderItemByMerchantOrderItemId($item);
            //calculate amount
            $qty = (double)$item->getItemPriceAdjustments() / (double) $orderItem->getBasePriceInclTax();
    
            $fieldToChange = '';
            if($item->getAdjustmentReason() == 'CustomerReturn')
            {
                // Artikel als "Retourniert" markieren
                $fieldToChange = 'qty_kmo_backordered';
            }
            elseif($item->AdjustmentReason == 'CouldNotShip' || $item->AdjustmentReason == 'NoInventory')
            {
                // Artikel als "Nicht ausführbar" markieren
                $fieldToChange = 'qty_kmo_couldnotship';
    
                $emailComment.= ($orderItem->getData('qty_kmo_canceled') + $orderItem->getData('qty_kmo_couldnotship') + $qty).
                "x ".$orderItem->getData('name')." (". $orderItem->getData('sku') .")<br />";
            }
    
            $orderItem->addData(array(
                $fieldToChange => ($qty + $orderItem->getData($fieldToChange)),
            ));
    
            $orderItem->save();
        }
    
        //reload order object, might have changed by now already (ratepay problem with grand total)
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
    
        // send order update email for canceled items
        if(!is_null($emailComment)) {
            $order->addStatusHistoryComment('Teilstornierung der Bestellung durch Partner.<br />'. $emailComment, $order->getStatus())->setIsCustomerNotified(true);
            $order->sendOrderUpdateEmail(true, $emailComment);
        }
    }*/
}