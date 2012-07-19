<?php

class Knm_Orderprocessing_Block_Adminhtml_Message_Grid_Renderer_Log extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    private $maxLength = 150;
    
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        
        if (strlen($value) < $this->maxLength)
        return $value;
        
        return substr($value, 0, $this->maxLength) . '...';
    }
}