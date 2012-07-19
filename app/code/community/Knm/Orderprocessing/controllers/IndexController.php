<?php

class Knm_Orderprocessing_IndexController extends Mage_Core_Controller_Front_Action
{
    
    public function indexAction()
    {
        $sPath = '/var/www/vhosts/xml-creator/'; 
        $sFilename = 'Euro_Aend_1719901-D_201206081339137182.xml';
        
        $oXml = simplexml_load_file($sPath . $sFilename);
        $model = Mage::getModel('orderprocessing/observer');
        $model->processXml($oXml, $sFilename);
        
        die('ok');
        
        #die('test');
        #$model = Mage::getModel('orderprocessing/observer');
        #$model->start();
        #die('done');
        
        $model = Mage::getModel('orderprocessing/observer');
        $model->downloadStatusReportsFromKmoServer();
        die('done');
    }
    
    public function srAction()
    {
        $datetimeStart = '2012-03-21 00:00:00';
        $datetimeEnd   = '2012-03-27 23:59:59';
        
        $params = $this->getRequest()->getParams();
        
        #$datetimeStart = str_replace('T',' ',$params['datetimeStart']);
        #$datetimeEnd   = str_replace('T',' ',$params['datetimeEnd']);
        
        $model = Mage::getModel('orderprocessing/settlementreport');
        
        $model->_generatePartnerReport('Klingel', $datetimeStart, $datetimeEnd);
        $model->_generatePartnerReport('Wenz', $datetimeStart, $datetimeEnd);
        $model->_generatePartnerReport('Diemer', $datetimeStart, $datetimeEnd);
        
        die('done');
    }
    
    public function stAction()
    {
        $messageModel = Mage::getModel('orderprocessing/observer');
        
        $fileNames = array(
                'Euro_Vers_WZ-D_20120403.xml',
                #'Euro_Vers_KL-D_20111218.xml',
                #'Euro_Aend_WZ-D_20120215.xml'
        );
        
        foreach($fileNames as $fileName)
        {
            $xml = simplexml_load_file('/var/www/vhosts/'.$fileName);
            
            foreach ($xml->Message as $messageXml)
            {
                
                $messageModel->updateMessage($xml->MessageType, $xml, $messageXml, $fileName);
            }
        }
        
    }
    
    public function mpAction()
    {
        $model = Mage::getModel('orderprocessing/messageprocessor');
        $model->processMessages();
    }
    
    public function coAction()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        // now $write is an instance of Zend_Db_Adapter_Abstract
        $write->query("truncate knm_message; truncate knm_message_item;");
        
        $model = Mage::getModel('orderprocessing/test_test');
        $order = $model->startTest(); #createOrder();
        
        $write->query("update knm_message set status_xml = 'error' where message_type = 'OrderAdjustment';");
        
        #die($order->getIncrementId());
    }
    
    public function mp2Action()
    {
        $model = Mage::getModel('orderprocessing/test_test');
        $order = $model->startMPTest(); #createOrder();
        #die($order->getIncrementId());
    }
    
    /*
    public function uploadFile($sCreatedFilePath)
    {
        $blUploaded = false;
    
        //init SFTP object
        $oSftp = new Net_SFTP(self::KMO_HOST);
    
        //login to KMO Server
        if ($oSftp->login(self::KMO_USER, self::KMO_PASS) !== false) {
            //change directory
            $oSftp->chdir('data/kmodata/out');
    
            //get directory content
            $aFiles = $oSftp->nlist();
    
            //write filtered directory content into array
            $aFilesToCheck = array();
            if(isset($aFiles) && is_array($aFiles) && count($aFiles) > 0) {
                foreach ($aFiles as $sFilename) {
                    if($sFilename != '.' && $sFilename != '..' && $sFilename != 'archiv') {
                        //move files on KMO server to archiv folder
                        $oSftp->rename($sFilename, 'archiv/'.$sFilename);
                    }
                }
            }
    
            $blUploaded = $oSftp->put(basename($sCreatedFilePath), $sCreatedFilePath, NET_SFTP_LOCAL_FILE);
    
            //disconnect from KMO server
            $oSftp->disconnect();
            return $blUploaded;
        } else {
            $this->log(1070, 'Verbindung zum SFTP-Server nicht m�glich', 'SettlementReport');
        }
    }
    */
    
    public function ipnAction()
    {
        $handle = fopen('/srv/www/meinestrolche.de/htdocs/var/log/test.txt', "a");
        fwrite($handle, serialize($this->getRequest()->getPost()) . "\n");
        fclose($handle);
    }
}