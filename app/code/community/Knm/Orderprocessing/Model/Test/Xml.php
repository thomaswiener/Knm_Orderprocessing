<?php
class Knm_Orderprocessing_Model_Test_Xml
    extends Knm_Orderprocessing_Model_Abstract
{
    public function createOrderAcknowledgementConfirmXml($order, $item, $partnerId = '000')
    {
        #for ($i = 0; $i < $item->getQtyOrdered() ; $i++ )
        #{
            $xml  = trim('<?xml version="1.0" encoding="UTF-8" ?>');
            $xml .= trim('<ShopEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="Shop-envelope.xsd">');
            $xml .= trim('    <Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>'.$partnerId.'</MerchantIdentifier></Header>');
            $xml .= trim('    <MessageType>OrderAcknowledgement</MessageType><EffectiveDate>2011-05-17T14:07:22</EffectiveDate>');
            $xml .= trim('    <Message>');
            $xml .= trim('        <MessageID>1</MessageID>');
            $xml .= trim('        <OperationType>Update</OperationType>');
            $xml .= trim('        <OrderAcknowledgement>');
            $xml .= trim('            <ShopOrderID>'.$this->_createXmlOrderId($order->getIncrementId()).'</ShopOrderID>');
            $xml .= trim('            <MerchantOrderID>'.$order->getIncrementId().'_'.$partnerId.'</MerchantOrderID>');
            $xml .= trim('            <StatusCode>Success</StatusCode>');
            $xml .= trim('            <Item>');
            $xml .= trim('                <ShopOrderItemCode>'.$item->getId().'</ShopOrderItemCode>');
            $xml .= trim('                <MerchantOrderItemID>'.$order->getIncrementId().'-'.$item->getId().'-'.$partnerId.'</MerchantOrderItemID>');
            $xml .= trim('            </Item>');
            $xml .= trim('        </OrderAcknowledgement>');
            $xml .= trim('    </Message>');
            $xml .= trim('</ShopEnvelope>');

            $filename = 'Euro_Best_'.$partnerId.'-D_'.date('Ymd').time().'.xml';
            $this->_writeXml($filename, $xml);
            sleep(1);
        #}
    }
    
    public function createOrderAcknowledgementFailureXml($order, $partnerId = '000')
    {
        $xml  = trim('<?xml version="1.0" encoding="UTF-8" ?>');
        $xml .= trim('<ShopEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="Shop-envelope.xsd">');
        $xml .= trim('    <Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>'.$partnerId.'</MerchantIdentifier></Header>');
        $xml .= trim('    <MessageType>OrderAcknowledgement</MessageType><EffectiveDate>2011-02-14T17:06:53</EffectiveDate>');
        $xml .= trim('    <Message>');
        $xml .= trim('        <MessageID>1</MessageID>');
        $xml .= trim('        <OperationType>Update</OperationType>');
        $xml .= trim('        <OrderAcknowledgement>');
        $xml .= trim('            <ShopOrderID>'.$this->_createXmlOrderId($order->getIncrementId()).'</ShopOrderID>');
        $xml .= trim('            <StatusCode>Failure</StatusCode>');
        $xml .= trim('        </OrderAcknowledgement>');
        $xml .= trim('    </Message>');
        $xml .= trim('</ShopEnvelope>');
        
        #$filename = 'Euro_Stor_'.$order['Order']['orderId'].'_'.$partnerId.'_'.time().'_'.date('Ymd').'.xml';
        $filename = 'Euro_Stor_'.$partnerId.'-D_'.date('Ymd').time().'.xml';
        $this->_writeXml($filename, $xml);
    }
    
    public function createOrderFulfillmentXml($order, $item, $carrierCode, $trackingNumber, $partnerId)
    {
        for ($i = 0; $i < $item->getQtyOrdered() ; $i++ )
        {
            $xml  = trim('<?xml version="1.0" encoding="UTF-8" ?>');
            $xml .= trim('<ShopEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="Shop-envelope.xsd">');
            $xml .= trim('    <Header>');
            $xml .= trim('        <DocumentVersion>1.01</DocumentVersion>');
            $xml .= trim('        <MerchantIdentifier>'.$partnerId.'</MerchantIdentifier>');
            $xml .= trim('    </Header>');
            $xml .= trim('    <MessageType>OrderFulfillment</MessageType>');
            $xml .= trim('    <Message>');
            $xml .= trim('    <MessageID>1</MessageID>');
            $xml .= trim('    <OperationType>Update</OperationType>');
            $xml .= trim('    <OrderFulfillment>');
            $xml .= trim('        <ShopOrderID>'.$this->_createXmlOrderId($order->getIncrementId()).'</ShopOrderID>');
            $xml .= trim('        <MerchantFulfillmentID>'.$partnerId.$order->getIncrementId().'</MerchantFulfillmentID>');
            $xml .= trim('        <FulfillmentDate>'.date('Y-m-d').'T'.date('H:i:s').'</FulfillmentDate>');
            $xml .= trim('        <FulfillmentData>');
            $xml .= trim('            <CarrierCode>'.$carrierCode.'</CarrierCode>');
            $xml .= trim('            <ShippingMethod>Standard</ShippingMethod>');
            $xml .= trim('            <ShipperTrackingNumber>'.$trackingNumber.'</ShipperTrackingNumber>');
            $xml .= trim('        </FulfillmentData>');
            $xml .= trim('        <Item>');
            $xml .= trim('            <MerchantOrderItemID>'.$order->getIncrementId().'-'.$item->getId().'-'.$partnerId.'</MerchantOrderItemID>');
            $xml .= trim('            <MerchantFulfillmentItemID>'.$partnerId.$order->getIncrementId().'</MerchantFulfillmentItemID>');
            $xml .= trim('            <Quantity>1</Quantity>');
            $xml .= trim('        </Item>');
            $xml .= trim('    </OrderFulfillment>');
            $xml .= trim('    </Message>');
            $xml .= trim('</ShopEnvelope>');
    
            #$filename = 'Euro_Vers_'.$order['Order']['orderId'].'_'.$partnerId.'_'.date('Ymd').'.xml';
            $filename = 'Euro_Vers_'.$partnerId.'-D_'.date('Ymd').time().'.xml';
            $this->_writeXml($filename, $xml);
            sleep(1);
        }
    }
    
    public function createOrderAdjustmentXml($order, $item, $adjustmentReason, $partnerId)
    {
        for ($i = 0; $i < $item->getQtyOrdered() ; $i++ )
        {
        
            $xml  = trim('<?xml version="1.0" encoding="UTF-8" ?>');
            $xml .= trim('<ShopEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="Shop-envelope.xsd">');
            $xml .= trim('    <Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>'.$partnerId.'</MerchantIdentifier></Header>');
            $xml .= trim('    <MessageType>OrderAdjustment</MessageType>');
            $xml .= trim('    <Message>');
            $xml .= trim('        <MessageID>1</MessageID>');
            $xml .= trim('        <OrderAdjustment>');
            $xml .= trim('            <ShopOrderID>'.$this->_createXmlOrderId($order->getIncrementId()).'</ShopOrderID>');
            $xml .= trim('            <AdjustedItem>');
            $xml .= trim('                <MerchantOrderItemID>'.$order->getIncrementId().'-'.$item->getId().'-'.$partnerId.'</MerchantOrderItemID>');
            $xml .= trim('                <MerchantAdjustmentItemID>111111</MerchantAdjustmentItemID>');
            $xml .= trim('                <AdjustmentReason>'.$adjustmentReason.'</AdjustmentReason>');
            $xml .= trim('               <ItemPriceAdjustments>');
            $xml .= trim('                   <Component>');
            $xml .= trim('                        <Type>Principal</Type>');
            $xml .= trim('                        <Amount currency="EUR">'.($item->getOriginalPrice() * 1).'</Amount>');
            $xml .= trim('                    </Component>');
            $xml .= trim('                </ItemPriceAdjustments>');
            $xml .= trim('            </AdjustedItem>');
            $xml .= trim('        </OrderAdjustment>');
            $xml .= trim('    </Message>');
            $xml .= trim('</ShopEnvelope>');
            
            #$filename = 'Euro_Aend_'.$order['Order']['orderId'].'_'.$partnerId.'_'.date('Ymd').'.xml';
            $filename = 'Euro_Aend_'.$partnerId.'-D_'.date('Ymd').time().'.xml';
            $this->_writeXml($filename, $xml);
            sleep(1);
        }
    }
    
    protected function _createXmlOrderId($orderId)
    {
        $part1 = substr($orderId, 0, 2);
        $part2 = substr($orderId, 2, 9);
        return '000-00000'.$part1.'-'.$part2;
        #return '000-0000010-0001313';
    }
    
    protected function _writeXml($filename, $data)
    {
        $file = $this->_getDirectoryNew() . $filename; 
        $handle = fopen($file, 'w');
        fwrite($handle, $data); 
        fclose($handle); 
        //change rights
        chmod($file, 0755);
    }
}
