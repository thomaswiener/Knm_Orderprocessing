<?php 
class Knm_Orderprocessing_Model_Test_Xmlfactory
{
    private $order = null;
    private $items = null;
    private $xml   = null;

    private function _init($order)
    {
        $this->order = $order;
        $this->items = $this->order->getAllVisibleItems();
        $this->xml   = Mage::getModel('orderprocessing/test_xml');
    }
    
    private function _createOrderAcknowledgementConfirmXml()
    {
        foreach ($this->items as $item)
        {
            $this->xml->createOrderAcknowledgementConfirmXml($this->order, $item, $partnerId = '1719901');
        }
    }
    
    private function _createOrderAcknowledgementFailureXml()
    {
        foreach ($this->items as $item)
        {
            $this->xml->createOrderAcknowledgementFailureXml($this->order, $partnerId = '1719901');
        }
    }
    
    private function _createOrderFulfillmentXml()
    {
        foreach ($this->items as $item)
        {
            $this->xml->createOrderFulfillmentXml($this->order, $item, $carrierCode = 'DHL', $trackingNumber = '1234556788', $partnerId = '1719901');
        }
    }
    
    private function _createOrderAdjustmentXml()
    {
        foreach ($this->items as $item)
        {
            $this->xml->createOrderAdjustmentXml($this->order, $item, $adjustmentReason = 'CustomerReturn', $partnerId = '1719901');
        }
    }
    
    public function createXml($order)
    {
        $this->_init($order);
        $this->_createOrderAcknowledgementConfirmXml();
        #$this->_createOrderAcknowledgementFailureXml();
        $this->_createOrderFulfillmentXml();
        $this->_createOrderAdjustmentXml();
        #echo "\nall done\n";
    }
    
}

/*
Auf dem Testsystem sollte das immer durchlaufen.
 
Ralf Westermann
Domweg 3
33098 Paderborn
1968-08-03
(05251) 916543

*/
