<?php
/**
 * 
 * @author twiener
 *
 */
class Knm_Orderprocessing_Model_Payment_Paypal 
    extends Knm_Orderprocessing_Model_Payment_Abstract 
        implements Knm_Orderprocessing_Model_Payment_Interface
{
	
    //ipn response of paypal from ip: 173.0.82.126
	
    //paypal urls live and dev
    const PAYPAL_SANDBOX_CURLOPT_URL      = 'https://api-3t.sandbox.paypal.com/nvp';
    const PAYPAL_LIVE_CURLOPT_URL         = 'https://api-3t.paypal.com/nvp';
    
    const PAYPAL_SANBOX_CURLOPT_USERAGENT = 'stage.faszinata.de';
    const PAYPAL_LIVE_CURLOPT_USERAGENT   = 'faszinata.de';
    
    private $paymentName                  = 'paypal_standard';
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::deliver()
     */
    public function deliver(Mage_Sales_Model_Order $order, $items = array())
    {
    	//recommmended settings for payment action is: CAPTURE
    	//do not use AUTHORIZE
    	//a refund for several invoices is difficult to handle and can lead to problems with customer in orderprocessing
    	//invoice has already been created (configured in system config paypal)
        //$this->_createInvoice($order, $items);
    }
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::refund()
     */
    public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $creditmemo = $this->_refund($order, $invoice, $items, $message);
        
        $refundType = 'Full';
        $amount = 0;
        if(is_array($items) && count($items) > 0)
        {
            $amount = $creditmemo->getGrandTotal();
            if($amount != $order->getBaseGrandTotal())
                $refundType = 'Partial';
        }
        
        $isSucces = false;
        $isSucces = $this->_refundPayPal($order, $invoice, $amount, $refundType);
        
        $this->_updateQuantitiesAndAddHistory($order, $message);
        
    }
    
    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
    
    }
    
    
//     /**
//      * (non-PHPdoc)
//      * @see Knm_Orderprocessing_Model_Payment_Interface::refund()
//      */
//     public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message)
//     {
//         $service = Mage::getModel('sales/service_order', $order);
//         $data = $this->_getRefundArray($order, $items);
    
//         $creditmemo = $service->prepareCreditmemo($data);
//         $creditmemo->setRefundRequested(true);
//         $creditmemo->setOfflineRequested(true);
//         $creditmemo->register();
//         $creditmemo->setEmailSent(true);
//         $creditmemo->getOrder()->setCustomerNoteNotify(false);
    
//         $transactionSave = Mage::getModel('core/resource_transaction')
//         ->addObject($creditmemo)
//         ->addObject($creditmemo->getOrder())
//         ;
    
//         if ($creditmemo->getInvoice()) {
//             $transactionSave->addObject($creditmemo->getInvoice());
//         }
    
//         $transactionSave->save();
    
//         $refundType = 'Full';
//         $amount = 0;
//         if(is_array($items) && count($items) > 0)
//         {
//             $amount = $creditmemo->getGrandTotal();
//             if($amount != $order->getBaseGrandTotal())
//                 $refundType = 'Partial';
//         }
    
//         $isSucces = false;
//         $isSucces = $this->_refundPayPal($order, $invoice, $amount, $refundType);
    
//         $this->_updateQuantitiesAndAddHistory($order, $message);
    
