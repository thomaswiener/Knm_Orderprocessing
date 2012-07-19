<?php
class Knm_Orderprocessing_Block_Message extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getMessage()     
     { 
        if (!$this->hasData('message')) {
            $this->setData('message', Mage::registry('message'));
        }
        return $this->getData('message');
        
    }
}