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
        //create creditmemo
        $creditmemo = $this->_refund($order, $invoice, $items, $message, $offline = false, $isPrepareInvoiceCreditmemo = true);
        //update history
        $this->_updateQuantitiesAndAddHistory($order, $message);
    }

    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::cancel()
     */
    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message)
    {
        //cancel uninvoiced items
        $isCanceled = $this->cancelItems($order, $items, $message);
        //if invoice has been created, do refund
        if ($isCanceled === false)
        {
            $invoices   = $order->getInvoiceCollection();
            $invoice    = $invoices->getFirstItem();
            $creditmemo = $this->refund($order, $invoice, $items['NoInventory'], $message);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::convertItems()
     */
    public function convertItems($items)
    {
        return array();
    }

    /**
     * (non-PHPdoc)
     * @see Knm_Orderprocessing_Model_Payment_Interface::allowMultipleInvoices()
     */
    public function allowMultipleInvoices()
    {
        return false;
    }


}