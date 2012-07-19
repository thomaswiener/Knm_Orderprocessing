<?php 

//load external sftp library1
#require_once BP.DS.'lib'.DS.'Orderprocessing'.DS.'Net'.DS.'SFTP.php';

class Knm_Orderprocessing_Model_Observer extends Knm_Orderprocessing_Model_Abstract
{
    public function processStatusReports()
    {
        //get directory with files
        $oDirHandle = opendir( $this->_getDirectoryNew() );
        //check if handle is valid
        if($oDirHandle !== false) {
            //get file from directory
            while(($filename = readdir($oDirHandle)) !== false) {
                //create xml object from file
                $oXml = simplexml_load_file($this->_getDirectoryNew() . $filename);
                //check if xml object is valid
                if($oXml === false) //xml is not valid, log and continue
                {
                    //Mage::log
                    continue;
                }
                //process xml file
                $this->processXml($oXml, $filename);
            }
        }
        //close handle
        closedir($oDirHandle);
    }
    
    public function processXml(SimpleXMLElement $xml, $fileName)  //simple xml element
    {
        try 
        {
            if ($this->hasFileBeenProcessedBefore($fileName))
                return false;
            
            $messageType = $xml->MessageType;
            
            $itemElementName = 'Item';
            if ((string) $messageType == 'OrderAdjustment')
                $itemElementName = 'AdjustedItem';
            
            foreach ($xml->Message as $messageXml) 
            {
                $message = Mage::getModel('orderprocessing/message');
                
                #if ($message->hasMessageBeenProcessedBefore($messageXml, $messageType, $xml->Header->MerchantIdentifier))
                #    continue;
                
                $message->addMessage($messageXml, $xml->MessageType, $xml->Header->MerchantIdentifier, $fileName);
                foreach ($messageXml->$messageType->$itemElementName as $itemXml) {
                    
                    $item = Mage::getModel('orderprocessing/item');
                    
                    if ($item->hasItemBeenProcessedBefore($itemXml, $messageType))
                        continue;
                    
                    $item->addItem($itemXml, $xml->MessageType, $message->getId(), $message->getShopOrderId(), $message->getMerchantIdentifier());
                }
            
            }
            return true;
        } 
        catch (Exception $e)
        {
            //log the exception
        }
    }
    
    public function updateMessage($messageType, $xml, $xmlMessage, $fileName)
    {
        $messageModel = Mage::getModel('orderprocessing/message');
        
        $collection = $messageModel
            ->getCollection()
            ->addFieldToFilter('message_type'             , $messageType)
            ->addFieldToFilter('merchant_identifier'      , $xml->Header->MerchantIdentifier)
            ->addFieldToFilter('file_name_xml'            , $fileName)
        ;
        
        switch ($messageType)
        {
            case $messageModel::ORDERACKNOWLEDGEMENT:
                $collection->addFieldToFilter('shop_order_id', $xmlMessage->OrderAcknowledgement->ShopOrderID)
                ;
                break;
            case $messageModel::ORDERFULFILLMENT:
                $collection->addFieldToFilter('shop_order_id', $xmlMessage->OrderFulfillment->ShopOrderID)
                ;
                break;
            case $messageModel::ORDERADJUSTMENT:
                $collection->addFieldToFilter('shop_order_id', $xmlMessage->OrderAdjustment->ShopOrderID)
                ;
                break;
            default:
                return;
        }
        $message = null;
        $message = $collection->getFirstItem();
        
        if (!$message)
        {
		echo "message is empty";
		return;
		}
		echo "message is valid: Message id: " . $message->getId();
        $message->updateStatus();
        
    }
    
    private function hasFileBeenProcessedBefore($fileName)
    {
        $message = Mage::getModel('orderprocessing/message');
        $collection = $message->getCollection()->addFieldToFilter('file_name_xml', $fileName);
        if (count($collection) == 0)
            return false;
        return true;
    }
    
    private function getDateStart()
    {
        $wednesday = 3;
        $today     = time(); #strtotime('2012-04-10'); #time();
        $weekDay   = date('N', $today);
        $shift     = $wednesday - $weekDay;
        $today     = date('Y-m-d', $today);
        $startDate = date('Y-m-d', strtotime($today) + (60 * 60 * 24 * $shift));
        $startDate = date('Y-m-d', strtotime(date("Y-m-d", strtotime($startDate)) . " -1 week"));
        if ($weekDay < $wednesday)
            $startDate = date('Y-m-d', strtotime(date("Y-m-d", strtotime($startDate)) . " -1 week"));
        $startDate = $startDate . ' ' . '00:00:00';
        return $startDate;
    }
    
    private function getDateEnd()
    {
        $startDate = $this->getDateStart();
        $endDate = date('Y-m-d H:i:s', strtotime(date("Y-m-d", strtotime($startDate)) . " +1 week") - 1); //plus 1 week minus 1 second
        return $endDate;
    }
    
    public function createSettlementReport()
    {
        $datetimeStart = $this->getDateStart();
        $datetimeEnd   = $this->getDateEnd();
        
        $model = Mage::getModel('orderprocessing/settlementreport');
        
        $model->_generatePartnerReport('Klingel', $datetimeStart, $datetimeEnd);
        $model->_generatePartnerReport('Wenz', $datetimeStart, $datetimeEnd);
        $model->_generatePartnerReport('Diemer', $datetimeStart, $datetimeEnd);
    }
    
    
    
    public function processStatusReportsTest()
    {
    
        #$model = Mage::getModel('orderprocessing/message');
    
        $fileNames = array(
                'Euro_Vers_KL-D_20110622.xml',
                'Euro_Vers_KL-D_20110817.xml',
                'Euro_Best_615-D_201109061315294388.xml',
        );
    
        foreach($fileNames as $fileName)
        {
            $xml = simplexml_load_file('/var/www/vhosts/'.$fileName);
            $this->processXml($xml, $fileName);
        }
    }
    
    
}