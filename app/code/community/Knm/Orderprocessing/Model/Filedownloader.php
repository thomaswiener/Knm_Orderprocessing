<?php

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
                            Mage::log(Knm_Orderprocessing_Model_Abstract::ERROR_LOG_PREFIX . ': ' . 'Error beim Runterladen der XMLs: ' . $sFilename);
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

}