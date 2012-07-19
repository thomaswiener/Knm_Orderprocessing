<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Sku extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        
        $orderItems = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('kmo_merchant_order_item_id', $value )
        ;
        $orderItem = $orderItems->getFirstItem();
        
        return $orderItem->getSku();
    }
}