<?php

require_once BP.DS.'lib'.DS.'Orderprocessing'.DS.'Net'.DS.'SFTP.php';

class Knm_Orderprocessing_Model_Filedownloader extends Knm_Orderprocessing_Model_Abstract
{
    /**
    * function downloadStatusReportsFromKmoServer
    *
    * Download status reports from kmo servers, save to new directory for further processing
    *
    * @author Thomas Wiener
    * @return boolean
    */
    public function downloadStatusReportsFromKmoServer()
    {
        $blSuccess = false;
        $blDownloaded = false;

        $isFileDownloadActive = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/active');
        $sftpHostName         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/host');
        $sftpUsername         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/username');
        $sftpPassword         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/password');
        $remotePath           = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/remote_path');
        $liveMode             = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/livemode');

        if (!$isFileDownloadActive)
            return;

        //init SFTP object
        $sftp = new Net_SFTP($sftpHostName);
        if ($sftp->login($sftpUsername, $sftpPassword) !== false) {
            //change directory
            $sftp->chdir($remotePath);
            //get directory content
            $aFiles = $sftp->nlist();
            //write filtered directory content into array
            $aFilesToCheck = array();
            if(isset($aFiles) && is_array($aFiles) && count($aFiles) > 0) {
                foreach ($aFiles as $sFilename) {
                    if($sFilename != '.' && $sFilename != '..' && $sFilename != 'archiv') {
                        $aFilesToCheck[] = $sFilename;
                    }
                }
            }
            //handle directory content
            foreach ($aFilesToCheck as $sFilename)
            {
                $aExpl = explode('.', $sFilename);

                //is filetype = xml
                if(isset($aExpl) && is_array($aExpl) && count($aExpl) > 0 && $aExpl[count($aExpl)-1] == 'xml')
                {
                    //is the end file for xml file exists
                    $sEndFile = substr($sFilename, 0, strlen($sFilename)-4).'.end';
                    if(array_search($sEndFile, $aFilesToCheck) !== false) {

                        //download file to folder downloaded
                        $sDownloadPath = $this->_getDirectoryNew() . $sFilename;
                        $blSuccess = $sftp->get($sFilename, $sDownloadPath);
                        //$blSuccess2 = $sftp->get($sFilename, '/nfsmount/www/faszinata/de/kmo-bestellabwicklung/xmls/1_new/' . $sFilename);
                        //$blSuccess2 = $sftp->get($sFilename, '/var/www/vhosts/faszinata-reloaded/src/shared/messages/new/' . $sFilename);
                        //was download successfull
                        if($blSuccess === true && file_exists($sDownloadPath) !== false)
                        {
                            //copy file to folder new
                            #copy($sDownloadPath, $this->_sFolderPathXmls.processBase::FOLDER_NEW.$sFilename);
                            if($liveMode == true)
                            {
                                //move files on KMO server to archiv folder
                                $sftp->rename($sFilename, 'archiv/'.$sFilename);
                                $sftp->rename($sEndFile, 'archiv/'.$sEndFile);
                            }
                            $blDownloaded = true;
                        }
                        else
                        {
                            //download was not successful
                            Mage::log($this->_getPrefixLog('ERROR_LOG_PREFIX') . ': ' . 'Error beim Runterladen der XMLs: ' . $sFilename);
                            //$this->log(1001, serialize(array('request' => 'Error beim Runterladen der XMLs', 'file' => $sFilename)), 'XmlFileDownload');
                        }
                    }
                }
            }
            //disconnect from KMO server
            $sftp->disconnect();
            return $blDownloaded;
        } else {

            $sftpErrors = implode('; ', $sftp->getErrors());
            Mage::log('Verbindung zum SFTP-Server nicht möglich; Error Message: '. $sftpErrors);
            //$this->log(1070, serialize(array('request' => 'Verbindung zum SFTP-Server nicht möglich; Error Message: '. $sftpErrors )), 'XmlFileDownload');
            return false;
        }
    }

