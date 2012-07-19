<?php

abstract class Knm_Orderprocessing_Model_Abstract extends Mage_Core_Model_Abstract
{
    const NOTICE_LOG_PREFIX   = '#### ORDERPROCESSING NOTICE';
    const WARNING_LOG_PREFIX  = '#### ORDERPROCESSING WARNING';
    const ERROR_LOG_PREFIX    = '#### ORDERPROCESSING ERROR';
    
    const DIRECTORY_ORDERPROCESSING = 'orderprocessing';
    const DIRECTORY_NEW             = 'new';
    const DIRECTORY_PROCESSED       = 'processed';
    const DIRECTORY_ERROR           = 'error';
    
    protected function _hasOrderItemCreditmemo(Mage_Sales_Model_Order $order, $orderItem)
    {
        //check if creditmemo for item exists
        $creditmemo = $this->_getCreditmemo($order, $orderItem);
        //if creditmemo exsists, abort
        if($creditmemo !== false) {
            //echo $this->oColor->prstr("Creditmemo was already created. Aborting.", 'ERROR') . "\n";
            // Throw Error F020
            throw new Exception('Creditmemo was already created. Aborting.');
        }
        return true;
    }
    
    protected function _cancelIfNeeded(Mage_Sales_Model_Order $order, Knm_Orderprocessing_Model_Message $message)
    {
        // Als zu stornierend markierte Artikel (Status "nicht ausführbar") vorhanden?
        $cancelItemsExistant = $this->_hasExsistantCancelItems($order);
        if($cancelItemsExistant === false)
            return;
    
        $openItemsExistant = $this->_hasExsistantOpenItems($order);
    
        //TODO wo kommt dieses orderitem her
        if(!$orderItem->getQtyKmoApproved() || $openItemsExistant === false) {
            // check if order has been canceled already
            if($order->isCanceled()) {
                // Throw Error F080
                throw new Exception($oMessage->asXML(), 1080);
            }
    
            $this->_cancelOrder($order);
        }
    }
    
    protected function _cancelOrder(Mage_Sales_Model_Order $order)
    {
        try {
            // try to cancel order
            $order
                ->cancel()
                ->save()
            ;
    
            //set history log
            $order->addStatusHistoryComment('Komplette Stornierung der Bestellung durch Partner.', $order->getStatus())->setIsCustomerNotified(true);
            $emailComment = 'Ihre komplette Bestellung wurde aufgrund von Lieferschwierigkeiten bei unserem Partner storniert.';
    
            // inform customer about part and full cancellation
            $order->sendOrderUpdateEmail(true, $emailComment);
    
        } catch (Exception $e) {
            // Stornierung konnte nicht durchgeführt werden
            // Freigabe der vorauthorisierten Finanzmittel erfolgreich?
            // Throw Error F090
            #echo $this->oColor->prstr("Cancellation was not successful. Pre-Authorization successful?", 'ERROR') . "\n";
            throw new Exception($e->getMessage().' '.$oMessage->asXML(), 1090);
        }
    }
    
    
    
    //TODO need to be refactored!!!
    protected function _sendInvoiceEmail(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice)
    {
        $templateId = 15;
        $template = Mage::getModel('sales/email_template')->load($templateId);
        $body = str_replace('{{htmlescape var=$order.getCustomerName()}}',$order->getCustomerFirstname(). ' ' . $order->getCustomerLastname(),$template->getTemplateText());
        $body = str_replace('{{var order.increment_id}}',$order->getIncrementId(),$body);
    
        //create pdf invoice
        $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf( array($invoice) );
        //create mail
        $mail = new Zend_Mail('utf-8');
        $mail->setSubject('Ihre Rechnung für die Bestellung '. $order->getIncrementId() );
        $mail->setBodyHtml($body);
        #$mail->setBodyText('Test');
        //add recipients
        $mail->setFrom(
                Mage::getStoreConfig('trans_email/ident_support/email'),
                'Kontakt'
        );
        $mail->addTo($order->getCustomerEmail());
        $mail->addBcc(Mage::getStoreConfig('trans_email/ident_support/email'));
        //add attachment
        $attachment = $mail->createAttachment( $pdf->render() );
        $attachment->filename = $order->getIncrementId().'.pdf';
        //send
        $mail->send();
    }
    
