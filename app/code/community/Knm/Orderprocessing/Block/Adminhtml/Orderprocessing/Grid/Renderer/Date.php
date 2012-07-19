<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Date extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        
        $message = Mage::getModel('orderprocessing/message')->load($value);
        
        return $message->getCreatedAt();
        
        #return '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$value.'"/>';
    }
}