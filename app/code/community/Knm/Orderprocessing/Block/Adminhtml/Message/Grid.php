<?php

class Knm_Orderprocessing_Block_Adminhtml_Message_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('orderprocessingGrid');
      $this->setDefaultSort('id');
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('orderprocessing/message')
          ->getCollection()
          //->addFieldToFilter()
          //'message_type', array(array('eq' => 'OrderFulfillment'),array('eq' => 'OrderAdjustment')))
      ;
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('id', array(
              'header'    => Mage::helper('orderprocessing')->__('Id'),
              'align'     =>'right',
              'width'     => '50px',
              'index'     => 'id',
      ));
      
      $this->addColumn('created_at', array(
              'header'    => Mage::helper('orderprocessing')->__('Created at'),
              'align'     =>'center',
              'type'      => 'datetime',
              #'width'     => '50px',
              'index'     => 'created_at',
              'width'     => '120px',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_date',
      ));
      
      $this->addColumn('message_type', array(
              'header'    => Mage::helper('orderprocessing')->__('Message Type'),
              'align'     =>'left',
              'width'     => '150px',
              'type'      => 'options',
              'options'   => array(
                      'OrderAcknowledgement' => 'OrderAcknowledgement',
                      'OrderFulfillment'     => 'OrderFulfillment',
                      'OrderAdjustment'      => 'OrderAdjustment',
                      'OrderCancellation' => 'OrderCancellation',
              ),
              'index'     => 'message_type',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_type',
      ));
      
      $this->addColumn('orderid', array(
              'header'    => Mage::helper('orderprocessing')->__('Order Id'),
              'align'     =>'right',
              'width'     => '100px',
              'index'     => 'shop_order_id',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_order',
      ));
      
      $this->addColumn('processed_at', array(
              'header'    => Mage::helper('orderprocessing')->__('Processed at'),
              'align'     =>'center',
              'type'      => 'datetime',
              #'width'     => '50px',
              'index'     => 'processed_at',
              'width'     => '120px',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_date',
      ));
      
      $this->addColumn('status', array(
              'header'    => Mage::helper('orderprocessing')->__('Status'),
              'align'     => 'center',
              'index'     => 'status_xml',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_price',
              'width'     => '120px',
              'type'      => 'options',
              'options'   => array(
                  'open'     => 'open',
                  'complete' => 'complete',
                  'error'    => 'error',
              ),
      ));
      
      $this->addColumn('exception_log', array(
          'header'    => Mage::helper('orderprocessing')->__('Exception log'),
          'align'     => 'left',
          'index'     => 'exception_log',
          'renderer'  => 'orderprocessing/adminhtml_message_grid_renderer_log',
          'filter'    => false,
          #'width'     => '800px',
      ));
      
//       $this->addColumn('action',
//           array(
//               'header'    => Mage::helper('orderprocessing')->__('Action'),
//               'width'     => '50px',
//               'type'      => 'action',
//               'getter'     => 'getId',
//               'actions'   => array(
//                   array(
//                       'caption' => Mage::helper('orderprocessing')->__('View'),
//                       'url'     => array('base'=>'*/orderprocessing/edit'),
//                       'field'   => 'message_id'
//                   )
//               ),
//               'filter'    => false,
//               'sortable'  => false,
//               'index'     => 'stores',
//               'is_system' => true,
//           ));
      
        $this->addExportType('*/*/exportCsv', Mage::helper('orderprocessing')->__('CSV'));
        #$this->addExportType('*/*/exportXml', Mage::helper('orderprocessing')->__('XML'));
      
      return parent::_prepareColumns();
  }

//     protected function _prepareMassaction()
//     {
//         $this->setMassactionIdField('orderprocessing_id');
//         $this->getMassactionBlock()->setFormFieldName('orderprocessing');

//         $this->getMassactionBlock()->addItem('delete', array(
//              'label'    => Mage::helper('orderprocessing')->__('Delete'),
//              'url'      => $this->getUrl('*/*/massDelete'),
//              'confirm'  => Mage::helper('orderprocessing')->__('Are you sure?')
//         ));

//         $statuses = Mage::getSingleton('orderprocessing/status')->getOptionArray();

//         array_unshift($statuses, array('label'=>'', 'value'=>''));
//         $this->getMassactionBlock()->addItem('status', array(
//              'label'=> Mage::helper('orderprocessing')->__('Change status'),
//              'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
//              'additional' => array(
//                     'visibility' => array(
//                          'name' => 'status',
//                          'type' => 'select',
//                          'class' => 'required-entry',
//                          'label' => Mage::helper('orderprocessing')->__('Status'),
//                          'values' => $statuses
//                      )
//              )
//         ));
//         return $this;
//     }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }
  
  public function getPartners()
  {
      $partners = Mage::getModel('brands/brands')->getCollection();
      $partnerArray = array();
      foreach ($partners as $partner)
      {
          $partnerArray[$partner->getName()] = $partner->getName();
      }
      return $partnerArray;
  }

}