    public function checkDownload()
    {
        $blSuccess = false;
        $blDownloaded = false;

        $isFileDownloadActive = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/active');
        $sftpHostName         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/host');
        $sftpUsername         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/username');
        $sftpPassword         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/password');
        $remotePath           = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/remote_path');
        $liveMode             = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/livemode');

        if (!$isFileDownloadActive)
            return;

        //init SFTP object
        $sftp = new Net_SFTP($sftpHostName);
        if ($sftp->login($sftpUsername, $sftpPassword) !== false) {
            //change directory
            $sftp->chdir($remotePath . '/archiv');
            //get directory content
            $aFiles = $sftp->nlist();
            //write filtered directory content into array
            $aFilesToCheck = array();
            if(isset($aFiles) && is_array($aFiles) && count($aFiles) > 0) {
                foreach ($aFiles as $sFilename) {
                    if($sFilename != '.' && $sFilename != '..' && $sFilename != 'archiv') {
                        $aFilesToCheck[] = $sFilename;
                    }
                }
            }
            $count = 0;
            //handle directory content
            foreach ($aFilesToCheck as $sFilename)
            {
                $aExpl = explode('.', $sFilename);

                //is filetype = xml
                if(isset($aExpl) && is_array($aExpl) && count($aExpl) > 0 && $aExpl[count($aExpl)-1] == 'xml')
                {
                    //is the end file for xml file exists
                    #$sEndFile = substr($sFilename, 0, strlen($sFilename)-4).'.end';
                    #if(array_search($sEndFile, $aFilesToCheck) !== false) {
                    $isTodaysFile = strpos($sFilename, date('Ymd'));
                    if ($isTodaysFile == true) {
                        $count++;
                        //download file to folder downloaded
                        $sDownloadPath = '/srv/www/faszinata/htdocs-reloaded/shared/messages/archive/' . $sFilename;
                        $blSuccess = $sftp->get($sFilename, $sDownloadPath);
                        //$blSuccess2 = $sftp->get($sFilename, '/nfsmount/www/faszinata/de/kmo-bestellabwicklung/xmls/1_new/' . $sFilename);
                        //$blSuccess2 = $sftp->get($sFilename, '/var/www/vhosts/faszinata-reloaded/src/shared/messages/new/' . $sFilename);
                        //was download successfull
                        if($blSuccess === true && file_exists($sDownloadPath) !== false)
                        {

                        }
                        else
                        {
                            echo "wrong";
                            //download was not successful
                            Mage::log($this->_getPrefixLog('ERROR_LOG_PREFIX') . ': ' . 'Error beim Runterladen der XMLs: ' . $sFilename);
                            //$this->log(1001, serialize(array('request' => 'Error beim Runterladen der XMLs', 'file' => $sFilename)), 'XmlFileDownload');
                        }
                    }
                }
            }
            //disconnect from KMO server
            $sftp->disconnect();

            if ($count == $this->getTodaysFilesCount()) {
                echo "count is correct";
            } else {

                //create mail
                $mail = new Zend_Mail('utf-8');
                $mail->setSubject('store: '.Mage::getStoreConfig('general/store_information/name') . ' | XML Import Warning');
                $mail->setBodyHtml('We found ' . $count . ' xml files on the kmo server, however ' . $this->getTodaysFilesCount() . ' were found on integration server. Check the mismatch on sftp.klingel.de/data/kmodata/in and on integration server messages/archive');
                //add recipients
                $mail->setFrom('noreply-error@orderprocessing.de');
                $receiptient = 'thomas.wiener.work@googlemail.com'; #explode(';', Mage::getStoreConfig('knm_orderprocessing/orderprocessing_logging/error_email_address'));
                #foreach ($receiptients as $receiptient)
                    $mail->addTo($receiptient);
                //send
                $mail->send();
            }

            return $blDownloaded;
        } else {

            $sftpErrors = implode('; ', $sftp->getErrors());
            Mage::log('Verbindung zum SFTP-Server nicht möglich; Error Message: '. $sftpErrors);
            //$this->log(1070, serialize(array('request' => 'Verbindung zum SFTP-Server nicht möglich; Error Message: '. $sftpErrors )), 'XmlFileDownload');
            return false;
        }
    }

    public function getTodaysFilesCount()
    {
        $count = 0;
        if ($handle = opendir('/srv/www/faszinata/htdocs-reloaded/shared/messages/processed')) {
            while (false !== ($entry = readdir($handle))) {
                $code = array('WZ','DI','KL');
                $skip = false;
                foreach ($code as $c) {
                    if (strpos($entry, $c)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip === true) continue;

                if (strpos($entry, date('Ymd'))) {
                    if ($isTodaysFile = strpos($entry, date('Ymd'))) {
                        $count++;
                    }
                }
            }
            closedir($handle);
        }
        return $count;
    }

    public function uploadFile($fileName, $clearDirectory = true)
    {
        $sCreatedFilePath = Knm_Orderprocessing_Model_SettlementReport::PATH . $fileName;

        $isFileDownloadActive = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/active');
        $sftpHostName         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/host');
        $sftpUsername         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/username');
        $sftpPassword         = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/password');
        $remotePath           = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/remote_path');
        $liveMode             = Mage::getStoreConfig('knm_orderprocessing/orderprocessing_file_download_server/livemode');


        $blUploaded = false;

        //init SFTP object
        $oSftp = new Net_SFTP($sftpHostName);

        //login to KMO Server
        if ($oSftp->login($sftpUsername, $sftpPassword) !== false) {
            //change directory
            $oSftp->chdir('data/kmodata/out');

            //get directory content
            $aFiles = $oSftp->nlist();

            //write filtered directory content into array
            $aFilesToCheck = array();
            if ($clearDirectory === true)
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
            #$this->log(1070, 'Verbindung zum SFTP-Server nicht m�glich', 'SettlementReport');
        }


    }

}