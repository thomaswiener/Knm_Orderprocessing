<?php

class Knm_Orderprocessing_Block_Adminhtml_Orderprocessing_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
      $collection = Mage::getModel('orderprocessing/item')
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
      
      $this->addColumn('orderid', array(
              'header'    => Mage::helper('orderprocessing')->__('Order Id'),
              'align'     =>'right',
              'width'     => '100px',
              'index'     => 'shop_order_id',
              #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_order',
      ));
      
      $this->addColumn('created_at', array(
          'header'    => Mage::helper('orderprocessing')->__('Date'),
          'align'     =>'center',
          'type'      => 'datetime',
          #'width'     => '50px',
          'index'     => 'created_at',
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
      
      $this->addColumn('partner', array(
          'header'    => Mage::helper('orderprocessing')->__('Partner'),
          'align'     =>'left',
          'index'     => 'merchant_identifier',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_partner',
      ));

      $this->addColumn('sku', array(
          'header'    => Mage::helper('orderprocessing')->__('SKU'),
          'align'     =>'left',
          'index'     => 'merchant_order_item_id',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_sku',
          'filter'    => false,
      ));
      
      $this->addColumn('name', array(
          'header'    => Mage::helper('orderprocessing')->__('Name'),
          'align'     =>'left',
          'index'     => 'merchant_order_item_id',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_name',
          'filter'    => false,
      ));
      
      $this->addColumn('payment', array(
          'header'    => Mage::helper('orderprocessing')->__('Payment'),
          'align'     =>'center',
          'index'     => 'id',
          'width'     => '150px',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_payment',
          'filter'    => false,
      ));
      
      $this->addColumn('qty_ordered', array(
          'header'    => Mage::helper('orderprocessing')->__('Qty Fulfilled/Returned'),
          'align'     =>'center',
          'index'     => 'id',
          'width'     => '150px',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_qtyordered',
          'filter'    => false,
      ));
      
      $this->addColumn('price', array(
          'header'    => Mage::helper('orderprocessing')->__('Value'),
          'align'     =>'right',
          'index'     => 'id',
          'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_price',
          'filter'    => false,
      ));
      /*
      $this->addColumn('qty_adjusted', array(
          'header'    => Mage::helper('orderprocessing')->__('Qty Fulfilled'),
          'align'     =>'right',
          'index'     => 'quantity',
          #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_qtyadjusted',
          'filter'    => false,
      ));
      */
      $this->addColumn('adjustment_reason', array(
          'header'    => Mage::helper('orderprocessing')->__('Adjustment Reason'),
          'align'     =>'right',
          'index'     => 'adjustment_reason',
          'type'      => 'options',
          'options'   => array(
               'CustomerReturn' => 'CustomerReturn',
               'NoInventory'    => 'NoInventory',
               'CouldNotShip'   => 'CouldNotShip'
           ),
          'align'     =>'center',
          #'filter'    => true,
          #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_qtyadjusted',
      ));
      
      $this->addColumn('status', array(
          'header'    => Mage::helper('orderprocessing')->__('Status'),
          'align'     => 'right',
          'index'     => 'status_xml',
          #'renderer'  => 'orderprocessing/adminhtml_orderprocessing_grid_renderer_price',
          'filter'    => false,
      ));
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
      return; # $this->getUrl('*/*/edit', array('id' => $row->getId()));
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