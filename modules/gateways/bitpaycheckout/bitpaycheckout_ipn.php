<?php

/**
 * BitPay Checkout IPN 4.0.2
 *
 * This file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
// Require libraries needed for gateway module functions.
require_once  '../../../init.php';
require_once  '../../../includes/gatewayfunctions.php';
require_once  '../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = 'bitpaycheckout';
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

function checkInvoiceStatus($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$all_data = json_decode(file_get_contents("php://input"), true);
$data = $all_data['data'];

$file = 'bitpay.txt';
$err = "bitpay_err.txt";

file_put_contents($file,"===========INCOMING IPN=========================",FILE_APPEND);
file_put_contents($file,date('d.m.Y H:i:s'),FILE_APPEND);
file_put_contents($file,print_r($all_data, true),FILE_APPEND);
file_put_contents($file,"===========END OF IPN===========================",FILE_APPEND);
    
$order_status = $data['status'];
$order_invoice = $data['id'];
$endpoint = $gatewayParams['bitpay_checkout_endpoint'];
if($endpoint == "Test"):
    $url_check = 'https://test.bitpay.com/invoices/'.$order_invoice;
else:
    $url_check = 'https://bitpay.com/invoices/'.$order_invoice;
endif;
$invoiceStatus = json_decode(checkInvoiceStatus($url_check));

$orderid = $invoiceStatus->data->orderId;
$price = $invoiceStatus->data->price;
#first see if the ipn matches
#get the user id first
$table = "_bitpay_checkout_transactions";
$fields = "order_id,transaction_id";
$where = array("order_id" => $orderid,"transaction_id" => $order_invoice);

$result = select_query($table, $fields, $where);
$rowdata = mysql_fetch_array($result);
$btn_id = $rowdata['transaction_id'];

if($btn_id):
switch ($event['status']) {
     #complete, update invoice table to Paid
     case 'complete':
     
        $table = "tblinvoices";
        $update = array("status" => 'Paid','datepaid' => date("Y-m-d H:i:s"));
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        try{
        update_query($table, $update, $where);
        }
        catch (Exception $e ){
         file_put_contents($err,$e,FILE_APPEND);
      }

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => "complete");
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($err,$e,FILE_APPEND);
      }

      addInvoicePayment(
        $orderid,
        $order_invoice,
        $price,
        0,
        'bitpaycheckout'
    );
     break;
     
     #processing - put in Payment Pending
     case 'paid':
        $table = "tblinvoices";
        $update = array("status" => 'Payment Pending','datepaid' => date("Y-m-d H:i:s"));
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => 'paid');
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }
     break;
     
     #expired, remove from transaction table, wont be in invoice table
     case 'expired':
        #delete any orphans
        $table = "_bitpay_checkout_transactions";
        $delete = 'DELETE FROM _bitpay_checkout_transactions WHERE transaction_id = "' . $order_invoice.'"';
        try{
        full_query($delete);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }
     break;
}
http_response_code(200);
endif;#end of the table lookup
