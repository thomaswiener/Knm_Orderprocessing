<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Order extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $message = Mage::getModel('orderprocessing/message')->load($value);
        $orderId = str_replace('-', '', $message->getShopOrderId());
        return (int) $orderId;
    }
}