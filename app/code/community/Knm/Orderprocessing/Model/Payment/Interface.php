<?php

interface Knm_Orderprocessing_Model_Payment_Interface
{
    public function deliver(Mage_Sales_Model_Order $order, $items = array());

    public function refund(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $items = array(), Knm_Orderprocessing_Model_Message $message);

    public function cancel(Mage_Sales_Model_Order $order, $items = array(), Knm_Orderprocessing_Model_Message $message);

    public function convertItems($items);

    public function allowMultipleInvoices();
}