<?php
// app/code/local/Spicepay/Spicepaypaymentmethod/Model/Paymentmethod.php
class Spicepay_Spicepaypaymentmethod_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
  protected $_code  = 'spicepaypaymentmethod';
  
 
  public function getOrderPlaceRedirectUrl()
  {
    return Mage::getUrl('spicepaypaymentmethod/payment/redirect', array('_secure' => false));
  }
}