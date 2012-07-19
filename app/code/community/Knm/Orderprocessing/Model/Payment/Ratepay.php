<?php

class Knm_Orderprocessing_Model_Payment_Ratepay 
    extends Knm_Orderprocessing_Model_Payment_Abstract 
        implements Knm_Orderprocessing_Model_Payment_Interface
{
    
    protected $paymentName = 'ratepay_rechnung';
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::deliver()
     */
    public function deliver(Mage_Sales_Model_Order $order, $items = array())
    {
        $requester = Mage::getModel('ratepayrequester/requester');
        $requester->setOrderId($order->getId());
        $requester->delivery($items);
    }
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::refund()
     */
    public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $requester = Mage::getModel('ratepayrequester/requester');
        $requester->setOrderId($order->getId());
        $requester->customerReturn($items);
        $this->_updateQuantitiesAndAddHistory($order, $message);
    }
    
    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        foreach($notInvoicedItems as $reason => $items) 
        {
            if($reason != 'CouldNotShip' && $reason != 'NoInventory') continue;
            
            $requester = Mage::getModel('ratepayrequester/requester');
            $requester->setOrderId($order->getId());
            $requester->cancel($items);
        }
    }
    
    protected function _setOrderItemQty(Mage_Sales_Model_Order_Item $orderItem, $fieldToChange, $qty)
    {
        $orderItem->addData(array(
            $fieldToChange => ($qty + $orderItem->getData($fieldToChange)),
            #'qty_refunded'  => ($qty + $orderItem->getData('qty_refunded')) //ratepay handles qty_refund by its own
        ));
        
        $orderItem->save();
    }
    
}