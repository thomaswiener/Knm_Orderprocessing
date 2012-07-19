<?php
class Knm_Orderprocessing_Model_Message extends Mage_Core_Model_Abstract
{
    const OPEN     = 'open';
    const COMPLETE = 'complete';
    const ERROR    = 'error';
    
    const ORDERACKNOWLEDGEMENT = 'OrderAcknowledgement';
    const ORDERFULFILLMENT     = 'OrderFulfillment';
    const ORDERADJUSTMENT      = 'OrderAdjustment';
    const ORDERCANCELLATION    = 'OrderCancellation';
    
    
    public function _construct()
    {
        $this->_init('orderprocessing/message');
    }
    
    public function addMessage(SimpleXMLElement $message, $messageType, $merchantIdentifier, $fileName)
    {
        $a = (string) $merchantIdentifier;
        
        $this
            ->setMessageType($messageType)
            ->setMerchantIdentifier($a)
            ->setFileNameXml($fileName)
            ->setStatusXml(self::OPEN)
            ->setStatusSettlementReport(self::OPEN)
            ->setCreatedAt(now())
        ;
    
        switch ($messageType)
        {
            case self::ORDERACKNOWLEDGEMENT:
                $this
                    ->setStatusCode($message->OrderAcknowledgement->StatusCode)
                    ->setShopOrderId($this->normalizeOrderId($message->OrderAcknowledgement->ShopOrderID))
                    ->setMerchantOrderId($message->OrderAcknowledgement->MerchantOrderID)
                    ;
                break;
            case self::ORDERFULFILLMENT:
                $this
                    ->setShopOrderId($this->normalizeOrderId($message->OrderFulfillment->ShopOrderID))
                    ->setMerchantFulfillmentId($message->OrderFulfillment->MerchantFulfillmentID)
                    ->setFulfillmentDate($message->OrderFulfillment->FulfillmentDate)
                    ->setCarrierCode($message->OrderFulfillment->FulfillmentData->CarrierCode)
                    ->setShippingMethod($message->OrderFulfillment->FulfillmentData->ShippingMethod)
                    ->setShipperTrackingNumber($message->OrderFulfillment->FulfillmentData->ShipperTrackingNumber)
                    ;
                break;
            case self::ORDERADJUSTMENT:
                $this
                    ->setStatusCode($message->OrderAdjustment->StatusCode)
                    ->setShopOrderId($this->normalizeOrderId($message->OrderAdjustment->ShopOrderID))
                    ->setMerchantOrderId($message->AdjustedItem->MerchantOrderID)
                    ->setMerchantAdjustmentItemId($message->AdjustedItem->MerchantAdjustmentItemID)
                    ->setAdjustmentReason($message->AdjustedItem->AdjustmentReason)
                    ;
                break;
            default:
                break;
        }
        
        try
        {
            $this->save();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    private function normalizeOrderId($orderId)
    {
        $orderId = str_replace('-', '', $orderId);
        return (int) $orderId;
    }
    
public function hasMessageBeenProcessedBefore(SimpleXMLElement $message, $messageType, $merchantIdentifier, $fileName)
    {
        $collection = $this
            ->getCollection()
            ->addFieldToFilter('message_type', $messageType)
            ->addFieldToFilter('merchant_identifier', $merchantIdentifier)
            ->addFieldToFilter('file_name_xml', $fileName)
        ;
        switch ($messageType)
        {
            case self::ORDERACKNOWLEDGEMENT:
                $collection->addFieldToFilter('shop_order_id', $message->OrderAcknowledgement->ShopOrderID)
                ;
                break;
            case self::ORDERFULFILLMENT:
                $collection
                    ->addFieldToFilter('shop_order_id', $message->OrderFulfillment->ShopOrderID)
                    ->addFieldToFilter('shipper_tracking_number', $message->OrderFulfillment->FulfillmentData->ShipperTrackingNumber)
                ;
                break;
            case self::ORDERADJUSTMENT:
                $collection
                    ->addFieldToFilter('shop_order_id', $message->OrderAdjustment->ShopOrderID)
                ;
                break;
            default:
                break;
        }
        if (count($collection) == 0)
            return false;
        return true;
    }
    
    public function logSettlementReportExportStatus($sFileName)
    {
        $this
            ->setFileNameSettlementReport($sFileName)
            ->setStatusSettlementReport(self::COMPLETE)
            ->setExportedAt(now())
            ->save();
    }
    
    public function updateStatus()
    {
        $this
            ->setStatusXml(self::COMPLETE)
            ->setProcessedAt(now())
            ->save()
        ;
    }
}