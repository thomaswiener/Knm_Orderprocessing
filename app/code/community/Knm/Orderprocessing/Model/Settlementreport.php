<?php
class Knm_Orderprocessing_Model_SettlementReport extends Mage_Core_Model_Abstract
{
    
    const PARTNER_KLINGEL   = 'Klingel';
    const PARTNER_WENZ      = 'Wenz';
    const PARTNER_DIEMER    = 'Diemer';
    
    private $_aReportsToCreate = array(
            self::PARTNER_KLINGEL => 'KL',
            self::PARTNER_WENZ    => 'WZ',
            self::PARTNER_DIEMER  => 'DI',
    );
    
    private $merchantIdentifier = array(
            self::PARTNER_KLINGEL => 'Klingel_Versand',
            self::PARTNER_WENZ    => 'Wenz_Versand',
            self::PARTNER_DIEMER  => 'Diemer_Versand',
    );
    
    const MARKET_PLACE_NAME = 'faszinata.de';
    
    private $shippingPrice    = 0.0;
    private $refundCommission = 0.0;
    private $shippingHBFee    = 0.0;
    private $commissionFee    = 0.0;
    
    protected $_sNewLine    = "\n";
    protected $_sTab        = '    ';
    protected $_sDelimiter  = ";";
    
    const PAYPAL   = 'paypal_standard';
    const COMPUTOP = 'computop_cc';
    const RATEPAY  = 'ratepay_rechnung';
    const UNKNOWN_PAYMENT_METHOD = 'unknown';
    
    private $aPaymentProviders = array(
        self::COMPUTOP  => 'Computop',
        self::PAYPAL    => 'PayPal',
        self::RATEPAY   => 'Ratepay'
    );
    
    
    
    private $connection = null;
    
    private function _getFilename($sPartner, $dateStart, $dateEnd) {
        return "Settlement_{$this->_aReportsToCreate[$sPartner]}DE_".date('ymd', strtotime($dateStart))."_".date('ymd', strtotime($dateEnd)).".xml";
    }
    
    private function _getMerchantIdentifier($sPartner) {
        switch ($sPartner) {
            case self::PARTNER_KLINGEL:
                $sMerchantIdentifier = '201_Klingel';
            break;
            case self::PARTNER_WENZ:
                $sMerchantIdentifier = '101_Wenz';
            break;
            case self::PARTNER_DIEMER:
                $sMerchantIdentifier = '901_Diemer';
            break;
            default:
                $sMerchantIdentifier = 'Unknown';
            break;
        }
        return $sMerchantIdentifier;
    }
    
