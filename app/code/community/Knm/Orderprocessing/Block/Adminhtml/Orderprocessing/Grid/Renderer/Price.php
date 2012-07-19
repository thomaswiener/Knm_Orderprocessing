<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Price extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
            return number_format($orderItem->getBasePriceInclTax() * $item->getQuantity(), 2, ',','.') . ' €';
            #$orderItem->getRowTotalInclTax()
        }
        
        if ($item->getMessageType() == 'OrderAdjustment')
        {
            return number_format((-1) * $item->getItemPriceAdjustments(), 2, ',','.') . ' €'; 
        }
        
        return 'error';
        
    }
}