<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid_Renderer_Partner extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        
        if ($value == '1719901')
            return 'HEGA'; 
        
        return '';

        /*
        $partners = Mage::getModel('brands/brands')
            ->getCollection()
            ->addFieldToFilter('client_id', $value )
            #->getSelect()
        ;
        #return (string) $partners;
        //if no partner found get original value from message table
        if (sizeof($partners) == 0)
            return $value;
        
        $partner = $partners->getFirstItem();
        
        //return partner name from brands table
        if ($partner)
            return $partner->getName() . " (" . $partner->getClientId() . ")";
        */
    }
}