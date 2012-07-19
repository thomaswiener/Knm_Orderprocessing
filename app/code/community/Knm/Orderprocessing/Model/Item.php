<?php
class Knm_Orderprocessing_Model_Item extends Mage_Core_Model_Abstract
{
    const OPEN     = 'open';
    const COMPLETE = 'complete';
    const ERROR    = 'error';
    
    public function _construct()
    {
        $this->_init('orderprocessing/item');
    }
    
    public function addItem(SimpleXMLElement $item, $messageType, $messageId, $orderId, $merchantIdentifier)
    {
        $a = (string) $merchantIdentifier;
        
        $this
            ->setKnmMessageId($messageId)
            ->setMessageType($messageType)
            ->setMerchantIdentifier($a)
            ->setShopOrderId($orderId)
            ->setMerchantOrderItemID($item->MerchantOrderItemID)
            ->setShopOrderItemCode($item->ShopOrderItemCode)
            ->setCreatedAt(now())
        ;
        
        $message = Mage::getModel('orderprocessing/message');
        
        switch ($messageType)
        {
            case $message::ORDERACKNOWLEDGEMENT:
                break;
            case $message::ORDERFULFILLMENT:
                $this
                    ->setMerchantOrderItemID($item->MerchantOrderItemID)
                    ->setMerchantFulfillmentItemId($item->MerchantFulfillmentItemID)
                    ->setQuantity($item->Quantity)
                ;
                break;
            case $message::ORDERADJUSTMENT:
                $this
                    ->setItemPriceAdjustments($item->ItemPriceAdjustments->Component->Amount)
                    ->setMerchantAdjustmentItemId($item->MerchantAdjustmentItemID)
                    ->setAdjustmentReason($item->AdjustmentReason)
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
    
    private function hasItemBeenProcessedBefore(SimpleXMLElement $itemXml, $messageType)
    {
        $item = Mage::getModel('orderprocessing/item');
        $collection = $this
            ->getCollection()
            ->addFieldToFilter('message_type', $messageType)
            ->addFieldToFilter('merchant_order_item_id', $itemXml->MerchantOrderItemID)
        ;
        if (count($collection) == 0)
            return false;
        return true;
    }
}