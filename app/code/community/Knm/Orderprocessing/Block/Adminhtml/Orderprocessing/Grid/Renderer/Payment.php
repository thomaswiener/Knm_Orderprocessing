<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Payment extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
        
        $order   = Mage::getModel('sales/order')->load($orderItem->getOrderId());
        $payment = $order->getPayment();
        if (!$payment)
            return;
        
        return $payment->getMethod();
    }
}