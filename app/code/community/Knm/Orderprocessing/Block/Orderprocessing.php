<?php
class Knm_Orderprocessing_Block_Orderprocessing extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getOrderprocessing()     
     { 
        if (!$this->hasData('orderprocessing')) {
            $this->setData('orderprocessing', Mage::registry('orderprocessing'));
        }
        return $this->getData('orderprocessing');
        
    }
}