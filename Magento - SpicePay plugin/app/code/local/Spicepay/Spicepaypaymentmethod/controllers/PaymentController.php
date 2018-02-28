<?php
// app/code/local/Spicepay/Spicepaypaymentmethod/controllers/PaymentController.php
class Spicepay_Spicepaypaymentmethod_PaymentController extends Mage_Core_Controller_Front_Action 
{
	private $_logFile = 'spicepaypaymentmethod.log';
	
	protected function _getRemoteIP(){
		return Mage::helper('core/http')->getRemoteAddr(false);
	}	
	
  public function gatewayAction() 
  {
		$arr_querystring = array(
			'flag' => 1, 
			'orderId' => Mage::getSingleton('checkout/session')->getLastRealOrderId()
		);

		Mage_Core_Controller_Varien_Action::_redirect('spicepaypaymentmethod/payment/response', array('_secure' => false, '_query'=> $arr_querystring));
    
  }
   
  public function redirectAction() 
  {	
  	
	$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
	$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
	$siteId = Mage::getStoreConfig('payment/spicepaypaymentmethod/site_id');
	$custname = $order->getCustomerName();
	
	$data = array(
		'siteId' => $siteId,
		'orderId' => $orderId,
		'grandTotal' => $order->getGrandTotal(),
		'customerName' => $custname
	);
	
    $this->loadLayout();
    $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','spicepaypaymentmethod',array('template' => 'spicepaypaymentmethod/redirect.phtml'))->assign('data',$data);
    $this->getLayout()->getBlock('content')->append($block);
    $this->renderLayout();
  }
 
  public function responseAction() 
  {
    if ($this->getRequest()->get("flag") == "1" && $this->getRequest()->get("orderId")) 
    {
      /*$orderId = $this->getRequest()->get("orderId");
      $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
      $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, 'Payment Success.');
      $order->save();*/
       
      Mage::getSingleton('checkout/session')->unsQuoteId();
      Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=> false));
    }
    else
    {
      Mage_Core_Controller_Varien_Action::_redirect('', array('_secure'=> false));
    }
  }
  
  public function ipnAction()
  {
		Mage::log('IPN('.$this->_getRemoteIP().') new entry: ', 6, $this->_logFile);
		
		if ($this->getRequest()->get('paymentId')
		&& $this->getRequest()->get('orderId') && $this->getRequest()->get('hash') 
		&& $this->getRequest()->get('paymentCryptoAmount') && $this->getRequest()->get('paymentAmountUSD') 
		&& $this->getRequest()->get('receivedCryptoAmount') && $this->getRequest()->get('receivedAmountUSD')) 
		{
			
			Mage::log('IPN('.$this->_getRemoteIP().') post data: '.json_encode($this->getRequest()->getPost()), 6, $this->_logFile);
            /*
			$paymentId = $this->getRequest()->get('paymentId');
			$orderId = $this->getRequest()->get('orderId');
			$hash = $this->getRequest()->get('hash');    
			$clientId = $this->getRequest()->get('clientId');
			$paymentAmountBTC = $this->getRequest()->get('paymentAmountBTC');
			$paymentAmountUSD = $this->getRequest()->get('paymentAmountUSD');
			$receivedAmountBTC = $this->getRequest()->get('receivedAmountBTC');
			$receivedAmountUSD = $this->getRequest()->get('receivedAmountUSD');
			$status = $this->getRequest()->get('status'); // "paid", "partiallyPaid" 
			*/
			$paymentId = addslashes(filter_input(INPUT_POST, 'paymentId', FILTER_SANITIZE_STRING));
            $orderId = addslashes(filter_input(INPUT_POST, 'orderId', FILTER_SANITIZE_STRING));
            $hash = addslashes(filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));    
            $clientId = addslashes(filter_input(INPUT_POST, 'clientId', FILTER_SANITIZE_STRING));
            $paymentAmountBTC = addslashes(filter_input(INPUT_POST, 'paymentAmountBTC', FILTER_SANITIZE_NUMBER_INT));
            $paymentAmountUSD = addslashes(filter_input(INPUT_POST, 'paymentAmountUSD', FILTER_SANITIZE_STRING));
            $receivedAmountBTC = addslashes(filter_input(INPUT_POST, 'receivedAmountBTC', FILTER_SANITIZE_NUMBER_INT));
            $receivedAmountUSD = addslashes(filter_input(INPUT_POST, 'receivedAmountUSD', FILTER_SANITIZE_STRING));
            $status = addslashes(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING));
            
            if($this->getRequest()->get('paymentCryptoAmount') && $this->getRequest()->get('receivedCryptoAmount')) {
                $paymentCryptoAmount = addslashes(filter_input(INPUT_POST, 'paymentCryptoAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                $receivedCryptoAmount = addslashes(filter_input(INPUT_POST, 'receivedCryptoAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            }
            else {
                $paymentCryptoAmount = addslashes(filter_input(INPUT_POST, 'paymentAmountBTC', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                $receivedCryptoAmount = addslashes(filter_input(INPUT_POST, 'receivedAmountBTC', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            }

			$secretCode = Mage::getStoreConfig('payment/spicepaypaymentmethod/api_key');
			$hashString = md5($secretCode . $paymentId . $orderId . $clientId . $paymentCryptoAmount . $paymentAmountUSD . $receivedCryptoAmount . $receivedAmountUSD . $status);
			
			//die($hashString.' - '.$hash);

			if (0 == strcmp($hashString, $hash)) 
			{
				Mage::log('IPN('.$this->_getRemoteIP().') post hash check ok.', 6, $this->_logFile);
				
				
				Mage::log('IPN('.$this->_getRemoteIP().') IP check ok.', 6, $this->_logFile);
				
				$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				
				try{
					switch($status){
						case 'paid':						
							$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Payment Success.');
							$order->sendNewOrderEmail()->addStatusHistoryComment('You have confirmed the order to the customer via email.')
            					  ->setIsCustomerNotified(true)
            					  ->save();
							break;
						case 'partiallyPaid':						
							$order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true, 'Payment Partially Paid.');
							break;	
						default:
							$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Payment Faild. Status: '.$status);
							
					}
					
					$order->save();
					
					if (!$order->getEmailSent()) {
                    	$order->sendNewOrderEmail();
                	}
				
					Mage::log('IPN('.$this->_getRemoteIP().') status changed. Original status: '.$status, 6, $this->_logFile);
					//Process callback there 
					Mage::app()->getResponse()->setBody('OK'); //Say Spicepay server no more callbacks needed
					
				} catch(Exception $e){
					Mage::log('IPN('.$this->_getRemoteIP().') error: '.$e->getMessage(), 3, $this->_logFile);
				}	
					
					
				                           
			}
		} else {
		    Mage::app()->getResponse()->setBody('fail');
			Mage::log('IPN('.$this->_getRemoteIP().') invalid post data: '.json_encode($this->getRequest()->getPost()), 3, $this->_logFile);
		}
		
  }
}