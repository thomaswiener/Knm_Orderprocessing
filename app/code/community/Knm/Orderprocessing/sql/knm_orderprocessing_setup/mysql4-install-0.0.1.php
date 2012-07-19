<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('knm_message')};
    
    CREATE TABLE {$this->getTable('knm_message')} (
        `id` INT NOT NULL auto_increment,
        `message_type` SET('OrderAcknowledgement','OrderFulfillment','OrderAdjustment') NULL ,
        `merchant_identifier` VARCHAR(40) NULL ,
        `shop_order_id` VARCHAR(20) NULL ,
        `status_code` VARCHAR(45) NULL ,
        `merchant_order_id` VARCHAR(45) NULL ,
        `merchant_fulfillment_id` INT NULL ,
        `fulfillment_date` DATETIME NULL ,
        `carrier_code` VARCHAR(10) NULL ,
        `shipping_method` VARCHAR(15) NULL ,
        `shipper_tracking_number` VARCHAR(20) NULL ,
        `status_xml` SET('open','complete','error') NULL ,
        `status_settlement_report` SET('open','complete') NULL ,
        `file_name_xml` VARCHAR(50) NULL ,
        `file_name_settlement_report` VARCHAR(50) NULL ,
        `exception_log` TEXT NULL ,
        `created_at` DATETIME NULL ,
        `processed_at` DATETIME NULL ,
        `exported_at` DATETIME NULL ,
        PRIMARY KEY (`id`) )
        ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;
");

$installer->run("
        DROP TABLE IF EXISTS {$this->getTable('knm_message_item')};

        CREATE TABLE {$this->getTable('knm_message_item')} (
        `id` INT NOT NULL auto_increment,
        `knm_message_id` INT NOT NULL ,
        `message_type` SET('OrderAcknowledgement','OrderFulfillment','OrderAdjustment') NULL ,
        `merchant_identifier` VARCHAR(40) NULL ,
        `shop_order_id` VARCHAR(20) NULL ,
        `shop_order_item_code` VARCHAR(25) NULL ,
        `merchant_order_item_id` VARCHAR(45) NULL ,
        `merchant_fulfillment_item_id` VARCHAR(45) NULL ,
        `merchant_adjustment_item_id` VARCHAR(15) NULL ,
        `quantity` INT NULL ,
        `adjustment_reason` SET('CustomerReturn','NoInventory','CouldNotShip') NULL ,
        `item_price_adjustments` DECIMAL(7,2) NULL ,
        `created_at` DATETIME NULL ,
        PRIMARY KEY (`id`, `knm_message_id`) ,
        FOREIGN KEY (`knm_message_id` )
        REFERENCES {$this->getTable('knm_message_item')} (`id` ) )
        ENGINE = InnoDB DEFAULT CHARACTER SET = utf8
");

$installer->run("
        INSERT INTO {$this->getTable('sales_order_status')} (`status`, `label`) VALUES ('test', 'Test');
        INSERT INTO {$this->getTable('sales_order_status')} (`status`, `label`) VALUES ('open', 'Open');
        INSERT INTO {$this->getTable('sales_order_status')} (`status`, `label`) VALUES ('different_article_states', 'Different article states');
        INSERT INTO {$this->getTable('sales_order_status')} (`status`, `label`) VALUES ('shipped', 'Shipped');
");

$installer->endSetup();