//     }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param unknown_type $amount
     * @param unknown_type $refundType
     * @return multitype:string unknown number NULL
     */
    private function _getRefundPayPalParams(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $amount, $refundType)
    {
        $params = array(
            'TRANSACTIONID'   => $invoice->getTransactionId(),
            'AMT'             => abs($amount),
            'CURRENCYCODE'    => $order->getBaseCurrencyCode(),
            'REFUNDTYPE'      => $refundType,
            'NOTE'            => "Erstattung. Bestellung #{$order->getIncrementId()}",
            'USER'            => Mage::getStoreConfig('paypal/wpp/api_username'),
            'PWD'             => Mage::getStoreConfig('paypal/wpp/api_password'),
            'SIGNATURE'       => Mage::getStoreConfig('paypal/wpp/api_signature'),
            'VERSION'         => '58.0',
            'METHOD'          => 'RefundTransaction',
            #'INVNUM'         => '',
            #'SOFTDESCRIPTOR' => '',
        );
        
    	Mage::log($params);
    	
        return $params;
    }
    
    /**
     * Performs a paypal request, action depending on params sent
     * @param unknown_type $params
     * @return multitype:
     */
    private function _sendPayPalRequest($params)
    {
        $curl = curl_init();
    
        $xmlPost = array();
        foreach ($params as $key => $value) {
            $xmlPost[] = $key . '=' . urlencode($value);
        }
    
        $isSandbox = Mage::getStoreConfig('paypal/wpp/sandbox_flag');
        if($isSandbox == 1) {
            curl_setopt($curl, CURLOPT_URL, self::PAYPAL_SANDBOX_CURLOPT_URL);
            curl_setopt($curl, CURLOPT_USERAGENT, self::PAYPAL_SANBOX_CURLOPT_USERAGENT);
        } else {
            curl_setopt($curl, CURLOPT_URL, self::PAYPAL_LIVE_CURLOPT_URL);
            curl_setopt($curl, CURLOPT_USERAGENT, self::PAYPAL_LIVE_CURLOPT_USERAGENT);
        }
    
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    
        curl_setopt($curl, CURLOPT_POST, count($params));
        curl_setopt($curl, CURLOPT_POSTFIELDS, join('&', $xmlPost));
    
        $response = curl_exec($curl);
        $result = array();
        foreach (explode('&', $response) as $line) {
            if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                $result[$matches[1]] = urldecode($matches[2]);
            }
        }
        return $result;
    }
    
    private function _refundPayPal(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $amount, $refundType)
    {
        $params   = $this->_getRefundPayPalParams($order, $invoice, $amount, $refundType);
        $response = $this->_sendPayPalRequest($params);
        
        $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': PayPal Request : ' . $this->_implodeArray($params));
        $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': PayPal Response : ' . $this->_implodeArray($response));
        $order->save();
        
        if($response['ACK'] != 'Success')
        {
            throw new Exception($response.' '.$message, 1031);
        }
    }
    
    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param unknown_type $items
     * @return unknown
     */
    protected function _prepareCreditmemo(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items)
    {
    	$service = Mage::getModel('sales/service_order', $order);
        
        $shippingAmount = $this->_getShippingAmount($order, $items);
        $data = $this->_getCreditmemoData($items, $shippingAmount);
        //$this->_getCreditmemoData($data, $shippingAmount)
        // Getting creditmemo
        $creditmemo = $service->prepareCreditmemo($data);
        #$service->prepareInvoiceCreditmemo($invoice, $this->_getCreditmemoData($items));
    
        // Set do transaction
        $creditmemo->setDoTransaction(true);
        $creditmemo->setRefundRequested(true);
		
		$baseTotal = 0;
		$total     = 0;
		foreach($creditmemo->getAllItems() as $creditmemoItem)
		{
			$baseTotal = $baseTotal + $creditmemoItem->getBaseRowTotalInclTax() - $creditmemoItem->getBaseDiscountAmount();
			$total     = $total +     $creditmemoItem->getRowTotalInclTax() -     $creditmemoItem->getDiscountAmount();
		}
		
		$baseTotal += $shippingAmount;
        $total     += $shippingAmount;
		
		$creditmemo->setBaseGrandTotal(round($baseTotal,2));
        $creditmemo->setGrandTotal(round($total,2));
    
    	return $creditmemo;
    }
   
    protected function _finalizeCreditmemo(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
    	$creditmemo->setEmailSent(true);
        $creditmemo->getOrder()->setCustomerNoteNotify(true);
    
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder())
        ;
    
        if ($creditmemo->getInvoice())
            $transactionSave->addObject($creditmemo->getInvoice());
    
        $transactionSave->save();
        #$creditmemo->sendEmail(true, '');
        
        $order = $creditmemo->getOrder();
        //add status history to order
        $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': Creditmemo: ' . $creditmemo->getIncrementId() . ' was successfully created.');
		
        $order->setBaseTotalRefunded($order->getBaseTotalRefunded() + $creditmemo->getGrandTotal());
		$order->setTotalRefunded($order->getTotalRefunded() + $creditmemo->getGrandTotal());
		
		$order->setBaseTotalOnlineRefunded($order->getBaseTotalOnlineRefunded() + $creditmemo->getGrandTotal());
		$order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() + $creditmemo->getGrandTotal());
		
        $order->save();
    
        return $creditmemo;
    }
}