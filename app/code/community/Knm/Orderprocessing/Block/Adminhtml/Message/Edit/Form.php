<?php

class Knm_Orderprocessing_Block_Adminhtml_Message_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        #$form = new Varien_Data_Form();
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );
    
        $this->setForm($form);
        $fieldset = $form->addFieldset('message_form', array('legend'=>Mage::helper('orderprocessing')->__('Message')));
        $fieldset->addField('id', 'hidden', array(
                'name'  => 'id',
            )
        );
        $fieldset->addField('created_at', 'label', array(
            'label'     => Mage::helper('orderprocessing')->__('Created_at'),
            'name'      => 'created_at',
        ));
        $fieldset->addField('message_type', 'label', array(
            'label'     => Mage::helper('orderprocessing')->__('Message type'),
            'name'      => 'message_type',
        ));
        $fieldset->addField('shop_order_id', 'label', array(
            'label'     => Mage::helper('orderprocessing')->__('Order Id'),
            'name'      => 'shop_order_id',
        ));
        $fieldset->addField('processed_at', 'label', array(
            'label'     => Mage::helper('orderprocessing')->__('Processed at'),
            'name'      => 'processed_at',
        ));
        $fieldset->addField('exception_log', 'textarea', array(
            'label'     => Mage::helper('orderprocessing')->__('Exception log'),
            'style'     => 'width: 1000px;',
            'name'      => 'exception_log',
            #'disabled'  => true,
        ));
        $fieldset->addField('status_xml', 'select', array(
            'label'     => Mage::helper('orderprocessing')->__('Status'),
            'name'      => 'status_xml',
            'values'    => array(
                array(
                        'value'     => 'open',
                        'label'     => Mage::helper('orderprocessing')->__('open'),
                ),
                array(
                        'value'     => 'complete',
                        'label'     => Mage::helper('orderprocessing')->__('complete'),
                ),
                array(
                        'value'     => 'error',
                        'label'     => Mage::helper('orderprocessing')->__('error'),
                ),
            ),
            'value'     => 1,
        ));
        
        #$form->setAction($this->getUrl('*/adminhtml_brands/save'));
        #$form->setMethod('post');
        $form->setUseContainer(true);
        #$form->setId('edit_form');

        if($this->getRequest()->getParam('id')) {
            $id = $this->getRequest()->getParam('id');
            $data = Mage::getModel('orderprocessing/message')->load($id);
            $form->setValues($data->getData());
            
        }
        return parent::_prepareForm();
    }
}