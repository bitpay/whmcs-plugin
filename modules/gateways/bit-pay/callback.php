<?php

//Normal way of intergrating into WHMCS
require_once '../../../init.php';

$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

require_once 'bp_lib.php';

$GATEWAY = getGatewayVariables('bitpay');

$_REQUEST = array_merge($_POST, $_GET);

if (!$GATEWAY["type"]) 
{
	logTransaction($GATEWAY["name"], $_REQUEST, 'Not activated');
	die("Bitpay module not activated");
}

$response = bpVerifyNotification($GATEWAY['apiKey']);
if (is_string($response))
{
	logTransaction($GATEWAY["name"], $_REQUEST, $response);	
	die($response);
}


if ($response['status']=="confirmed" || $response['status']=="complete") {
	$invoiceid = $response['posData'];
	$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
	
	$transid = $response['id'];
	checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
	
    # Successful
	$fee = 0;
	$amount = ''; // left blank, this will auto-fill as the full balance
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice
	logTransaction($GATEWAY["name"], $response, "Successful"); 
}
?>
