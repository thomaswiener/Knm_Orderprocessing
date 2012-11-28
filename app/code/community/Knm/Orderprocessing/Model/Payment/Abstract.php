<?php

class Knm_Orderprocessing_Model_Payment_Abstract 
    extends Knm_Orderprocessing_Model_Abstract 
{
    protected $paymentIdentifier;
    
    /**
     * function _createInvoice
     *
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $invoiceItems
     * @param unknown_type $captureCase
     * @throws Exception
     */
    protected function _createInvoice(Mage_Sales_Model_Order $order, $invoiceItems = array(), $captureCase = 'online')
    {
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($invoiceItems);
        $invoice->setRequestedCaptureCase($captureCase);
        $invoice->register();
    
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
        ;
    
        $transactionSave->save();
    
        //check if invoice was created
        if(!$invoice || !$invoice->getId()) {
            // Throw Error F100
            #echo $this->oColor->prstr("Problems while creating invoice. Aborting.", 'ERROR') . "\n";
            throw new Exception($oMessage->asXML(), 1100);
        }
        
        //add status history to order
        $order->addStatusHistoryComment($this->_getPrefixLog('NOTICE_LOG_PREFIX') . ': Invoice: ' . $invoice->getIncrementId() . ' was successfully created.');
        $order->save();
        
        return $invoice;
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param unknown_type $items
     * @param Knm_Orderprocessing_Model_Message $message
     * @param unknown_type $offline
     * @param unknown_type $isPrepareInvoiceCreditmemo
     * @return unknown
     */
    protected function _refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message, $offline = true, $isPrepareInvoiceCreditmemo = false)
    {
        $service = Mage::getModel('sales/service_order', $order);
        $data = $this->_getRefundArray($order, $invoice, $items);
        
        if (!$isPrepareInvoiceCreditmemo)
            $creditmemo = $service->prepareCreditmemo($data);
        else
            $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
        
        $creditmemo->setRefundRequested(true);
        $creditmemo->setOfflineRequested($offline);
        $creditmemo->register();
        $creditmemo->setEmailSent(true);
        $creditmemo->getOrder()->setCustomerNoteNotify(false);
        
        $transactionSave = Mage::getModel('core/resource_transaction')
        ->addObject($creditmemo)
        ->addObject($creditmemo->getOrder())
        ;
        
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();
        
        $creditmemo->sendEmail();
        
        return $creditmemo;
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $items
     * @param Knm_Orderprocessing_Model_Message $message
     */
    protected function cancelItems(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $invoices = $order->getInvoiceCollection();
        if (sizeof($invoices) == 0)
        {
            print_r($items);
            foreach($items['NoInventory'] as $itemId => $qty)
            {
                $item = Mage::getModel('sales/order_item')->load($itemId);
                $item
                ->setQtyCanceled($item->getQtyCanceled() + $qty)
                ->save()
                ;
            }
            return true;
        }
        return false;
    }
    
    /**
     * function refundInvoice
     *
     * Refunds invoice for all payment methods
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param unknown_type $items
     * @param Knm_Orderprocessing_Model_Message $message
     */
    /*protected function _refundInvoice(
            Mage_Sales_Model_Order $order,
            Mage_Sales_Model_Order_Invoice $invoice,
            $items = array(),
            Knm_Orderprocessing_Model_Message $message)
    {
        //get payment
        $payment = $order->getPayment();
        //get method
        $paymentMethod = $payment->getMethod();
    
        switch ($paymentMethod)
        {
            case Knm_Orderprocessing_Model_Messageprocessor::RATEPAY_INVOICE:
                $this->refundRatepayInvoice($order, $items);
                break;
            case Knm_Orderprocessing_Model_Messageprocessor::COMPUTOP_CC:
                $creditmemo = $this->prepareCreditmemo($order, $invoice, $items);
                $creditmemo->refund();
                $this->finalizeCreditmemo($creditmemo);
                break;
            case Knm_Orderprocessing_Model_Messageprocessor::COMPUTOP_EFT:
                break;
            case Knm_Orderprocessing_Model_Messageprocessor::PAYPAL_STANDARD:
                $creditmemo = $this->prepareCreditmemo($order, $invoice, $items);
                $refundType = 'Full';
                $amount = 0;
                if(is_array($items) && count($items) > 0)
                {
                    $amount = $creditmemo->getGrandTotal();
                    if($dAmount != $oOrder->getBaseGrandTotal())
                        $refundType = 'Partial';
                }
                $isSucces = false;
                $isSucces = $this->refundPayPal($order, $invoice);
                if ($isSuccess)
                    $creditmemo->refund();
                $this->finalizeCreditmemo($creditmemo);
                break;
            default:
                break;
        }
    }*/
    
    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param unknown_type $items
     * @return unknown
     */
//     protected function _prepareCreditmemo(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items)
//     {
//         $service = Mage::getModel('sales/service_order', $order);
        
//         $shippingAmount = $this->_getShippingAmount($order, $items);
//         $data = $this->_getCreditmemoData($items, $shippingAmount);
//         //$this->_getCreditmemoData($data, $shippingAmount)
//         // Getting creditmemo
//         $creditmemo = $service->prepareCreditmemo($data);
//         //$service->prepareInvoiceCreditmemo($invoice, $this->_getCreditmemoData($items));
    
//         /*$diff = $creditmemo->getGrandTotal() - $creditmemo->getSubtotalInclTax();
//         if($diff != 0) {
//             unset($creditmemo);
//             $data = $this->_getCreditmemoData($aItems);
//             if($diff > 0) {
//                 $data['adjustment_negative'] = $diff;
//             } elseif($diff < 0) {
//                 $data['adjustment_positive'] = (-1 * $diff);
//             }
//             $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
//         }*/
//         // Set do transaction
//         $creditmemo->setDoTransaction(true);
//         $creditmemo->setRefundRequested(true);
        
//         $creditmemo->setBaseGrandTotal(round($creditmemo->getBaseGrandTotal(),2));
//         $creditmemo->setGrandTotal(round($creditmemo->getGrandTotal(),2));
    
//         return $creditmemo;
//     }
    
    protected function _getRefundArray(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items)
    {
        $data = array(
            'items'               => $this->_reformatArray($items),
            'do_offline'          => 1,
            'comment_text'        => '',
            'shipping_amount'     => $this->_getShippingAmount($order, $invoice, $items),
            'adjustment_positive' => 0,
            'adjustment_negative' => 0,
            'qtys'                => $items
        )
        ;
        return $data;
    }
    
    protected function _reformatArray($items)
    {
        $result = array();
        foreach ($items as $id => $qty)
        {
            $result[$id] = array('qty' => $qty);
        }
        return $result;
    }
    
    protected function _getShippingAmount(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items)
    {
        //reload order
        $orderItems = $order->getAllVisibleItems();
        
        $totalShipped   = 0;
        $totalCancelled = 0;
        $totalRefunded  = 0;
        foreach ($orderItems as $orderItem)
        {
            $totalShipped   += $orderItem->getQtyShipped();
            $totalCancelled += $orderItem->getQtyCancelled();
            $totalRefunded  += $orderItem->getQtyRefunded();
        
            if (array_key_exists($orderItem->getId(), $items))
            {
                $totalRefunded  += $items[$orderItem->getId()];
            }
        }
    
        //all items will be returned with current request, refund shipping amount = true
        if ((int) $totalShipped == (int) ($totalCancelled + $totalRefunded))
        {
            //calculate shipping amount
            $baseAllowedAmount = 0;
            $isShippingInclTax = Mage::getSingleton('tax/config')->displaySalesShippingInclTax($order->getStoreId());
            if ($isShippingInclTax) {
                $baseAllowedAmount = $order->getBaseShippingInclTax() - $order->getBaseShippingRefunded() - $order->getBaseShippingTaxRefunded();
            } else {
                $baseAllowedAmount = $order->getBaseShippingAmount() - $order->getBaseShippingRefunded();
                $baseAllowedAmount = min($baseAllowedAmount, $invoice->getBaseShippingAmount());
            }
            
            return $baseAllowedAmount;
        }
        return 0;
    }
    
//     protected function _finalizeCreditmemo(Mage_Sales_Model_Order_Creditmemo $creditmemo)
//     {
//         $creditmemo->setEmailSent(true);
//         $creditmemo->getOrder()->setCustomerNoteNotify(true);
    
//         $transactionSave = Mage::getModel('core/resource_transaction')
//             ->addObject($creditmemo)
//             ->addObject($creditmemo->getOrder())
//         ;
    
//         if ($creditmemo->getInvoice())
//             $transactionSave->addObject($creditmemo->getInvoice());
    
//         $transactionSave->save();
//         $creditmemo->sendEmail(true, '');
        
//         //add status history to order
//         $creditmemo->getOrder()->addStatusHistoryComment($this->_getPrefixLog('NOTICE_LOG_PREFIX') . ': Creditmemo: ' . $creditmemo->getIncrementId() . ' was successfully created.');
//         $creditmemo->getOrder()->save();
//     }
    
    /**
     * 
     * @param unknown_type $items
     */
    protected function _getCreditmemoData($items, $shippingAmount) {
        $itemArray = array();
        foreach ($items as $itemId => $qty) {
            $itemArray[$itemId] = array('qty' => $qty);
        }
    
        $data = array(
            'items'               => $itemArray,
            'do_offline'          => 0,
            'shipping_amount'     => $shippingAmount,
            'adjustment_positive' => 0,
            'adjustment_negative' => 0,
            'qtys'                => $items,
        );
        return $data;
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $items
     * @param Knm_Orderprocessing_Model_Message $message
     */
    protected function _updateQuantitiesAndAddHistory(Mage_Sales_Model_Order $order, Knm_Orderprocessing_Model_Message $message, $updateQtyRefunded = false)
    {
        $emailComment = null;
    
        $items = $this->_getItemsByMessage($message);
    
        foreach ($items as $item)
        {
            //get orderItem from item
            $orderItem = $this->_getOrderItemByMerchantOrderItemId($item);
            //calculate amount
            $qty = (double)$item->getItemPriceAdjustments() / (double) $orderItem->getBasePriceInclTax();
    
            $fieldToChange = '';
            if($item->getAdjustmentReason() == 'CustomerReturn')
            {
                // Artikel als "Retourniert" markieren
                $fieldToChange = 'qty_kmo_backordered';
            }
            elseif($item->AdjustmentReason == 'CouldNotShip' || $item->AdjustmentReason == 'NoInventory')
            {
                // Artikel als "Nicht ausführbar" markieren
                $fieldToChange = 'qty_kmo_couldnotship';
    
                $emailComment.= ($orderItem->getData('qty_kmo_canceled') + $orderItem->getData('qty_kmo_couldnotship') + $qty).
                "x ".$orderItem->getData('name')." (". $orderItem->getData('sku') .")<br />";
            }
    
            $orderItem->addData(array(
                $fieldToChange => ($qty + $orderItem->getData($fieldToChange)),
            ));
            
            if ($updateQtyRefunded)
                $orderItem->addData(array(
                    'qty_refunded' => ($qty + $orderItem->getData('qty_refunded')),
                ));
    
            $orderItem->save();
        }
    
        //reload order object, might have changed by now already (ratepay problem with grand total)
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
    
        // send order update email for canceled items
        if(!is_null($emailComment)) {
            $order->addStatusHistoryComment('Teilstornierung der Bestellung durch Partner.<br />'. $emailComment, $order->getStatus())->setIsCustomerNotified(true);
            $order->sendOrderUpdateEmail(true, $emailComment);
        }
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param unknown_type $fieldToChange
     * @param unknown_type $qty
     */
    protected function _setOrderItemQty(Mage_Sales_Model_Order_Item $orderItem, $fieldToChange, $qty)
    {
        $orderItem->addData(array(
            $fieldToChange => ($qty + $orderItem->getData($fieldToChange)),
            'qty_refunded'  => ($qty + $orderItem->getData('qty_refunded')) //edit
        ));
        
        $orderItem->save();
    }
    
    protected function implodeArray($array, $glue = '<br />')
    {
    	$text = '';
    	foreach ($array as $key => $value)
    	{
    		$text .= $glue . $key . ' => ' . $value;
    	}
    	return $text;
    }
}