    /**
     * Loop through invoice collection of order and find orderItem by id.
     *
     * @param Mage_Sales_Model_Order $order Order of invoices
     * @param integer $orderItemId order item id to look for in invoices
     * @return boolean if exsists return invoice, else false
     *
     * @author Thomas Wiener
     * @date 2012-06-06
     */
    protected function _getInvoiceByOrderItemId(Mage_Sales_Model_Order $order, $orderItemId)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            foreach ($invoice->getItemsCollection() as $item) {
                if($item->getOrderItemId() == $orderItemId) {
                    return $invoice;
                };
            }
        }
        return false;
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $orderItemId
     */
    protected function _getInvoiceItemByOrderItemId(Mage_Sales_Model_Order $order, $orderItemId)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            foreach ($invoice->getItemsCollection() as $item) {
                if($item->getOrderItemId() == $orderItemId) {
                    return $item;
                };
            }
        }
        return false;
    }
    
    /**
     * 
     * @param unknown_type $orderId
     * @return Ambigous <Mage_Core_Model_Abstract, Mage_Core_Model_Abstract>
     */
    protected function _getOrderByIncrementId($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        return $order;
    }
    
    /**
     * Finds creditmemo for given order and orderItem if exsists.
     * 
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return credtimemo if exsists otherwise false
     */
    protected function _getCreditmemo(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Item $orderItem)
    {
        foreach ($order->getCreditmemosCollection() as $creditmemo)
        {
            foreach ($creditmemo->getAllItems() as $creditmemoItem)
            {
                if($creditmemoItem->getId() == $orderItem->getId())
                {
                    return $creditmemo;
                }
            }
        }
        return false;
    }
    
    protected function _getInvoice($order, $orderItem)
    {
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            foreach ($invoice->getItemsCollection() as $invoiceItem)
            {
                if($invoiceItem->getOrderItemId() == $orderItem->getId()) { // && false == $invoice->getIsUsedForRefund()
                    return $invoice;
                };
            }
        }
        return false;
    }
    
    protected function _getItemsByMessage(Knm_Orderprocessing_Model_Message $message)
    {
        $items = Mage::getModel('orderprocessing/item')
            ->getCollection()
            ->addFieldToFilter('knm_message_id', $message->getId())
        ;
        #if ($message->getMessageType() == 'OrderFulfillment') die((string)$items->getSelect());
        return $items;
    }
    
    /**
     * * Loads item by XMLs Shop Order Item Id which is type of item id in Mage_Sales_Model_Order_Item
     * @param Knm_Orderprocessing_Model_Item $item Item with given Item Id
     * @return Mage_Sales_Model_Order_Item if found otherwise false
     */
    protected function _getOrderItemByShopOrderItemCode(Knm_Orderprocessing_Model_Item $item)
    {
        $orderItems = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('item_id', $item->getShopOrderItemCode())
        ;
        
        if (sizeof($orderItems) == 0)
            return false;
        
        $orderItem = $orderItems->getFirstItem();
        return $orderItem;
    }
    
    /**
     * Lao
     * @param unknown_type $item
     */
    protected function _getOrderItemByMerchantOrderItemId($item)
    {
        $orderItems = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('kmo_merchant_order_item_id', $item->getMerchantOrderItemId())
        ;
        if (sizeof($orderItems) == 0)
            return false;
        $orderItem = $orderItems->getFirstItem();
        return $orderItem;
    }
    
    protected function _hasExsistantCancelItems(Mage_Sales_Model_Order $order)
    {
        foreach($order->getAllItems() as $orderItem) {
            if($orderItem->isDummy() === true) {
                continue;
            }
            if($orderItem->getQtyKmoCanceled() > 0 || $orderItem->getQtyKmoCouldnotship() > 0)
                return true;
        }
        return false;
    }
    
    protected function _hasExsistantOpenItems(Mage_Sales_Model_Order $order)
    {
        foreach($order->getAllItems() as $orderItem) {
            if($orderItem->isDummy() === true) {
                continue;
            }
            if(($orderItem->geQtyKmoShipped() + $oOrderItem->getQtyKmoCouldnotship() + $orderItem->getQtyKmoCanceled()) != $oOrderItem->getQtyKmoApproved())
                return true;
        }
        return false;
    }
    
    protected function _checkIfAllItemsExist($items)
    {
        foreach ($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->getOrderItemById($item);
            // check if orderItem exsists
            if($orderItem === false) {
                // Throw Error F011
                throw new Exception($message, 1011);
            }
        }
        return true;
    }
    
    protected function _cancelOrderWhenAllItemsHaveBeenCanceled()
    {
    
    }
    
    protected function _getDirectoryNew()
    {
        return $this->_getDirectory(Knm_Orderprocessing_Model_Abstract::DIRECTORY_NEW);
    }
    
    protected function _getDirectoryProcessed()
    {
        return $this->_getDirectory(Knm_Orderprocessing_Model_Abstract::DIRECTORY_PROCESSED);
    }
    
    protected function _getDirectoryError()
    {
        return $this->_getDirectory(Knm_Orderprocessing_Model_Abstract::DIRECTORY_ERROR);
    }
    
    private function _getDirectory($directory)
    {
        $baseDirectory = Mage::getBaseDir('base') . '/' . Knm_Orderprocessing_Model_Abstract::DIRECTORY_ORDERPROCESSING;
        $directory     = $baseDirectory . '/' . $directory;
        
        if (!file_exists($baseDirectory) )
        {
            mkdir($baseDirectory);
            chmod($baseDirectory, 0777);
        }
        if (!file_exists($directory) )
        {
            mkdir($directory);
            chmod($directory, 0777);
        }
        
        return $directory . '/';
    }
    
    protected function _implodeArray($array, $glue = '<br />')
    {
    	$ignore = array('USER','PWD','SIGNATURE');
    	$text   = '';
    	foreach ($array as $key => $value)
    	{
    		if (in_array($key, $ignore)) $value = 'xxxxxxxxxxxxxxxxxxxx';
    
    		$text .= $glue . $key . ' => ' . $value;
    	}
    	return $text;
    }
}