<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('orderprocessing_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('orderprocessing')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('orderprocessing')->__('Item Information'),
          'title'     => Mage::helper('orderprocessing')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('orderprocessing/adminhtml_orderprocessing_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}