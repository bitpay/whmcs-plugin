<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require('bp_lib.php');

$gatewaymodule = "bitpay";
$GATEWAY = getGatewayVariables($gatewaymodule);

// get invoice
$invoiceId = (int)$_POST['invoiceId'];
$price = $currency = false;
$result = mysql_query("SELECT tblinvoices.total, tblinvoices.status, tblcurrencies.code FROM tblinvoices, tblclients, tblcurrencies where tblinvoices.userid = tblclients.id and tblclients.currency = tblcurrencies.id and tblinvoices.id=$invoiceId");
$data = mysql_fetch_assoc($result);
if (!$data) {
	die("Invalid invoice");
}
$price = $data['total'];
$currency = $data['code'];
$status = $data['status'];
if ($status != 'Unpaid') {
	die("Invoice status must be Unpaid.  Status: ".$status);
}

// if convert-to option is set (gateway setting), then convert to requested currency
$convertTo = false;
$query = "SELECT value from tblpaymentgateways where `gateway` = '$gatewaymodule' and `setting` = 'convertto'"; 
$result = mysql_query($query);
$data = mysql_fetch_assoc($result);
if ($data)
	$convertTo = $data['value'];
if ($convertTo)
{
	// fetch $currency and $convertTo currencies
	$query= "SELECT rate FROM tblcurrencies where `code` = '$currency'";
	$result = mysql_query($query);
	$currentCurrency = mysql_fetch_assoc($result);
	if (!$currentCurrency) {
		die("Invalid invoice currency");
	}
	$result = mysql_query("SELECT code, rate FROM tblcurrencies where `id` = $convertTo");
	$convertToCurrency = mysql_fetch_assoc($result);
	if (!$convertToCurrency) {
		die("Invalid convertTo currency");
	}
		
	$currency = $convertToCurrency['code'];
	$price = $price / $currentCurrency['rate'] * $convertToCurrency['rate'];
}

// create invoice	
$options = $_POST;
unset($options['invoiceId']);
unset($options['systemURL']);
$options['notificationURL'] = $_POST['systemURL'].'/modules/gateways/callback/bitpay.php';
$options['redirectURL'] = $_POST['systemURL'];
$options['apiKey'] = $GATEWAY['apiKey'];
$options['transactionSpeed'] = $GATEWAY['transactionSpeed'];
$options['currency'] = $currency;
$invoice = bpCreateInvoice($invoiceId, $price, $invoiceId, $options);

if (array_key_exists('error', $invoice))
{
	if (ini_get('display_errors'))
		var_dump($invoice['error']);
}
else
	header("Location: ".$invoice['url']); 

?>