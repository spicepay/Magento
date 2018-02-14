<?php
// app/code/local/Spicepay/Spicepaypaymentmethod/Helper/Data.php
class Spicepay_Spicepaypaymentmethod_Helper_Data extends Mage_Core_Helper_Abstract
{
  function getPaymentGatewayUrl() 
  {
    //return Mage::getUrl('spicepaypaymentmethod/payment/gateway', array('_secure' => false));
    return 'https://www.spicepay.com/p.php';
  }
}