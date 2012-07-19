<?php 

class Knm_Orderprocessing_Model_Test_Test extends Knm_Orderprocessing_Model_Abstract
{
    protected $_sFileRegex = "#^Euro_(Best|Stor|Vers|Aend)_1719901-D_([0-9]{8}|[0-9]{18}).xml$#";
    
    public function startTest()
    {
        $orderFactory     = Mage::getModel('orderprocessing/test_orderfactory');
        $xmlFactory       = Mage::getModel('orderprocessing/test_xmlfactory');
        $messageProcessor = Mage::getModel('orderprocessing/messageprocessor');
        
        #die('test');
        //create new order
        $order = $orderFactory->createOrder(); 
        //create xml files
        #$order = Mage::getModel('sales/order')->load(38);
        $xmlFactory->createXml($order);
        //write xmls to db
        $this->_writeXmlsToDb();
        //processMessages
        #$messageProcessor->processMessages();
        
        #die('all done');
    }
    
    public function startMPTest()
    {
        $orderFactory     = Mage::getModel('orderprocessing/test_orderfactory');
        $xmlFactory       = Mage::getModel('orderprocessing/test_xmlfactory');
        $messageProcessor = Mage::getModel('orderprocessing/messageprocessor');
    
        #die('test');
        //create new order
        #$order = $orderFactory->createOrder();
        //create xml files
        #$order = Mage::getModel('sales/order')->load(3);
        #$xmlFactory->createXml($order);
        //write xmls to db
        #$this->_writeXmlsToDb();
        //processMessages
        $messageProcessor->processMessages();
    
        die('all done');
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
                    if(preg_match($this->_sFileRegex, $sFilename) == 1) {
                        
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