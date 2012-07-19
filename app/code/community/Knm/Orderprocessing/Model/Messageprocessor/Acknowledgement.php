<?php

class Knm_Orderprocessing_Model_Messageprocessor_Acknowledgement
    extends Knm_Orderprocessing_Model_Messageprocessor_Abstract 
    implements Knm_Orderprocessing_Model_Messageprocessor_Interface
{

    public function handleMessage(Knm_Orderprocessing_Model_Message $message)
    {
        //get items from message
        $items = $this->_getItemsByMessage($message);
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
    
        //loop through items
        foreach($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->_getOrderItemByShopOrderItemCode($item);
    
            //check if orderItem exists
            if ($orderItem !== false)
            {
                //check if kmo merchant order item id exists => will be written on acknowledgement
                if ($orderItem->getKmoMerchantOrderItemId() != '')
                {
                    //item already confirmed
                    //throw new Exception('item already confirmed');
                    continue;
                }
            }
            else  //orderItem does not exist, abort
            {
                //items not found in kmo reference data
                throw new Exception('items not found in kmo reference data');
            }
    
            //check if creditmemo for item exists
            $creditmemo = $this->_getCreditmemo($order, $orderItem);
            //if creditmemo exsists, abort
            if($creditmemo !== false) {
                //echo $this->oColor->prstr("Creditmemo was already created. Aborting.", 'ERROR') . "\n";
                // Throw Error F020
                throw new Exception('Creditmemo was already created. Aborting.');
            }
        }
    
        //orderitem exists and no creditmemo was yet created
        //loop through items and write kmo merchant order id to orderitem
        foreach($items as $item)
        {
            $orderItem = $this->_getOrderItemByShopOrderItemCode($item);
            $orderItem->addData(array(
                'kmo_merchant_order_item_id' => $item->getMerchantOrderItemId(),
                'qty_kmo_approved'           => $orderItem->getQtyOrdered(),
            ));
            //write MerchantOrderItemId to orderItem for later processing
            $orderItem->save();
            //write history log to order, save
            $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': KmoMerchantOrderItemId for Item: ' . $orderItem->getId() . ' was successfully added.');
            $order->save();
        }
    }
    
}