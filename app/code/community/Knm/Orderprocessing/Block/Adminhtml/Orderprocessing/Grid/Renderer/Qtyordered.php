<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Qtyordered extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $item = Mage::getModel('orderprocessing/item')->load($value);
        
        if (  $item->getMessageType() == Knm_Orderprocessing_Model_Message::ORDERACKNOWLEDGEMENT
           || $item->getMessageType() == Knm_Orderprocessing_Model_Message::ORDERCANCELLATION)
        return '';
        
        $orderItems = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
        ;
        $orderItem = $orderItems->getFirstItem();
        
        if ($item->getMessageType() == 'OrderFulfillment')
        {
            return number_format($orderItem->getQtyOrdered(), 0);
        }
        
        if ($item->getMessageType() == 'OrderAdjustment')
        {
            if ($orderItem->getBasePriceInclTax() == '' || (double) $orderItem->getBasePriceInclTax() == 0) 
                return 'division by zero'; //division by zero
            
            $returnedQuantity = ((double) $item->getItemPriceAdjustments() / (double) $orderItem->getBasePriceInclTax());
            return number_format($returnedQuantity, 0);
        }
        
        return 'error';
    }
}