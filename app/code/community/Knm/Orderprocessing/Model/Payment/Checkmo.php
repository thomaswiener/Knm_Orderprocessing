<?php

class Knm_Orderprocessing_Model_Payment_Checkmo 
    extends Knm_Orderprocessing_Model_Payment_Abstract 
        implements Knm_Orderprocessing_Model_Payment_Interface
{
    
    protected $paymentName = 'checkmo';
    
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
        $creditmemo = $this->_refund($order, $invoice, $items, $message);
        
        $this->_updateQuantitiesAndAddHistory($order, $message);
    }
    
    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        $order->cancel();
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
    
//         $this->_updateQuantitiesAndAddHistory($order, $message);
//     }
    
}