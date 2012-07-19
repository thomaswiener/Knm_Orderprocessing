<?php

class Knm_Orderprocessing_Block_Adminhtml_Message_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'orderprocessing';
        $this->_controller = 'adminhtml_message';
        $this->_updateButton('save', 'label', Mage::helper('orderprocessing')->__('Save message'));
    }

    public function getHeaderText()
    {
        if( Mage::registry('message_data') && Mage::registry('message_data')->getId() ) {
            return Mage::helper('orderprocessing')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('message_data')->getTitle()));
        } else {
            return Mage::helper('orderprocessing')->__('Add Item');
        }
    }
}