    public function _generatePartnerReport($sPartner, $dateStart, $dateEnd) {
        
        $this->connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $sFileName = $this->_getFilename($sPartner, $dateStart, $dateEnd);
        $sFilePath = '/srv/www/faszinata.de/settlement_reports/' . $sFileName;
        if(true || file_exists($sFilePath) === false) {
            $iShopSettlementId = time();
            $iDepositDate = mktime(0,0,0,date("m"),date("d")+2,date("Y"));
    
            $totalAmount = 0;
            $t = $this->merchantIdentifier[$sPartner];
            //get payment
            $messageCollection = Mage::getModel('orderprocessing/message')
            ->getCollection()
            ->addFieldToFilter('merchant_identifier'      , $this->merchantIdentifier[$sPartner])
            ->addFieldToFilter('message_type'             , 'OrderFulfillment')
            #->addFieldToFilter('status_settlement_report' , 'open')
            ->addFieldToFilter('created_at' , array('gteq' => $dateStart))
            ->addFieldToFilter('created_at' , array('lt'   => $dateEnd))
            ;
            
            foreach($messageCollection as $message)
            {
                $itemCollection = Mage::getModel('orderprocessing/item')->getCollection()->addFieldToFilter('knm_message_id', $message->getId());
                foreach($itemCollection as $item)
                {
                    $orderItems = Mage::getModel('sales/order_item')
                    ->getCollection()
                    ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
                    ;
                    $orderItem = $orderItems->getFirstItem();
                    $totalAmount += $item->getQuantity() * $orderItem->getPriceInclTax();
                }
            }
            
            //get adjustments
            $messageCollection = Mage::getModel('orderprocessing/message')
                ->getCollection()
                ->addFieldToFilter('merchant_identifier'      , $this->merchantIdentifier[$sPartner])
                ->addFieldToFilter('message_type'             , 'OrderAdjustment')
                #->addFieldToFilter('status_settlement_report' , 'open')
                ->addFieldToFilter('created_at' , array('gteq' => $dateStart))
                ->addFieldToFilter('created_at' , array('lt'   => $dateEnd))
            ;
            
            foreach($messageCollection as $message)
            {
                $itemCollection = Mage::getModel('orderprocessing/item')->getCollection()->addFieldToFilter('knm_message_id', $message->getId());
                foreach($itemCollection as $item)
                {
                    $orderItems = Mage::getModel('sales/order_item')
                        ->getCollection()
                        ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
                    ;
                    $orderItem = $orderItems->getFirstItem();

                    $orderShipmentItems = Mage::getModel('sales/order_shipment_item')
                    ->getCollection()
                    ->addFieldToFilter('order_item_id', $orderItem->getId() )
                    ;
                    $orderShipmentItem = $orderShipmentItems->getFirstItem();
                      
                    $orderShipments = Mage::getModel('sales/order_shipment')
                        ->getCollection()
                        ->addFieldToFilter('entity_id', $orderShipmentItem->getParentId())
                    ;
                    $orderShipment = $orderShipments->getFirstItem();
                    
                    //if fulfillmentId is empty, shipment has not been created, no need to adjust
                    if (trim($orderShipment->getKmoFulfillmentId()) == '')
                        continue;

                    $totalAmount -= $item->getItemPriceAdjustments();
                }
            }
            
            #if((is_array($aOrders) === true && count($aOrders) > 0) || (is_array($aAdjustments) === true && count($aAdjustments) > 0)) {
                $oXmlHandle = fopen($sFilePath, 'w');
                $this->_writeLine(  '<?xml version="1.0"?>', $oXmlHandle);
                $this->_writeLine(  '<ShopEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:amzn="http://www.Shop.com/XSL/Transform/Extensions" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">', $oXmlHandle);
                $this->_writeLine(      '<Header>', $oXmlHandle, 1);
                $this->_writeLine(          '<DocumentVersion>3.01</DocumentVersion>', $oXmlHandle, 2);
                $this->_writeLine(          '<MerchantIdentifier>'.$this->_getMerchantIdentifier($sPartner).'</MerchantIdentifier>', $oXmlHandle, 2);
                $this->_writeLine(      '</Header>', $oXmlHandle, 1);
                $this->_writeLine(      '<MessageType>SettlementReport</MessageType>', $oXmlHandle, 1);
                $this->_writeLine(      '<Message>', $oXmlHandle, 1);
                $this->_writeLine(          '<MessageID>1</MessageID>', $oXmlHandle, 2);
                $this->_writeLine(          '<SettlementReport>', $oXmlHandle, 2);
                $this->_writeLine(              '<SettlementData>', $oXmlHandle, 3);
                $this->_writeLine(                  '<ShopSettlementID>'.$iShopSettlementId.'</ShopSettlementID>', $oXmlHandle, 4);
                $this->_writeLine(                  '<TotalAmount currency="EUR">'.number_format($totalAmount, 2).'</TotalAmount>', $oXmlHandle, 4);
                $this->_writeLine(                  '<StartDate>'.date('c', strtotime($dateStart)).'</StartDate>', $oXmlHandle, 4);
                $this->_writeLine(                  '<EndDate>'.date('c', strtotime($dateEnd)).'</EndDate>', $oXmlHandle, 4);
                $this->_writeLine(                  '<DepositDate>'.date('c', $iDepositDate).'</DepositDate>', $oXmlHandle, 4);
                $this->_writeLine(              '</SettlementData>', $oXmlHandle, 3);
                
                $messageCollection = Mage::getModel('orderprocessing/message')
                    ->getCollection()
                    ->addFieldToFilter('merchant_identifier'      , $this->merchantIdentifier[$sPartner])
                    ->addFieldToFilter('message_type'             , 'OrderFulfillment')
                    #->addFieldToFilter('status_settlement_report' , 'open')
                    ->addFieldToFilter('created_at' , array('gteq' => $dateStart))
                    ->addFieldToFilter('created_at' , array('lt'   => $dateEnd))
                ;
                
                foreach ($messageCollection as $message) 
                {
                    $incrementId = (int) str_replace('-','',$message->getShopOrderId());
                    
                    $orders = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addFieldToFilter('increment_id', $incrementId )
                    ;
                    $order = $orders->getFirstItem();
                    $payment = $order->getPayment();
                    
                    $sql = 'select * from sales_flat_order_merchant where order_id = ' . $order->getId() . ' and merchant_order_id LIKE \''.$this->_aReportsToCreate[$sPartner] . '%\'';
                    $result = $this->connection->fetchAll($sql);
                    if ($result == array())
                        continue;
                    
                    $orderShipments = Mage::getModel('sales/order_shipment')
                    ->getCollection()
                    ->addFieldToFilter('kmo_fulfillment_id', $message->getMerchantFulfillmentId())
                    ;
                    $orderShipment = $orderShipments->getFirstItem();
                    
                    $this->_writeLine(          '<Order>', $oXmlHandle, 3);
                    $this->_writeLine(              '<ShopOrderID>'.((int) str_replace('-', '', $message->getShopOrderId())).'</ShopOrderID>', $oXmlHandle, 4);
                    $this->_writeLine(              '<MerchantOrderID>'.$result['0']['merchant_order_id'].'</MerchantOrderID>', $oXmlHandle, 4);    #$orderMerchant->getMerchantOrderId()
                    $this->_writeLine(              '<ShipmentID>'.$orderShipment->getIncrementId().'</ShipmentID>', $oXmlHandle, 4);  //$orderShipment->getKmoFulfillmentId()
                    $this->_writeLine(              '<CTRefNr>'.(array_key_exists($payment->getMethod(), $this->aPaymentProviders) ? $this->aPaymentProviders[$payment->getMethod()] : self::UNKNOWN_PAYMENT_METHOD ).'</CTRefNr>', $oXmlHandle, 4);
                    $this->_writeLine(              '<MarketplaceName>'.self::MARKET_PLACE_NAME.'</MarketplaceName>', $oXmlHandle, 4);
                    $this->_writeLine(              '<Fulfillment>', $oXmlHandle, 4);
                    $this->_writeLine(                  '<MerchantFulfillmentID>'.$message->getMerchantFulfillmentId().'</MerchantFulfillmentID>', $oXmlHandle, 5);
                    $this->_writeLine(                  '<PostedDate>'.date('c', strtotime($message->getCreatedAt())).'</PostedDate>', $oXmlHandle, 5);
                    
                    $itemCollection = Mage::getModel('orderprocessing/item')->getCollection()->addFieldToFilter('knm_message_id', $message->getId());
                    
                    foreach ($itemCollection as $item) {
                        
                        $orderItems = Mage::getModel('sales/order_item')
                        ->getCollection()
                        ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
                        ;
                        $orderItem = $orderItems->getFirstItem();
                        
                        $this->_writeLine(          '<Item>', $oXmlHandle, 5);
                        $this->_writeLine(              '<ShopOrderItemCode>'.$orderItem->getId().'</ShopOrderItemCode>', $oXmlHandle, 6);   #00000000009776
                        $this->_writeLine(              '<MerchantOrderItemID>'.$item->getMerchantOrderItemId().'</MerchantOrderItemID>', $oXmlHandle, 6);
                        $this->_writeLine(              '<SKU>'.$orderItem->getSku().'</SKU>', $oXmlHandle, 6); 
                        $this->_writeLine(              '<Quantity>'.number_format($item->getQuantity(), 4).'</Quantity>', $oXmlHandle, 6);
                        $this->_writeLine(              '<ItemPrice>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<Component>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Type>Principal</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                      '<Amount currency="EUR">'.number_format($item->getQuantity() * $orderItem->getPriceInclTax(), 2).'</Amount>', $oXmlHandle, 8);
                        $this->_writeLine(                  '</Component>', $oXmlHandle, 7);
                        $this->_writeLine(                  '<Component>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Type>Principal</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                      '<Shipping currency="EUR">'.number_format($this->shippingPrice, 2).'</Shipping>', $oXmlHandle, 8); 
                        $this->_writeLine(                  '</Component>', $oXmlHandle, 7);
                        $this->_writeLine(              '</ItemPrice>', $oXmlHandle, 6);
                        $this->_writeLine(              '<ItemFees>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Type>ShippingHB</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                      '<Amount currency="EUR">'.number_format($this->shippingHBFee, 2).'</Amount>', $oXmlHandle, 8); 
                        $this->_writeLine(                  '</Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                  '<Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Type>Commission</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                      '<Amount currency="EUR">'.number_format($this->commissionFee, 2).'</Amount>', $oXmlHandle, 8); 
                        $this->_writeLine(                  '</Fee>', $oXmlHandle, 7);
                        $this->_writeLine(              '</ItemFees>', $oXmlHandle, 6);
                        $this->_writeLine(          '</Item>', $oXmlHandle, 5);
                        #$this->updateSrStatus('sr_order', $aOrder, $aItem, $this->_getFilename($sPartner));
                    }
                    
                    $this->_writeLine(              '</Fulfillment>', $oXmlHandle, 4);
                    $this->_writeLine(          '</Order>', $oXmlHandle, 3);
                    
                    $message->logSettlementReportExportStatus($sFileName);
                    
                }
                
                //get all open adjustments by partner
                $messageCollection = Mage::getModel('orderprocessing/message')
                    ->getCollection()
                    ->addFieldToFilter('merchant_identifier'      , $this->merchantIdentifier[$sPartner])
                    ->addFieldToFilter('message_type'             , 'OrderAdjustment')
                    #->addFieldToFilter('status_settlement_report' , 'open')
                    ->addFieldToFilter('created_at' , array('gteq' => $dateStart))
                    ->addFieldToFilter('created_at' , array('lt'   => $dateEnd)) #->getSelect()
                ;
                #die((string) $messageCollection);
                
                foreach ($messageCollection as $message) {
                    
                    $itemCollection = Mage::getModel('orderprocessing/item')->getCollection()->addFieldToFilter('knm_message_id', $message->getId());
                    
                    $incrementId = (int) str_replace('-','',$message->getShopOrderId());
                    
                    $orders = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addFieldToFilter('increment_id', $incrementId )
                    ;
                    $order = $orders->getFirstItem();
                    $payment = $order->getPayment();
                    
                    $sql = 'select * from sales_flat_order_merchant where order_id = ' . $order->getId() . ' and merchant_order_id LIKE \''.$this->_aReportsToCreate[$sPartner] . '%\'';
                    $result = $this->connection->fetchAll($sql);
                    if ($result == array())
                        continue;
                    
                    /*$orderShipments = Mage::getModel('sales/order_shipment')
                        ->getCollection()
                        ->addFieldToFilter('kmo_fulfillment_id', $message->getMerchantFulfillmentId())
                    ;
                    $orderShipment = $orderShipments->getFirstItem();
                    */
                    
                    foreach ($itemCollection as $item) {
                        $orderItems = Mage::getModel('sales/order_item')
                        ->getCollection()
                        ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
                        ;
                        $orderItem = $orderItems->getFirstItem();
                        break;
                    }
                    
                    
                    $orderShipmentItems = Mage::getModel('sales/order_shipment_item')
                    ->getCollection()
                    ->addFieldToFilter('order_item_id', $orderItem->getId() )
                    ;
                    $orderShipmentItem = $orderShipmentItems->getFirstItem();
                      
                    /*$orderShipments = Mage::getModel('sales/order_shipment')
                        ->getCollection()
                        ->addFieldToFilter('entity_id', $orderShipmentItem->getParentId())
                    ;*/
                    
                    $orderShipments = Mage::getModel('sales/order_shipment')
                        ->getCollection()
                        #->addFieldToFilter('entity_id', $orderShipmentItem->getParentId())
                        ->addFieldToFilter('order_id', $order->getId())
                        ->addFieldToFilter('kmo_fulfillment_partner', $this->_aReportsToCreate[$sPartner])
                    ;
                    
                    $orderShipment = $orderShipments->getFirstItem();
                    
                    //if fulfillmentId is empty, shipment has not been created, no need to adjust
                    if (trim($orderShipment->getKmoFulfillmentId()) == '')
                        continue;
                    
                    $creditmemoItem = Mage::getModel('sales/order_creditmemo_item')
                        ->getCollection()
                        ->addFieldToFilter('order_item_id', $orderItem->getId())
                    ;
                    $creditmemoItem = $creditmemoItem->getFirstItem();

                    $creditmemoIncrementId = '000';
                    if ($creditmemoItem)
                    {
                        $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoItem->getParentId());
                        $creditmemoIncrementId = $creditmemo->getIncrementId();
                    }
                    
                    $this->_writeLine(          '<Adjustment>', $oXmlHandle, 3);
                    $this->_writeLine(              '<ShopOrderID>'.((int) str_replace('-', '', $message->getShopOrderId())).'</ShopOrderID>', $oXmlHandle, 4);
                    $this->_writeLine(              '<MerchantOrderID>'.$result['0']['merchant_order_id'].'</MerchantOrderID>', $oXmlHandle, 4);
                    $this->_writeLine(              '<AdjustmentID>'.$creditmemoIncrementId.'</AdjustmentID>', $oXmlHandle, 4);                   # <- $message->creditmemo()
                    $this->_writeLine(              '<CTRefNr>'.(array_key_exists($payment->getMethod(), $this->aPaymentProviders) ? $this->aPaymentProviders[$payment->getMethod()] : self::UNKNOWN_PAYMENT_METHOD ).'</CTRefNr>', $oXmlHandle, 4);
                    $this->_writeLine(              '<MarketplaceName>'.self::MARKET_PLACE_NAME.'</MarketplaceName>', $oXmlHandle, 4);
                    $this->_writeLine(              '<Fulfillment>', $oXmlHandle, 4);
                    $this->_writeLine(                  '<MerchantFulfillmentID>'.$orderShipment->getKmoFulfillmentId().'</MerchantFulfillmentID>', $oXmlHandle, 5); //$message->getMerchantFulfillmentId()
                    $this->_writeLine(                  '<PostedDate>'.date('c', strtotime($message->getCreatedAt())).'</PostedDate>', $oXmlHandle, 5);
                    
                    
                    
                    foreach ($itemCollection as $item) {
                        
                        $orderItems = Mage::getModel('sales/order_item')
                        ->getCollection()
                        ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId() )
                        ;
                        $orderItem = $orderItems->getFirstItem();
                        
                        $this->_writeLine(              '<AdjustedItem>', $oXmlHandle, 5);
                        $this->_writeLine(                  '<ShopOrderItemCode>'.$orderItem->getId().'</ShopOrderItemCode>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<MerchantOrderItemID>'.$item->getMerchantOrderItemId().'</MerchantOrderItemID>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<MerchantAdjustmentItemID>'.$item->getMerchantAdjustmentItemId().'</MerchantAdjustmentItemID>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<SKU>'.$orderItem->getSku().'</SKU>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<ItemPriceAdjustments>', $oXmlHandle, 6);
                        $this->_writeLine(                      '<Component>', $oXmlHandle, 7);
                        $this->_writeLine(                          '<Type>Principal</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                          '<Amount currency="EUR">'.number_format( abs($item->getItemPriceAdjustments()) * (-1), 2).'</Amount>', $oXmlHandle, 8);
                        $this->_writeLine(                      '</Component>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Component>', $oXmlHandle, 7);
                        $this->_writeLine(                          '<Type>Principal</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                          '<Shipping currency="EUR">'.number_format($this->shippingPrice, 2).'</Shipping>', $oXmlHandle, 8);
                        $this->_writeLine(                      '</Component>', $oXmlHandle, 7);
                        $this->_writeLine(                  '</ItemPriceAdjustments>', $oXmlHandle, 6);
                        $this->_writeLine(                  '<ItemFees>', $oXmlHandle, 6);
                        $this->_writeLine(                      '<Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                          '<Type>RefundCommission</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                          '<Amount currency="EUR">'.number_format($this->shippingPrice, 2).'</Amount>', $oXmlHandle, 8);
                        $this->_writeLine(                      '</Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                          '<Type>ShippingHB</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                          '<Amount currency="EUR">'.number_format($this->shippingHBFee, 2).'</Amount>', $oXmlHandle, 8);
                        $this->_writeLine(                      '</Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                      '<Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                          '<Type>Commission</Type>', $oXmlHandle, 8);
                        $this->_writeLine(                          '<Amount currency="EUR">'.number_format($this->commissionFee, 2).'</Amount>', $oXmlHandle, 8);
                        $this->_writeLine(                      '</Fee>', $oXmlHandle, 7);
                        $this->_writeLine(                  '</ItemFees>', $oXmlHandle, 6);
                        $this->_writeLine(              '</AdjustedItem>', $oXmlHandle, 5);
                        
                        #$this->updateSrStatus('sr_adjustment', $aAdjustment, $aItem, $this->_getFilename($sPartner));
                    }
                    $this->_writeLine(              '</Fulfillment>', $oXmlHandle, 4);
                    $this->_writeLine(          '</Adjustment>', $oXmlHandle, 3);
                
                    $message->logSettlementReportExportStatus($sFileName);
                }
                
                // OtherFee ?
                // OtherTransaction ?
                // MiscEvent ?
                $this->_writeLine(          '</SettlementReport>', $oXmlHandle, 2);
                $this->_writeLine(      '</Message>', $oXmlHandle, 1);
                $this->_writeLine(  '</ShopEnvelope>', $oXmlHandle);
                fclose($oXmlHandle);
    
                return $sFilePath;
            #}
        }
        return false;
    }
    
    protected function _writeLine($sString, $oFilehandle, $iTabCount = 0) {
        $sLine = '';
        if($iTabCount > 0) {
            for($i = 0; $i < $iTabCount; $i++) {
                $sLine .= $this->_sTab;
            }
        }
        $sLine .= $sString.$this->_sNewLine;
        fwrite($oFilehandle, $sLine);
    }
}
