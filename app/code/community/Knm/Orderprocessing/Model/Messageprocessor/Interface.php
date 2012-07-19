<?php

interface Knm_Orderprocessing_Model_Messageprocessor_Interface
{
    public function handleMessage(Knm_Orderprocessing_Model_Message $message);
}