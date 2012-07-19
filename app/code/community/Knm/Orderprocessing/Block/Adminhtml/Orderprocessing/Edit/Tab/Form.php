<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('orderprocessing_form', array('legend'=>Mage::helper('orderprocessing')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('orderprocessing')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('orderprocessing')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('orderprocessing')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('orderprocessing')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('orderprocessing')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('orderprocessing')->__('Content'),
          'title'     => Mage::helper('orderprocessing')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getOrderprocessingData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getOrderprocessingData());
          Mage::getSingleton('adminhtml/session')->setOrderprocessingData(null);
      } elseif ( Mage::registry('orderprocessing_data') ) {
          $form->setValues(Mage::registry('orderprocessing_data')->getData());
      }
      return parent::_prepareForm();
  }
}