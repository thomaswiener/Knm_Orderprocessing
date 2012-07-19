<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Sentreturned extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $item = Mage::getModel('orderprocessing/item')->load($value);
        
        $orderItems = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
        ;
        $orderItem = $orderItems->getFirstItem();
        
        if ($item->getMessageType() == Knm_Orderprocessing_Model_Message::ORDERFULFILLMENT)
        {
            $returnValue = $orderItem->getQtyShipped();
            return ($returnValue != null ? $returnValue : 0);
        }
         
        if ($item->getMessageType() == Knm_Orderprocessing_Model_Message::ORDERADJUSTMENT)
        {
            $returnValue = ($orderItem->getQtyRefunded() == 0.000 ? $orderItem->getQtyCanceled() : $orderItem->getQtyRefunded());
            return ($returnValue != '' ? $returnValue : 0);
        }
        
        return -1;
    }
}