<?php
class Knm_Orderprocessing_Block_Adminhtml_Message extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_message';
        $this->_blockGroup = 'orderprocessing';
        $this->_headerText = Mage::helper('orderprocessing')->__('Processed status messages');
        #$this->_addButtonLabel = Mage::helper('orderprocessing')->__('Add Item');

        parent::__construct();
        $this->removeButton('add');

        $this->_addButton('process', array(
            'label'   => Mage::helper('orderprocessing')->__('Process messages'),
            'onclick'   => 'setLocation(\'' . $this->getProcessUrl() .'\')',
            'class'     => 'process',
        ), -100);
    }

    public function getProcessUrl()
    {
        return $this->getUrl('*/adminhtml_message/processMessages');
    }
}