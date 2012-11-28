<?php

class Knm_Orderprocessing_Model_Messageprocessor_Abstract 
    extends Knm_Orderprocessing_Model_Abstract 
{
    //payment methods
    const RATEPAY_INVOICE       = 'ratepay_rechnung';
    const COMPUTOP_CC           = 'computop_cc';
    const COMPUTOP_EFT          = 'computop_eft';
    const PAYPAL_STANDARD       = 'paypal_standard';
    const CHECKMO               = 'checkmo';
    const FREE                  = 'free';
    const PNSOFORTUEBERWEISUNG  = 'pnsofortueberweisung';
    const RS_VORKASSE           = 'rs_vorkasse';
    const RS_LASTSCHRIFT        = 'rs_lastschrift';
    
    /**
     * current payment methods
     * add new row here to add new payment method
     * create new payment class implementing interface
     * @var unknown_type
     */
    protected $paymentMethods = array(
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::RATEPAY_INVOICE        => 'orderprocessing/payment_ratepay',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::COMPUTOP_CC            => 'orderprocessing/payment_cc',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::COMPUTOP_EFT           => 'orderprocessing/payment_eft',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::PAYPAL_STANDARD        => 'orderprocessing/payment_paypal',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::CHECKMO                => 'orderprocessing/payment_checkmo',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::FREE                   => 'orderprocessing/payment_free',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::PNSOFORTUEBERWEISUNG   => 'orderprocessing/payment_pnsofortueberweisung',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::RS_VORKASSE            => 'orderprocessing/payment_rsvorkasse',
        Knm_Orderprocessing_Model_Messageprocessor_Abstract::RS_LASTSCHRIFT         => 'orderprocessing/payment_rslastschrift',
    );
    
    /**
     * Current order processing steps
     * @var unknown_type
     */
    protected $processes = array(
        Knm_Orderprocessing_Model_Message::ORDERACKNOWLEDGEMENT => 'orderprocessing/messageprocessor_acknowledgement',
        Knm_Orderprocessing_Model_Message::ORDERFULFILLMENT     => 'orderprocessing/messageprocessor_fulfillment',
        Knm_Orderprocessing_Model_Message::ORDERADJUSTMENT      => 'orderprocessing/messageprocessor_adjustment',
        Knm_Orderprocessing_Model_Message::ORDERCANCELLATION    => 'orderprocessing/messageprocessor_cancellation',
    );
    
    /**
     * Load payment model by order payment method
     * @param Mage_Sales_Model_Order $order
     * @throws Exception
     */
    protected function _getPaymentModel(Mage_Sales_Model_Order $order)
    {
        //get payment method
        $paymentMethod = $order->getPayment()->getMethod();
        //find paymentMethod in array, if not found throw exception
        if (array_key_exists($paymentMethod , $this->paymentMethods))
            return $this->paymentMethods[$paymentMethod];
    
        //model not found
        throw new Exception('payment model not found'); #$oMessage, 1040);
    }
    
    /**
     * function createShipment
     *
     * create shipment for shipment items
     * @param Knm_Orderprocessing_Model_Message $message
     * @param Mage_Sales_Model_Order $order
     * @param unknown_type $shipmentItems
     */
    protected function _createShipment(Knm_Orderprocessing_Model_Message $message, Mage_Sales_Model_Order $order, $shipmentItems)
    {
        //prepare shipment, add items
        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($shipmentItems);
    
        //write data to shipment
        /*$shipment->addData(array(
                'kmo_fulfillment_id'      => (string) $message->OrderFulfillment->MerchantFulfillmentId,
                'kmo_fulfillment_date'    => date('Y-m-d H:i:s', strtotime((string) $message->OrderFulfillment->FulfillmentDate)),
                'kmo_fulfillment_partner' => $partner,
                'kmo_carrier_code'        => (string)$message->OrderFulfillment->FulfillmentData->CarrierCode,
                'kmo_shipping_method'     => (string)$message->OrderFulfillment->FulfillmentData->ShippingMethod,
        ));*/
        //add tracking information
        $track = Mage::getModel('sales/order_shipment_track')
            ->setNumber(                 (string) $message->getShipperTrackingNumber())
            ->setCarrierCode( strtolower((string) $message->getCarrierCode()))
            ->setTitle(                  (string) $message->getCarrierCode())
        ;
        //add tracking information to shipment
        $shipment->addTrack($track);
        $shipment->register();
        $order->setIsInProcess(true);
        //add shipment to order
        Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($order)
            ->save()
        ;
    
        if(!$shipment || !$shipment->getId()) {
            // Throw Error F050
            throw new Exception($oMessage->asXML(), 1050);
        }
        //inform customer by email
        $shipment->sendEmail();
    
        $order->addStatusHistoryComment($this->_getPrefixLog('NOTICE_LOG_PREFIX') . ': Shipment ' . $shipment->getIncrementId() . ' was created successfully. Customer was informed by email.');
        $order->save();
    }
    
    protected function _hasValidItemQuantity($items)
    {
        $totalQty = 0;
        foreach($items as $sku => $qty)
        {
            $totalQty += $qty;
        }
        if ($totalQty > 0) return true;
        
        return false;
    }
    
    protected function _writeXmlsToDb() {
    
        //open directory new
        $oDirHandle = opendir($this->_getDirectoryNew());
    
        //is directory accessible
        if($oDirHandle !== false) {
            //read directory content
            while(($sFilename = readdir($oDirHandle)) !== false) {
                //is current directory index a file
                if(is_file($this->_getDirectoryNew() . $sFilename) === true) {
                    //does filename match given pattern
                    if(true || preg_match($this->_sFileRegex, $sFilename) == 1) {
    
                        //
                        $oXml = simplexml_load_file($this->_getDirectoryNew() . $sFilename);
                        $model = Mage::getModel('orderprocessing/observer');
                        $model->processXml($oXml, $sFilename);
    
                        unlink($this->_getDirectoryNew() . $sFilename);
                    } else {
                        //handle pattern mismatch
                        $this->_handleFileError($this->_getDirectoryNew(), $sFilename);
                    }
                }
            }
            //close directory
            closedir($oDirHandle);
        }
    }
}