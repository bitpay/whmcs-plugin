<?php

require_once 'bitpay/bp_lib.php';

function bitpay_config() {
    $configarray = array(
     	"FriendlyName" => array("Type" => "System", "Value"=>"Bit-pay"),
	 	'apiKey' => array('FriendlyName' => 'API Key from your bitpay.com account.', 'Type' => 'text'),
	 	'transactionSpeed' => array('FriendlyName' => 'Transaction Speed', 'Type' => 'dropdown', 'Options' => 'low,medium,high'),	 
    );

	return $configarray;
}



function bitpay_link($params) {
	// Check if "start" checked OR if adding funds OR completing an order, no button need to be shown in these situations.
	if (isset($_POST['start']) || (isset($_GET['a']) && $_GET['a'] == 'complete') || (isset($_GET['action']) && $_GET['action'] == 'addfunds' && isset($_POST['paymentmethod']) && $_POST['paymentmethod'] == 'paysafecard')) {

		$options['notificationURL'] = $params['systemurl'].'/modules/gateways/bit-pay/callback.php'; //Callback file.
		$options['redirectURL'] = $params['systemurl'].$params['returnurl'].'&paymentsuccess=true'; //Return URl, given by WHMCS.
		$options['apiKey'] = $params['apiKey']; //API key.
		$options['transactionSpeed'] = $params['transactionSpeed']; //Transaction speed.
		$options['currency'] = $params['currency']; //Currency, given by WHMCS.

		$invoice = bpCreateInvoice($params['invoiceid'], $params['amount'], $params['invoiceid'], $options); 

		if (isset($invoice['error']))
		{	
			logTransaction($params['paymentmethod'], $invoice['error'], 'Error');
			return "<p>Bitpay invoice error, please contact our support.</p>";
		}
		else{

			$options['status'] = 'creating bp invoice with whmcs invoice '.$params['invoiceid'].' '.$price;
			logTransaction($params['paymentmethod'], $options, 'Transaction opened');

			header("Location: ".$invoice['url']); 
			exit();
		}

 	} else {
        return '<form action="" method="POST"><input type="submit" name="start" class="form-control btn btn-primary" value="' . $params['langpaynow'] . '" /></form><br/>';
    }
}
