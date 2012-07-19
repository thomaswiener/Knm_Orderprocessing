<?php
class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_orderprocessing';
    $this->_blockGroup = 'orderprocessing';
    $this->_headerText = Mage::helper('orderprocessing')->__('Processed status messages');
    #$this->_addButtonLabel = Mage::helper('orderprocessing')->__('Add Item');
    
    parent::__construct();
    $this->removeButton('add');
  }
}