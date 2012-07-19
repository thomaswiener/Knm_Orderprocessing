<?php

/*
 * Das Script wird von der in der config.xml angegebenen Setup Klasse ausgefÃ¼hrt
 *
 * @var $installer Mage_Sales_Model_Mysql4_Setup
 */
$installer = $this;

/*
 * startSetup() schaltet einige automatische Prüfungen in MySQL ab, um Fehler
 * während Datenbank-Änderungen zu vermeiden.
 */
$installer->startSetup();

/*
 * Hinzufügen der neuen Attribute
 */
$this->addAttribute('order_item', 'kmo_merchant_order_item_id', array('label' => 'KMO-Seitige Id der Bestellposition', 'type' => 'varchar', 'required' => 0));
$this->addAttribute('order_item', 'qty_kmo_approved', array('label' => 'Anzahl der bestätigten Items der Bestellposition', 'type' => 'int', 'required' => 0));
$this->addAttribute('order_item', 'qty_kmo_canceled', array('label' => 'Anzahl der abgelehnten Items der Bestellposition', 'type' => 'int', 'required' => 0));
$this->addAttribute('order_item', 'qty_kmo_shipped', array('label' => 'KMO Id Bestellposition', 'type' => 'int', 'required' => 0));
$this->addAttribute('order_item', 'qty_kmo_backordered', array('label' => 'Anzahl der zurückgesandten Items der Bestellposition', 'type' => 'int', 'required' => 0));
$this->addAttribute('order_item', 'qty_kmo_couldnotship', array('label' => 'Anzahl der nicht versendbaren Items der Bestellposition', 'type' => 'int', 'required' => 0));

$this->addAttribute('shipment', 'kmo_fulfillment_id', array('label' => 'MerchantFulFillmentID der Sendung', 'type' => 'varchar', 'required' => 0));
$this->addAttribute('shipment', 'kmo_fulfillment_date', array('label' => 'FulfillmentDate der Sendung', 'type' => 'datetime', 'required' => 0));
$this->addAttribute('shipment', 'kmo_carrier_code', array('label' => 'CarrierCode der Sendung', 'type' => 'varchar', 'required' => 0));
$this->addAttribute('shipment', 'kmo_shipping_method', array('label' => 'ShippingMethod der Sendung', 'type' => 'varchar', 'required' => 0));

$this->addAttribute('shipment_item', 'kmo_merchant_fulfillment_item_id', array('label' => 'MerchantFulfillmentItemID der Bestellposition', 'type' => 'int', 'required' => 0));

/*
 * endSetup() schaltet die Prüfungen wieder ein
 */
$installer->endSetup();