<?php 

class Knm_Orderprocessing_Model_Test_Orderfactory extends Knm_Orderprocessing_Model_Abstract
{
 
    const COUPON_CODE = 'cheaper';
    
    protected $_quote;
    protected $_order;
    protected $_store;
    protected $_customer;
    protected $_productCollection;
    protected static $_storeCollection;
    protected static $_customerCollection;
 
    public function createOrder() {
        $customer = $this->_getCustomer();
    
        $this->_quote
            ->setStore($this->_getStore())
            ->setCustomer($customer)
            ->setCustomerIsGuest(0)
        ;
        $this->_quote
            ->getBillingAddress()
            ->importCustomerAddress($customer->getDefaultBillingAddress())
        ;
        $this->_quote
            ->getShippingAddress()
            ->importCustomerAddress($customer->getDefaultShippingAddress())
        ;
    
        $productCount = rand(2, 4);
        for ($i = 0; $i < $productCount; $i++) {
            $product = $this->_getRandomProduct();
            if ($product) {
                $product->setQuoteQty(1);
    
                /*$stockData = $product->getStockData();
                 if (!$stockData) {
                $product = $product->load($product->getId());
                $stockData = array(
                        'manage_stock' => 1,
                        'is_in_stock' => 1,
                        'qty' => 1
                );
    
                $product->setStockData($stockData);
                $product->save();
                }*/
                $this->_quote->addProduct($product);
            }
        }
    
        $this->_quote
            ->getPayment()
            ->setMethod('free') #checkmo
        ;
        $this->_quote
            ->getShippingAddress()
            ->setShippingMethod('flatrate_flatrate') //tablerate_bestway //flatrate_flatrate
        ; //->collectTotals()->save();
        $this->_quote
            ->getShippingAddress()
            ->setCollectShippingRates(true)
        ;
        $this->_quote
            ->setCouponCode(Knm_Orderprocessing_Model_Test_Orderfactory::COUPON_CODE)
            ->collectTotals()
            ->save()
        ;
        $this->_quote->save();
    
        $service = Mage::getModel('sales/service_quote', $this->_quote);
        $service->submitAll();
    
        $order = $service->getOrder();
        $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': PERFORMING TEST RUN | <br/> Knm_Orderprocessing_Model_Test_Orderfactory contains further information to current test case.');
        $order->addStatusHistoryComment(Knm_Orderprocessing_Model_Abstract::NOTICE_LOG_PREFIX . ': Order was automatically and successfully created.', 'Test');
        $order->save();
    
        return $order;
    
        /*
         $rand = rand(1, 4);
    
        switch ($rand) {
        case 1:
        $this->invoiceOrder($order);
        break;
        case 2:
        $this->shipOrder($order);
        break;
        case 3:
        $this->invoiceOrder($order);
        $this->shipOrder($order);
        break;
        default:
        break;
        }
    
        return $this;
        */
    }
    
    public function __construct() {
        $this->_quote = Mage::getModel('sales/quote')->save();
        $this->_order = Mage::getModel('sales/order');
    }
 
    protected function _getStores() {
        if (!self::$_storeCollection) {
            self::$_storeCollection = Mage::getResourceModel('core/store_collection')
                    ->load();
        }
        return self::$_storeCollection->getItems();
    }
 
    protected function _getCustomers() {
        if (!self::$_customerCollection) {
            self::$_customerCollection = Mage::getResourceModel('customer/customer_collection')
                    ->addAttributeToSelect('*')
                    #->addAttributeToFilter('email', 'thomas.wiener.work@googlemail.com')
                    ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'inner')
                    ->joinAttribute('shipping_country_id', 'customer_address/country_id', 'default_shipping', null, 'inner')
                    ->load();
        }
 
        return self::$_customerCollection->getItems();
    }
 
    protected function _getProducts() {
        if (!$this->_productCollection) {
            $this->_productCollection = Mage::getResourceModel('catalog/product_collection');
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($this->_productCollection);
            $this->_productCollection->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
                    ->load();
        }
 
        return $this->_productCollection->getItems();
    }
 
    protected function _getCustomer() 
    {
        if (!$this->_customer) {
            $items = $this->_getCustomers();
            $randKey = array_rand($items);
            $this->_customer = $items[$randKey];
        }
        return $this->_customer;
    }
 
    protected function _getRandomProduct() 
    {
        return Mage::getModel('catalog/product')->load(1056);
        
        $items = $this->_getProducts();
        $randKey = array_rand($items);
        $product = $items[$randKey];
        $product = Mage::getModel('catalog/product')->load($product->getId());
        return $product;
    
    return isset($items[$randKey]) ? $items[$randKey] : false;
    }
    
    /*protected function _getRandomProduct() {
        $items = $this->_getProducts();
        $randKey = array_rand($items);
        return isset($items[$randKey]) ? $items[$randKey] : false;
    }*/
 
    protected function _getStore() {
        if (!$this->_store) {
            $items = $this->_getStores();
            $randKey = array_rand($items);
            $this->_store = $items[$randKey];
        }
        return $this->_store;
    }
 
    protected function invoiceOrder($order) {
 
        try {
 
            if (!$order->canInvoice()) {
                $order->addStatusHistoryComment('Inchoo_Invoicer: Order cannot be invoiced.', false);
                $order->save();
            }
 
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
 
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();
 
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $order->addStatusHistoryComment('Automatically INVOICED by Inchoo_Invoicer.', false);
 
            $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
 
            $transactionSave->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
 
    protected function shipOrder($order) {
 
        try {
            $shipment = $order->prepareShipment();
            $shipment->register();
 
            $order->setIsInProcess(true);
            $order->addStatusHistoryComment('Automatically SHIPPED by Inchoo_Invoicer.', false);
 
            $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
 
}