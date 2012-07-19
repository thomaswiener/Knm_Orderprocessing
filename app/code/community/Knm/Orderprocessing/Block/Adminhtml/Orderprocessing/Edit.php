<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'orderprocessing';
        $this->_controller = 'adminhtml_orderprocessing';
        
        $this->_updateButton('save', 'label', Mage::helper('orderprocessing')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('orderprocessing')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('orderprocessing_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'orderprocessing_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'orderprocessing_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('orderprocessing_data') && Mage::registry('orderprocessing_data')->getId() ) {
            return Mage::helper('orderprocessing')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('orderprocessing_data')->getTitle()));
        } else {
            return Mage::helper('orderprocessing')->__('Add Item');
        }
    }
}