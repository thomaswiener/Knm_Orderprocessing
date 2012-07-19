<?php
class Knm_Orderprocessing_Model_Mysql4_Item_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('orderprocessing/item');
    }
}