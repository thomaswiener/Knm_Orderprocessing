<?php

class Knm_Orderprocessing_Model_Messageprocessor 
    extends Knm_Orderprocessing_Model_Messageprocessor_Abstract
{
    const STATUS_OPEN                     = 'open';
    const STATUS_SHIPPED                  = 'shipped';
    const STATUS_DIFFERENT_ARTICLE_STATES = 'different_article_states';
    const STATUS_COULD_NOT_SHIP           = 'could_not_ship';
    
    #private $_fileRegex = "#^Euro_(Best|Stor|Vers|Aend)_1719901-D_([0-9]{8}|[0-9]{18}).xml$#";
    /**
     * 
     */
    public function startProcess()
    {
        if (Mage::getStoreConfig('knm_orderprocessing/general/active') == 0) 
        {
            Mage::getSingleton('core/session')->addError('Orderprocessing module is disabled. Goto System->Configuration->General->Knm Orderprocessing and enable Orderprocessing.');
            return;
        }
        
        //download files if exist
        $downloader = Mage::getModel('orderprocessing/filedownloader');
        /* @var $downloader Knm_Orderprocessing_Model_Filedownloader */
        $downloader->downloadStatusReportsFromKmoServer();
        //import xmls to db
        $this->_importXmlsToDb();
        //process messages
        $this->processMessages();
        
        Mage::getSingleton('core/session')->addNotice('Open messages have been processed. Messages with errors are flagged as status = \'error\'. Exception log will give you more informations about the error.');
    }
    
    /**
     * Processes all open messages from messages table for further processing
     */
    public function processMessages()
    {
        //
        foreach ($this->processes as $name => $model)
        {
            $messages = Mage::getModel('orderprocessing/message')
                ->getCollection()
                ->addFieldToFilter('status_xml', Knm_Orderprocessing_Model_Message::OPEN)
                ->addFieldToFilter('message_type', $name)
                #->addFieldToFilter('id', 14)
            ;
            
            $processor = Mage::getModel($model);
            
            foreach ($messages as $message)
            {
                try 
                {
                    $processor->handleMessage($message);
                    $this->_setStatus($message);
                    $message->setStatusXml(Knm_Orderprocessing_Model_Message::COMPLETE);
                    $message->setProcessedAt(date('Y-m-d H:i:s', time()));
                    $message->save();
                }
                catch (Exception $e)
                {
                    #print_r($e);
                    //log $exception in message log
                    $message->setExceptionLog(
                        $message->getExceptionLog() . ' ' .
                        $e->getMessage() . ' ' .
                        $e->getTraceAsString() 
                    );
                    $message->setStatusXml(Knm_Orderprocessing_Model_Message::ERROR);
                    $message->save();
                    #die($e->getMessage());
                }
            }
        }
    }
    
    public function sendErrorLog()
    {
        $messages = Mage::getModel('orderprocessing/message')
            ->getCollection()
            ->addFieldToFilter('status_xml', Knm_Orderprocessing_Model_Message::ERROR)
        ;

        if (Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address') == '') return;
        if (sizeof($messages) == 0) return;

        $body = '';
        $body .= '<th>Created at</th>';
        $body .= '<th>Increment Id</th>';
        $body .= '<th>Customer Name</th>';
        $body .= '<th>Shipping Address</th>';
        $body .= '<th>KMO Order Id</th>';
        foreach ($orders as $order)
        {
            $shippingAddress = $order->getShippingAddress();
            $row  = '';
            $row .= '<td>' . $order->getCreatedAt() . '</td>';
            $row .= '<td>' . $order->getIncrementId() . '</td>';
            $row .= '<td>' . $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname() . '</td>';
            $row .= '<td>' . implode(' ', $shippingAddress->getStreet()) . ', ' . $shippingAddress->getPostcode() . ' ' . $shippingAddress->getCity() . '</td>';
            $row .= '<td>' . 'SPDE' . substr($order->getIncrementId(), strlen($order->getIncrementId())-6) . '</td>';
    
            $body .= '<tr>'.$row.'</tr>';
        }
        $body = '<table border="1">'.$body.'</table>';
        
        //create mail
        $mail = new Zend_Mail('utf-8');
        $mail->setSubject('Orderprocessing has found '.sizeof($messages).' message(s) that ended with status ERROR at store: '.Mage::getStoreConfig('general/store_information/name'));
        $mail->setBodyHtml($body);
        #$mail->setBodyText('Test');
        //add recipients
        $mail->setFrom('noreply-error@orderprocessing.de');
        $receiptients = explode(';', Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address'));
        foreach ($receiptients as $receiptient)
            $mail->addTo($receiptient);
        #$mail->addBcc();
        //send
        $mail->send();
    }
    
    public function sendErrorEmailSalesExportedTooOld()
    {
		$daysUntilWarning = 5;
	
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('status', 'exported')
            ->addFieldToFilter('TIMESTAMPDIFF(DAY , created_at, NOW())', array('gt' => $daysUntilWarning))
        #->addFieldToFilter()
        ;
        #die((string) $messages->getSelect() );

        if (Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address') == '') return;
        if (sizeof($orders) == 0) return;

        $body = '';
        $body .= '<th>Created at</th>';
        $body .= '<th>Increment Id</th>';
        $body .= '<th>Customer Name</th>';
        $body .= '<th>Shipping Address</th>';
        $body .= '<th>KMO Order Id</th>';
        foreach ($orders as $order)
        {
            $shippingAddress = $order->getShippingAddress();
            $row  = '';
            $row .= '<td>' . $order->getCreatedAt() . '</td>';
            $row .= '<td>' . $order->getIncrementId() . '</td>';
            $row .= '<td>' . $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname() . '</td>';
            $row .= '<td>' . implode(' ', $shippingAddress->getStreet()) . ', ' . $shippingAddress->getPostcode() . ' ' . $shippingAddress->getCity() . '</td>';
            $row .= '<td>' . 'SPDE' . substr($order->getIncrementId(), strlen($order->getIncrementId())-6) . '</td>';
    
            $body .= '<tr>'.$row.'</tr>';
        }
        $body = '<table border="1">'.$body.'</table>';
    
        //create mail
        $mail = new Zend_Mail('utf-8');
        $mail->setSubject('store: '.Mage::getStoreConfig('general/store_information/name'). ' | Total Count of '.sizeof($orders).' order(s) have status EXPORTED for more than ' . $daysUntilWarning . ' day(s). Check orderprocessing!');
        $mail->setBodyHtml($body);
        #$mail->setBodyText('Test');
        //add recipients
        $mail->setFrom('noreply-error@orderprocessing.de');
        $receiptients = explode(';', Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address'));
        foreach ($receiptients as $receiptient)
            $mail->addTo($receiptient);
        #$mail->addBcc();
        //send
        $mail->send();
    }
    
    public function sendErrorEmailSalesOpenTooOld()
    {
        $daysUntilWarning = 5;
    
        $orders = Mage::getModel('sales/order')
        ->getCollection()
        ->addFieldToFilter('status', 'open')
        ->addFieldToFilter('TIMESTAMPDIFF(DAY , created_at, NOW())', array('gt' => $daysUntilWarning))
        #->addFieldToFilter()
        ;
        #die((string) $messages->getSelect() );
    
        if (Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address') == '') return;
                if (sizeof($orders) == 0) return;
    
        $body = '';
        $body .= '<th>Created at</th>';
        $body .= '<th>Increment Id</th>';
        foreach ($orders as $order)
        {
            $row  = '';
            $row .= '<td>' . $order->getCreatedAt() . '</td>';
            $row .= '<td>' . $order->getIncrementId() . '</td>';

            $body .= '<tr>'.$row.'</tr>';
        }
        $body = '<table border="1">'.$body.'</table>';
    
        //create mail
        $mail = new Zend_Mail('utf-8');
        $mail->setSubject('store: '.Mage::getStoreConfig('general/store_information/name'). ' | Total Count of '.sizeof($orders).' order(s) have status OPEN for more than ' . $daysUntilWarning . ' day(s). Check orderprocessing!');
        $mail->setBodyHtml($body);
        #$mail->setBodyText('Test');
        //add recipients
        $mail->setFrom('noreply-error@orderprocessing.de');
        $receiptients = explode(';', Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address'));
        foreach ($receiptients as $receiptient)
            $mail->addTo($receiptient);
        #$mail->addBcc();
        //send
            $mail->send();
    }
    
    public function sendOrderDelayEmail()
    {
        $daysUntilWarning = 3;
    
        $orders = Mage::getModel('sales/order')
        ->getCollection()
        ->addFieldToFilter('status', 'exported')
        ->addFieldToFilter('TIMESTAMPDIFF(DAY , created_at, NOW())', $daysUntilWarning)
        #->addFieldToFilter()
        ;
        #die((string) $orders->getSelect() );
    
        if (Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address') == '') return;
                if (sizeof($orders) == 0) return;
    
                foreach ($orders as $order)
                {
    
                    $template = Mage::getModel('sales/email_template')->loadByCode('Order Delay');
                    $body = $template->getTemplateText();
                    $search = array(
                            '{{ name }}',
                    );

                    $replace = array(
                            $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                    );

                    $body = str_replace($search, $replace,$body);
                    $subject = str_replace($search, $replace,$template->getTemplateSubject());

                    $mail = new Zend_Mail('utf-8');
                    $mail->setSubject($subject);
                    $mail->setBodyHtml($body);
                    $mail->setFrom('service@faszinata.de', 'Faszinata.de');
                    $mail->addTo(''); //$order->getCustomerEmail());
                    $mail->addBcc('thomas.wiener.work@googlemail.com');
                    $mail->addBcc('j.salamah@k-newmedia.de');
                
                    $mail->send();
        
               }
    }
    
    protected function _setStatus(Knm_Orderprocessing_Model_Message $message)
    {
        //get order from message
        $order = $this->_getOrderByIncrementId($message->getShopOrderId());
        
        $qtyApproved     = 0;
        $qtyOrdered      = 0;
        $qtyInvoiced     = 0;
        $qtyShipped      = 0;
        $qtyRefunded     = 0;
        $qtyCanceled     = 0;
        
        $qtyBackordered  = 0;
        $qtyCouldNotShip = 0;
        
        foreach ($order->getAllVisibleItems() as $orderItem) 
        {
            $qtyOrdered      += $orderItem->getQtyOrdered();
            $qtyInvoiced     += $orderItem->getQtyInvoiced();
            $qtyShipped      += $orderItem->getQtyShipped();
            $qtyRefunded     += $orderItem->getQtyRefunded();
            $qtyCanceled     += $orderItem->getQtyCanceled();
            $qtyApproved     += $orderItem->getQtyKmoApproved();
            
            $qtyBackordered  += $orderItem->getQtyKmoBackordered();
            $qtyCouldNotShip += $orderItem->getQtyKmoCouldNotShip();
        }
        
        $stateNew  = '';
        $statusNew = '';
        
        //if items are partly approved, canceled, shipped => set different_article_states
        //TODO think it over again, seems wrong
        if(     ($qtyApproved     > 0 && $qtyApproved     != $qtyOrdered)
             || ($qtyCanceled     > 0 && $qtyCanceled     != $qtyOrdered)
             || ($qtyShipped      > 0 && $qtyShipped      != $qtyOrdered)
                
             || ($qtyBackordered  > 0 && $qtyBackordered  != $qtyOrdered)
             || ($qtyCouldNotShip > 0 && $qtyCouldNotShip != $qtyOrdered)
        ) 
        {
            #$stateNew  = Mage_Sales_Model_Order::STATE_PROCESSING;
            $statusNew = Knm_Orderprocessing_Model_Messageprocessor::STATUS_DIFFERENT_ARTICLE_STATES;
        }
        elseif ( $qtyRefunded == $qtyOrdered )  //refunded qty equals ordered qty => order is fully closed
        {
            #$stateNew  = Mage_Sales_Model_Order::STATE_CLOSED;
            $statusNew = Mage_Sales_Model_Order::STATE_CLOSED;
        }
        elseif ( $qtyShipped == $qtyOrdered ) //shipped qty equals ordered qty => order is fully shipped
        {
            #$stateNew  = Mage_Sales_Model_Order::STATE_COMPLETE;
            $statusNew = Mage_Sales_Model_Order::STATE_COMPLETE; //Knm_Orderprocessing_Model_Messageprocessor::STATUS_SHIPPED;
        }
        elseif ( $qtyCanceled == $qtyOrdered ) //canceled qty equals ordered qty => order is fully canceled
        {
            #$stateNew  = Mage_Sales_Model_Order::STATE_CANCELED;
            $statusNew = Mage_Sales_Model_Order::STATE_CANCELED;
        }
        elseif ( $qtyCouldNotShip == $qtyOrdered ) //could not ship qty equals ordered qty => no order item could not be shipped
        {
            #$stateNew  = Mage_Sales_Model_Order::STATE_CANCELED;
            $statusNew = Mage_Sales_Model_Order::STATUS_COULD_NOT_SHIP;
        }
        elseif( $qtyApproved == $qtyOrdered) { //set order to status open => ready for futher orderprocessing
            $statusNew = Knm_Orderprocessing_Model_Messageprocessor::STATUS_OPEN;
        }
        
        //set state, status and comment, if status has not changed
        if($statusNew != '') {
            //if status has not changed => return
            if ($order->getStatus() == $statusNew)
                return;
        
            // set log and new status
            $comment = $this->_getPrefixLog('NOTICE_LOG_PREFIX') . ': Old status was ' . $order->getStatus() . '. New Status is: ' . $statusNew;
            $order->addStatusHistoryComment($comment, $statusNew);
            #$order->setState($stateNew, $statusNew, $comment, $isCustomerNotified = false);
            $order->save();
        }
    }
    
    public function importXmls()
    {
        $this->_importXmlsToDb();
    }
    
    protected function _importXmlsToDb() 
    {
        $directoryPathNew       = $this->_getDirectoryNew();
        $directoryPathProcessed = $this->_getDirectoryProcessed();
        $directoryPathError     = $this->_getDirectoryError();
        
        $fileRegex              = Mage::getStoreConfig('knm_orderprocessing/general/fileregex');
        
        //open directory new
        $dirHandle = opendir($directoryPathNew);
    
        //is directory accessible
        if($dirHandle !== false) {
            //read directory content
            while(($filename = readdir($dirHandle)) !== false) {
                $filenamePath = $directoryPathNew . $filename;
                
                //is current directory index a file
                if(is_file($filenamePath) === true) {
                    //does filename match given pattern, if pattern exists
                    if($fileRegex == '' || preg_match($fileRegex, $filename) == 1) 
                    {
                        try
                        {
                            $oXml = simplexml_load_file($filenamePath);
                            $model = Mage::getModel('orderprocessing/observer');
                            $model->processXml($oXml, $filename);
        
                            #unlink($filenamePath);
                            //move file to processed folder
                            rename($filenamePath, $directoryPathProcessed . $filename);
                        }
                        catch (Exception $e)
                        {
                            //log exception
                            Mage::log(
                                $this->_getPrefixLog('ERROR_LOG_PREFIX') . ': Problems importing file: ' . $filename . ' | Exception: ' . $e->getMessage(),  
                                null, 
                                'orderprocessing.log'
                            );
                            rename($filenamePath, $directoryPathError . $filename);
                        }
                    } 
                    else 
                    {
                        Mage::log(
                            $this->_getPrefixLog('ERROR_LOG_PREFIX') . ': Problems importing file: ' . $filename . ' | Filename does not match given pattern.',
                            null,
                            'orderprocessing.log'
                                    
                        );
                        //move file to error folder for further investigation
                        rename($filenamePath, $directoryPathError . $filename);
                        //log

                       # $this->_handleFileError($directoryPathNew, $filename);
                    }
                }
            }
            //close directory
            closedir($dirHandle);
        }
    }
    
    public function correctFiles()
    {
        $find = array(
            '012-0907110-5070966' => '000-0000010-0009241',
            '012-0908150-0025310' => '000-0000010-0009249',
			'012-0907163-5489732' => '000-0000010-0009244',
			'012-0909140-0020514' => '000-0000010-0009253',
			'012-0908120-0016852' => '000-0000010-0009246',
			'012-0908150-0021117' => '000-0000010-0009248',
			'012-0908220-0019365' => '000-0000010-0009251',
            '012-0910110-0020557' => '000-0000010-0009254',
        ); 

        
        $directoryPathNew       = $this->_getDirectoryNew();
        $directoryPathProcessed = $this->_getDirectoryProcessed();
        $directoryPathError     = $this->_getDirectoryError();
    
        $fileRegex              = Mage::getStoreConfig('knm_orderprocessing/general/fileregex');
    
        //open directory new
        $dirHandle = opendir($directoryPathNew);
    
        //is directory accessible
        if($dirHandle !== false) {
            //read directory content
            while(($filename = readdir($dirHandle)) !== false) {
                $filenamePath = $directoryPathNew . $filename;
    
                //is current directory index a file
                if(is_file($filenamePath) === true) {
                    //does filename match given pattern, if pattern exists
                    //open file and get data
                    $data = file_get_contents($filenamePath);
                    // do tag replacements or whatever you want
                    foreach ($find as $key => $value)
                    {
                        $data = str_replace($key, $value, $data);
                    }
                    //save it back:
                    file_put_contents($filenamePath, $data);
                                        
                }
            }
        //close directory
        closedir($dirHandle);
        }
    }
    
    //TODO refactor
    protected function _hasLockedFulfillments($message)
    {
        $operationName = $message->getMessageType();
        
        if($operationName) {
            $incrementId = $message->getShopOrderId();
            $order = $this->_getOrderByIncrementId($message->getShopOrderId());
            if($order && $order->getId() !== null) {
                $payment = $order->getPayment();
                if($payment && $payment->getData('method') == 'computop_cc') 
                {
                    $maxTimestamp = 0;
                    foreach ($order->getInvoiceCollection() as $invoice) 
                    {
                        $createdAt = $invoice->getData('created_at');
                        $createdAt = strtotime($createdAt);
                        if($createdAt > $iMaxTimestamp) {
                            $maxTimestamp = $createdAt;
                        }
                    }
                    $now = time();
                    $diff = $now - $iMaxTimestamp;
                    if($diff < (getMinutesBetweenCreditcardCaptures() * 60)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}