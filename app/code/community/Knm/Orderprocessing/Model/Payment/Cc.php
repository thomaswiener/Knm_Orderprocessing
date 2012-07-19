<?php

class Knm_Orderprocessing_Model_Payment_Cc 
    extends Knm_Orderprocessing_Model_Payment_Abstract 
        implements Knm_Orderprocessing_Model_Payment_Interface
{
	/**
	 * Technical name of payment method see Dotsource_Computop_Model_Payment_Cc, must be unique
	 * @var string
	 */
    protected $paymentName = 'computop_cc';
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::deliver()
     */
    public function deliver(Mage_Sales_Model_Order $order, $items = array())
    {
        $this->_createInvoice($order, $items);
    }
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::refund()
     */
    public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $creditmemo = $this->_refund($order, $invoice, $items, $message, $offline = false, $isPrepareInvoiceCreditmemo = true);
        
        if ($creditmemo->canRefund())
        {
            $creditmemo->refund();
        }
        
        $this->_updateQuantitiesAndAddHistory($order, $message);
    }
    
    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::cancel()
     */
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
    
//         $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
//         $creditmemo->setRefundRequested(true);
//         $creditmemo->setOfflineRequested(false);
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
    
//         if ($creditmemo->canRefund())
//         {
//             $creditmemo->refund();
//         }
    
//         $this->_updateQuantitiesAndAddHistory($order, $message);
//     }

}