<?php

class Knm_Orderprocessing_Adminhtml_MessageController extends Mage_Adminhtml_Controller_action
{

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('orderprocessing/items')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
        
        return $this;
    }   
 
    public function indexAction() {
        $this->_initAction()
            ->renderLayout();
    }

    public function editAction() {
//         $id     = $this->getRequest()->getParam('id');
//         $model  = Mage::getModel('orderprocessing/message')->load($id);

//         if ($model->getId() || $id == 0) {
//             $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
//             if (!empty($data)) {
//                 $model->setData($data);
//             }
            
//             Mage::register('message_data', $model);

            $this->loadLayout();
//             $this->_setActiveMenu('orderprocessing/message');

//             $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
//             $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

//             $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

//             #$this->_addContent($this->getLayout()->createBlock('orderprocessing/adminhtml_message_edit'))
//             #    ->_addLeft($this->getLayout()->createBlock('orderprocessing/adminhtml_message_edit_tabs'));

            $this->renderLayout();
//         } else {
//             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('orderprocessing')->__('Item does not exist'));
//             $this->_redirect('*/*/');
//         }
    }
 
    public function newAction() {
        $this->_forward('edit');
    }
 
    public function saveAction() {
        if ($data = $this->getRequest()->getPost()) {
            
                  
            $model = Mage::getModel('orderprocessing/message');        
            $model->setData($data)
                ->setId($this->getRequest()->getParam('id'));
           
            try {
                if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
                    $model->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $model->setUpdateTime(now());
                }    
                
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('orderprocessing')->__('Item was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('orderprocessing')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
    }
 
    public function deleteAction() {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $model = Mage::getModel('orderprocessing/orderprocessing');
                 
                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                     
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $orderprocessingIds = $this->getRequest()->getParam('orderprocessing');
        if(!is_array($orderprocessingIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($orderprocessingIds as $orderprocessingId) {
                    $orderprocessing = Mage::getModel('orderprocessing/orderprocessing')->load($orderprocessingId);
                    $orderprocessing->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($orderprocessingIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
    
    public function massStatusAction()
    {
        $orderprocessingIds = $this->getRequest()->getParam('orderprocessing');
        if(!is_array($orderprocessingIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($orderprocessingIds as $orderprocessingId) {
                    $orderprocessing = Mage::getSingleton('orderprocessing/orderprocessing')
                        ->load($orderprocessingId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($orderprocessingIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
  
    public function exportCsvAction()
    {
        $fileName   = 'orderprocessing.csv';
        $content    = $this->getLayout()->createBlock('orderprocessing/adminhtml_orderprocessing_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'orderprocessing.xml';
        $content    = $this->getLayout()->createBlock('orderprocessing/adminhtml_orderprocessing_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
    
    public function processMessagesAction()
    {
        $messageProcessor = Mage::getModel('orderprocessing/messageprocessor');
        $messageProcessor->startProcess();
        
        Mage::getSingleton('core/session')->addNotice('Open messages have been processed. Messages with errors are flagged as status = \'error\'. Exception log will give you more informations about the error.');
        
        $this->_redirect('*/adminhtml_message/index');
    }
}