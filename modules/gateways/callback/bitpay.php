<?php

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once('../bit-pay/bp_lib.php');

$gatewaymodule = "bitpay";
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) 
{
	logTransaction($GATEWAY["name"],$_POST,'Not activated');
	die("Module Not Activated"); # Checks gateway module is active before accepting callback
}

$response = bpVerifyNotification($GATEWAY['apiKey']);
if (is_string($response))
{
	logTransaction($GATEWAY["name"],$_POST,$response);
	die($response);
}


if ($response['status']=="confirmed" || $response['status']=="complete") {
	$invoiceid = $response['posData'];
	$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
	
	$transid = $response['id'];
	checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
	
    # Successful
	$fee = 0;
	$amount = ''; // left blank, this will auto-fill as the full balance
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice
	logTransaction($GATEWAY["name"],$response,"Successful"); 
}
?>
