<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Type extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        
        $color = 'black';
        if ($value == 'OrderFulfillment')
            $color = 'green';
        
        if ($value == 'OrderAdjustment')
            $color = 'red';
        
        return "<b><span style='color: " . $color . "'>".$value."</span></b>";
    }
}